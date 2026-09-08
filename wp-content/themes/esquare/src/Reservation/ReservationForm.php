<?php

declare(strict_types=1);

namespace Esquare\Theme\Reservation;

/**
 * Custom (no Fluent Forms) salle reservation flow.
 *
 * On each salle detail page it renders a modal containing:
 *   - an inline CalendarJS calendar showing the room's non-expired bookings and
 *     letting the visitor pick one or several dates;
 *   - the reservation form (billing, schedule, layout, GDPR consents).
 *
 * Two REST routes back it:
 *   - GET  /esquare/v1/availability?room_id=… → busy slots (proxied, token stays server-side)
 *   - POST /esquare/v1/reservation            → receives a submission and forwards
 *     one entry per selected date to the external API (EntryController@store).
 *
 * Both routes are public, so the POST side only ever accepts a room from
 * PAGE_ROOM_MAP and is rate limited per IP — a submission creates real entries
 * in the shared salles.marche.be system, which has no delete route.
 */
final class ReservationForm
{
    /** Parent page id ("Louer une salle") whose children are salle detail pages. */
    public const SALLE_PARENT_ID = 7;

    public const REST_NAMESPACE = 'esquare/v1';

    /**
     * Horaires label → [start H:i, end H:i] used to build entry timestamps.
     *
     * @var array<string,array{0:string,1:string}>
     */
    private const SLOT_TIMES = [
        '8h30 à 17h00'  => ['08:30', '17:00'],
        '8h30 à 12h30'  => ['08:30', '12:30'],
        '13h00 à 17h00' => ['13:00', '17:00'],
        '18h00 à 22h00' => ['18:00', '22:00'],
    ];

    /** Accepted reservation requests per IP per window, and the window itself. */
    private const RATE_LIMIT_MAX    = 5;
    private const RATE_LIMIT_WINDOW = HOUR_IN_SECONDS;

    /** Dates one submission may ask for — each one becomes a separate entry. */
    private const MAX_DATES = 10;

    /**
     * Salle detail page id → external API room id (area 23, salles.marche.be).
     *
     * @var array<int,int>
     */
    private const PAGE_ROOM_MAP = [
        59 => 115, // La Meeting   → Meeting Room
        60 => 113, // La Box       → La Box
        61 => 114, // La Créative  → La Créative
        62 => 136, // La Talentum  → Talentum
        63 => 138, // L'Inspirante → L'Inspirante
    ];

    public static function register(): void
    {
        add_action('rest_api_init', [self::class, 'registerRoutes']);
        add_action('wp_enqueue_scripts', [self::class, 'enqueueAssets']);
        add_action('wp_footer', [self::class, 'renderModal']);
    }

    private static function isSalleDetailPage(): bool
    {
        return self::currentRoomId() > 0;
    }

    private static function currentRoomId(): int
    {
        if (! is_page()) {
            return 0;
        }
        $id = get_queried_object_id();
        if ($id <= 0 || wp_get_post_parent_id($id) !== self::SALLE_PARENT_ID) {
            return 0;
        }

        return self::PAGE_ROOM_MAP[$id] ?? 0;
    }

    public static function enqueueAssets(): void
    {
        if (! self::isSalleDetailPage()) {
            return;
        }

        // CalendarJS CE depends on LemonadeJS at runtime.
        wp_enqueue_style('calendarjs', 'https://cdn.jsdelivr.net/npm/@calendarjs/ce@1.1.0/dist/style.min.css', [], '1.1.0');
        wp_enqueue_script('lemonadejs', 'https://cdn.jsdelivr.net/npm/lemonadejs@5.3.6/dist/lemonade.min.js', [], '5.3.6', true);
        wp_enqueue_script('calendarjs', 'https://cdn.jsdelivr.net/npm/@calendarjs/ce@1.1.0/dist/index.min.js', ['lemonadejs'], '1.1.0', true);
    }

    public static function registerRoutes(): void
    {
        register_rest_route(self::REST_NAMESPACE, '/availability', [
            'methods'             => 'GET',
            'permission_callback' => '__return_true',
            'args'                => [
                'room_id' => [
                    'required'          => true,
                    'sanitize_callback' => 'absint',
                ],
            ],
            'callback'            => [self::class, 'handleAvailability'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/reservation', [
            'methods'             => 'POST',
            'permission_callback' => '__return_true',
            'callback'            => [self::class, 'handleReservation'],
        ]);
    }

    public static function handleAvailability(\WP_REST_Request $request): \WP_REST_Response
    {
        $roomId = (int) $request->get_param('room_id');
        if (! in_array($roomId, self::PAGE_ROOM_MAP, true)) {
            return new \WP_REST_Response(['message' => 'Unknown room.'], 400);
        }

        $response = new \WP_REST_Response(EntriesApi::busySlots($roomId));
        // Bookings change slowly; let the browser/CDN cache briefly.
        $response->header('Cache-Control', 'public, max-age=300');

        return $response;
    }

    public static function handleReservation(\WP_REST_Request $request): \WP_REST_Response
    {
        if (! wp_verify_nonce((string) $request->get_header('X-WP-Nonce'), 'wp_rest')) {
            return new \WP_REST_Response(['message' => __('Session expirée, veuillez recharger la page.', 'esquare')], 403);
        }

        $data   = self::sanitizeSubmission($request->get_params());
        $errors = self::validateSubmission($data);
        if ($errors !== []) {
            return new \WP_REST_Response(['message' => __('Formulaire incomplet.', 'esquare'), 'errors' => $errors], 422);
        }

        // Only valid submissions count against the quota: those are the ones that
        // create entries in the shared system. Invalid payloads have no side effect.
        if (self::isRateLimited()) {
            return new \WP_REST_Response([
                'message' => __('Trop de demandes envoyées depuis cette connexion. Merci de réessayer plus tard ou de nous contacter directement.', 'esquare'),
            ], 429);
        }

        // Always record the request so nothing is lost, even if forwarding fails.
        error_log('[esquare] Reservation received: ' . wp_json_encode($data));

        // Forward one entry per selected date to the external "add entry" API.
        // ESQUARE_API_CREATE_PATH stays the kill switch: unset it to stop
        // forwarding without a deploy (see EntriesApi).
        if (EntriesApi::isCreateConfigured()) {
            $forwarded = self::forwardToApi($data);
            if ($forwarded['failed'] > 0) {
                // The visitor's request is recorded regardless; log the API issue for ops.
                error_log(sprintf(
                    '[esquare] Reservation forwarding: %d/%d entries created (last status %d).',
                    $forwarded['created'],
                    $forwarded['created'] + $forwarded['failed'],
                    $forwarded['last_status']
                ));
            }
        }

        return new \WP_REST_Response([
            'message' => __('Votre demande de réservation a bien été envoyée. Nous vous recontacterons rapidement.', 'esquare'),
        ], 200);
    }

    /**
     * Build and POST one entry per requested date.
     *
     * @param array<string,mixed> $data Sanitized submission.
     * @return array{created:int,failed:int,last_status:int}
     */
    private static function forwardToApi(array $data): array
    {
        $created = 0;
        $failed  = 0;
        $last    = 0;

        foreach ($data['dates_souhaitees'] as $date) {
            $entry = self::buildEntry($data, (string) $date);
            if ($entry === null) {
                $failed++;
                continue;
            }

            $result = EntriesApi::createEntry($entry);
            $last   = $result['status'];
            if ($result['status'] >= 200 && $result['status'] < 300) {
                $created++;
            } else {
                $failed++;
            }
        }

        return ['created' => $created, 'failed' => $failed, 'last_status' => $last];
    }

    /**
     * Map a submission + a single date to an entry payload (modeled on the
     * GET /entries schema: room_id, start/end unix timestamps, name, description).
     *
     * @param array<string,mixed> $data
     * @return array<string,mixed>|null Null when the date or time slot is invalid.
     */
    private static function buildEntry(array $data, string $date): ?array
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || ! isset(self::SLOT_TIMES[$data['horaires']])) {
            return null;
        }

        [$startHm, $endHm] = self::SLOT_TIMES[$data['horaires']];
        $tz = wp_timezone();

        try {
            $start = new \DateTimeImmutable($date . ' ' . $startHm, $tz);
            $end   = new \DateTimeImmutable($date . ' ' . $endHm, $tz);
        } catch (\Exception $e) {
            return null;
        }

        $name = $data['societe'] !== ''
            ? $data['societe'] . ' – ' . $data['nom_prenom']
            : $data['nom_prenom'];

        $description = self::buildDescription($data);

        return [
            'room_id'          => $data['room_id'],
            'start_time'       => $start->getTimestamp(),
            'end_time'         => $end->getTimestamp(),
            'name'             => $name,
            'description'      => $description,
            'entry_type'       => 1,
            'beneficiaire_ext' => $data['email'],
            // Raw fields too, so the backend can map whatever it needs.
            'meta'             => [
                'societe'              => $data['societe'],
                'num_tva'              => $data['num_tva'],
                'adresse_facturation'  => $data['adresse_facturation'],
                'telephone'            => $data['telephone'],
                'email'                => $data['email'],
                'horaires'             => $data['horaires'],
                'horaire_precis'       => $data['horaire_precis'],
                'nombre_personnes'     => $data['nombre_personnes'],
                'disposition'          => $data['disposition'],
                'thematique_formation' => $data['thematique_formation'],
            ],
        ];
    }

    /** @param array<string,mixed> $data */
    private static function buildDescription(array $data): string
    {
        $lines = [
            'Nb personnes : ' . $data['nombre_personnes'],
            'Disposition : ' . $data['disposition'],
            'Tél : ' . $data['telephone'],
            'Email : ' . $data['email'],
            'TVA : ' . $data['num_tva'],
            'Adresse : ' . $data['adresse_facturation'],
        ];
        if ($data['horaire_precis'] !== '') {
            $lines[] = 'Précisions : ' . $data['horaire_precis'];
        }
        if ($data['thematique_formation'] !== '') {
            $lines[] = 'Thématique : ' . $data['thematique_formation'];
        }

        return implode("\n", $lines);
    }

    /**
     * Count one accepted submission for the caller's IP, and say whether the
     * quota is spent. The IP is hashed: we need to count requests, never to
     * identify visitors. Each accepted submission extends the window, so a
     * burst is held off for RATE_LIMIT_WINDOW after its last request.
     */
    private static function isRateLimited(): bool
    {
        $ip  = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $key = 'esquare_resa_' . md5($ip . wp_salt());

        $count = (int) get_transient($key);
        if ($count >= self::RATE_LIMIT_MAX) {
            return true;
        }

        set_transient($key, $count + 1, self::RATE_LIMIT_WINDOW);

        return false;
    }

    /**
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     */
    private static function sanitizeSubmission(array $params): array
    {
        $dates = $params['dates_souhaitees'] ?? [];
        if (is_string($dates)) {
            $dates = array_filter(array_map('trim', explode(',', $dates)));
        }
        $dates = array_values(array_filter(
            array_map('sanitize_text_field', (array) $dates),
            static fn (string $d): bool => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $d)
        ));

        return [
            'room_id'              => absint($params['room_id'] ?? 0),
            'salle'                => sanitize_text_field((string) ($params['salle'] ?? '')),
            'nom_prenom'           => sanitize_text_field((string) ($params['nom_prenom'] ?? '')),
            'societe'              => sanitize_text_field((string) ($params['societe'] ?? '')),
            'num_tva'              => sanitize_text_field((string) ($params['num_tva'] ?? '')),
            'adresse_facturation'  => sanitize_textarea_field((string) ($params['adresse_facturation'] ?? '')),
            'telephone'            => sanitize_text_field((string) ($params['telephone'] ?? '')),
            'email'                => sanitize_email((string) ($params['email'] ?? '')),
            'dates_souhaitees'     => $dates,
            'horaires'             => sanitize_text_field((string) ($params['horaires'] ?? '')),
            'horaire_precis'       => sanitize_textarea_field((string) ($params['horaire_precis'] ?? '')),
            'nombre_personnes'     => absint($params['nombre_personnes'] ?? 0),
            'disposition'          => sanitize_text_field((string) ($params['disposition'] ?? '')),
            'thematique_formation' => sanitize_text_field((string) ($params['thematique_formation'] ?? '')),
            'accepte_cgv'          => ! empty($params['accepte_cgv']),
            'accepte_donnees'      => ! empty($params['accepte_donnees']),
        ];
    }

    /**
     * @param array<string,mixed> $data
     * @return array<int,string>
     */
    private static function validateSubmission(array $data): array
    {
        $errors = [];
        // The room comes from a hidden field, so never trust it: a submission may
        // only target one of our salle pages, not any room of the shared system.
        if (! in_array($data['room_id'], self::PAGE_ROOM_MAP, true)) {
            $errors[] = 'room_id';
        }
        if ($data['nom_prenom'] === '') {
            $errors[] = 'nom_prenom';
        }
        if ($data['num_tva'] === '') {
            $errors[] = 'num_tva';
        }
        if ($data['adresse_facturation'] === '') {
            $errors[] = 'adresse_facturation';
        }
        if ($data['telephone'] === '') {
            $errors[] = 'telephone';
        }
        if ($data['email'] === '' || ! is_email($data['email'])) {
            $errors[] = 'email';
        }
        if ($data['dates_souhaitees'] === [] || count($data['dates_souhaitees']) > self::MAX_DATES) {
            $errors[] = 'dates_souhaitees';
        }
        // Unknown horaire → buildEntry() would silently skip the date, so reject
        // it here instead of reporting a success the visitor cannot act on.
        if (! isset(self::SLOT_TIMES[$data['horaires']])) {
            $errors[] = 'horaires';
        }
        if ($data['nombre_personnes'] <= 0) {
            $errors[] = 'nombre_personnes';
        }
        if ($data['disposition'] === '') {
            $errors[] = 'disposition';
        }
        if (! $data['accepte_cgv']) {
            $errors[] = 'accepte_cgv';
        }
        if (! $data['accepte_donnees']) {
            $errors[] = 'accepte_donnees';
        }

        return $errors;
    }

    public static function renderModal(): void
    {
        $roomId = self::currentRoomId();
        if ($roomId === 0) {
            return;
        }

        $config = [
            'restUrl' => esc_url_raw(rest_url(self::REST_NAMESPACE)),
            'nonce'   => wp_create_nonce('wp_rest'),
            'roomId'  => $roomId,
            'locale'  => 'fr',
            // Label → [start, end], so the front end can flag a horaire that
            // overlaps an existing booking. Same source as buildEntry().
            'slotTimes' => self::SLOT_TIMES,
        ];
        ?>
<dialog id="resa-modal" class="resa-modal" aria-labelledby="resa-modal-title">
    <div class="relative flex max-h-[90vh] w-full max-w-3xl flex-col overflow-hidden rounded-2xl bg-white shadow-[0_24px_48px_-12px_rgba(14,31,51,0.4)]">
        <div class="flex items-start justify-between gap-4 border-b border-navy/10 bg-navy-deep px-6 py-5 text-white lg:px-8">
            <div>
                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow">Réservation</p>
                <h2 id="resa-modal-title" class="mt-1 [font-family:var(--font-figtree)] text-xl font-bold tracking-tight" data-resa-title>Réserver une salle</h2>
            </div>
            <button type="button" data-resa-close aria-label="Fermer" class="-mr-1 inline-flex size-9 shrink-0 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>

        <form class="grid gap-6 overflow-y-auto px-6 py-6 lg:grid-cols-2 lg:px-8" data-resa-form novalidate>
            <input type="hidden" name="room_id" value="<?php echo esc_attr((string) $roomId); ?>">
            <input type="hidden" name="salle" value="" data-resa-salle>
            <input type="hidden" name="dates_souhaitees" value="" data-resa-dates required>

            <!-- Calendar column -->
            <div class="lg:col-span-1">
                <label class="resa-label">Dates souhaitées<span class="text-red-600">*</span></label>
                <p class="mb-2 text-xs text-navy/60">Cliquez sur une ou plusieurs dates. Les créneaux déjà occupés sont indiqués.</p>
                <div data-resa-calendar class="rounded-xl border border-navy/10 p-1"></div>
                <div class="mt-3" data-resa-legend>
                    <span class="inline-flex items-center gap-1.5 text-xs text-navy/70"><span class="inline-block size-2.5 rounded-full bg-yellow"></span> Sélectionnée</span>
                    <span class="ml-3 inline-flex items-center gap-1.5 text-xs text-navy/70"><span class="inline-block size-2.5 rounded-full bg-red-400"></span> Occupé</span>
                </div>
                <ul class="mt-3 flex flex-wrap gap-2" data-resa-chips aria-live="polite"></ul>
                <div class="mt-3 hidden rounded-xl border border-navy/10 p-3" data-resa-busy-info aria-live="polite"></div>
                <p class="mt-1 text-xs text-red-600 hidden" data-resa-dates-error>Sélectionnez au moins une date.</p>
            </div>

            <!-- Fields column -->
            <div class="grid gap-4 lg:col-span-1">
                <div>
                    <label class="resa-label" for="resa-nom">Nom Prénom<span class="text-red-600">*</span></label>
                    <input class="resa-input" id="resa-nom" name="nom_prenom" type="text" required autocomplete="name">
                </div>
                <div>
                    <label class="resa-label" for="resa-societe">Société</label>
                    <input class="resa-input" id="resa-societe" name="societe" type="text" autocomplete="organization">
                </div>
                <div>
                    <label class="resa-label" for="resa-tva">Num TVA (si pas, NA)<span class="text-red-600">*</span></label>
                    <input class="resa-input" id="resa-tva" name="num_tva" type="text" required>
                </div>
                <div>
                    <label class="resa-label" for="resa-adresse">Adresse de facturation<span class="text-red-600">*</span></label>
                    <textarea class="resa-input" id="resa-adresse" name="adresse_facturation" rows="2" required></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="resa-label" for="resa-tel">Téléphone<span class="text-red-600">*</span></label>
                        <input class="resa-input" id="resa-tel" name="telephone" type="tel" required autocomplete="tel">
                    </div>
                    <div>
                        <label class="resa-label" for="resa-email">Email<span class="text-red-600">*</span></label>
                        <input class="resa-input" id="resa-email" name="email" type="email" required autocomplete="email">
                    </div>
                </div>
                <fieldset>
                    <legend class="resa-label">Horaires<span class="text-red-600">*</span></legend>
                    <div class="mt-1 grid gap-1.5">
                        <?php foreach (array_keys(self::SLOT_TIMES) as $i => $slot) : ?>
                        <label class="flex items-center gap-2 text-sm text-navy">
                            <input type="radio" name="horaires" value="<?php echo esc_attr($slot); ?>" <?php echo $i === 0 ? 'required' : ''; ?> class="accent-navy">
                            <?php echo esc_html($slot); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="mt-2 text-xs text-red-600 hidden" data-resa-horaire-conflict aria-live="polite"></p>
                </fieldset>
                <div>
                    <label class="resa-label" for="resa-horaire-precis">Horaire précis et matériel nécessaire</label>
                    <textarea class="resa-input" id="resa-horaire-precis" name="horaire_precis" rows="2"></textarea>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="resa-label" for="resa-personnes">Nombre de personnes<span class="text-red-600">*</span></label>
                        <input class="resa-input" id="resa-personnes" name="nombre_personnes" type="number" min="1" required>
                    </div>
                    <div>
                        <label class="resa-label" for="resa-disposition">Disposition<span class="text-red-600">*</span></label>
                        <select class="resa-input" id="resa-disposition" name="disposition" required>
                            <option value="">Choisir…</option>
                            <option>Tables en U</option>
                            <option>Conférence (uniquement chaises)</option>
                            <option>Tables en rangées</option>
                            <option>Pas de préférence</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="resa-label" for="resa-thematique">Si formation, thématique de celle-ci ?</label>
                    <input class="resa-input" id="resa-thematique" name="thematique_formation" type="text">
                </div>
                <label class="flex items-start gap-2 text-sm text-navy">
                    <input type="checkbox" name="accepte_cgv" value="1" required class="mt-0.5 accent-navy">
                    <span>J'accepte les conditions générales de vente<span class="text-red-600">*</span></span>
                </label>
                <label class="flex items-start gap-2 text-sm text-navy">
                    <input type="checkbox" name="accepte_donnees" value="1" required class="mt-0.5 accent-navy">
                    <span>J'accepte que mes données soient utilisées lors du traitement de ma demande<span class="text-red-600">*</span></span>
                </label>
            </div>

            <div class="lg:col-span-2">
                <p class="mb-3 hidden text-sm" data-resa-message aria-live="polite"></p>
                <button type="submit" data-resa-submit class="inline-flex w-full items-center justify-center gap-3 bg-yellow px-6 py-3.5 [font-family:var(--font-figtree)] text-sm font-bold uppercase tracking-[0.14em] text-navy-deep transition-colors duration-300 hover:bg-yellow-soft disabled:cursor-not-allowed disabled:opacity-60">
                    Envoyer ma demande
                </button>
            </div>
        </form>
    </div>
</dialog>
<style>
    #resa-modal[open] { display: flex; }
    #resa-modal {
        align-items: center; justify-content: center;
        width: 100%; max-width: 100%; height: 100%; max-height: 100%;
        margin: auto; padding: 1rem; border: 0; background: transparent; overflow: hidden;
    }
    #resa-modal::backdrop { background: color-mix(in oklch, #081523 72%, transparent); backdrop-filter: blur(3px); }
    body.resa-open { overflow: hidden; }
    .resa-label { display:block; font-size:.8125rem; font-weight:600; color:#0E1F33; margin-bottom:.25rem; }
    .resa-input {
        width:100%; border:1px solid rgba(14,31,51,.18); border-radius:.5rem;
        padding:.5rem .625rem; font-size:.875rem; color:#0E1F33; background:#fff;
    }
    .resa-input:focus { outline:0; border-color:#0E1F33; box-shadow:0 0 0 1px #0E1F33; }
    .resa-chip {
        display:inline-flex; align-items:center; gap:.375rem; border-radius:9999px;
        background:#FDE3A6; color:#0E1F33; padding:.2rem .5rem .2rem .625rem; font-size:.75rem; font-weight:600;
    }
    .resa-chip button { line-height:1; font-size:1rem; color:#0E1F33; }
    /* CalendarJS renders its month navigation as Material Symbols ligatures
       ("expand_less" / "expand_more") and the theme loads no icon font, so the
       words showed up as raw text. Hide them and draw the chevrons in CSS —
       cheaper than pulling in a webfont for two arrows. */
    #resa-modal .lm-calendar-navigation button {
        display:inline-flex; align-items:center; justify-content:center;
        font-size:0; color:transparent;
    }
    #resa-modal .lm-calendar-navigation button::before {
        content:""; width:.5rem; height:.5rem;
        border-left:2px solid #0E1F33; border-bottom:2px solid #0E1F33;
    }
    #resa-modal .lm-calendar-navigation button:first-child::before {
        transform:rotate(135deg); margin-top:.25rem;    /* mois précédent */
    }
    #resa-modal .lm-calendar-navigation button:last-child::before {
        transform:rotate(-45deg); margin-bottom:.25rem; /* mois suivant */
    }
    /* CalendarJS: mark days that already have a booking. CalendarJS binds its own
       `data-event` marker as a JS property, never as an attribute, so we flag the
       busy days via `cell.bold` in the validRange callback and style that instead. */
    #resa-modal .lm-calendar-content [data-bold="true"] { position:relative; }
    #resa-modal .lm-calendar-content [data-bold="true"]::after {
        content:""; position:absolute; left:50%; bottom:3px; transform:translateX(-50%);
        width:5px; height:5px; border-radius:9999px; background:#f87171;
    }
</style>
<script>
(function () {
    var modal = document.getElementById('resa-modal');
    if (!modal || typeof modal.showModal !== 'function') { return; }

    var CONFIG = <?php echo wp_json_encode($config); ?>;
    var form      = modal.querySelector('[data-resa-form]');
    var titleEl   = modal.querySelector('[data-resa-title]');
    var salleInput= modal.querySelector('[data-resa-salle]');
    var datesInput= modal.querySelector('[data-resa-dates]');
    var chipsEl   = modal.querySelector('[data-resa-chips]');
    var datesError= modal.querySelector('[data-resa-dates-error]');
    var messageEl = modal.querySelector('[data-resa-message]');
    var submitBtn = modal.querySelector('[data-resa-submit]');
    var calHost   = modal.querySelector('[data-resa-calendar]');
    var busyInfoEl= modal.querySelector('[data-resa-busy-info]');
    var conflictEl= modal.querySelector('[data-resa-horaire-conflict]');

    var selected   = [];      // ISO 'YYYY-MM-DD', kept sorted
    var busyByDate = {};      // ISO 'YYYY-MM-DD' → its busy slots (anonymised)
    var calendar   = null;
    var loaded     = false;

    function todayISO() {
        var d = new Date();
        return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
    }

    function fmtFR(iso) {
        var p = iso.split('-');
        return p[2] + '/' + p[1] + '/' + p[0];
    }

    function syncDates() {
        selected.sort();
        datesInput.value = selected.join(',');
        chipsEl.innerHTML = '';
        selected.forEach(function (iso) {
            var li = document.createElement('li');
            li.className = 'resa-chip';
            li.appendChild(document.createTextNode(fmtFR(iso)));
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('aria-label', 'Retirer ' + fmtFR(iso));
            btn.textContent = '×';
            btn.addEventListener('click', function () {
                selected = selected.filter(function (d) { return d !== iso; });
                syncDates();
            });
            li.appendChild(btn);
            chipsEl.appendChild(li);
        });
        if (selected.length) { datesError.classList.add('hidden'); }
        renderBusyInfo();
        renderHoraireConflict();
    }

    /**
     * Hours already taken on the selected dates. A day is rarely booked solid —
     * it can be free in the morning and taken in the afternoon — so the dot on
     * the calendar is not enough: the visitor needs the hours to pick a horaire.
     * Anonymised by the server (see EntriesApi::toSlot): hours only, no names.
     */
    function renderBusyInfo() {
        if (!busyInfoEl) { return; }

        busyInfoEl.innerHTML = '';
        if (!selected.length) {
            busyInfoEl.classList.add('hidden');
            return;
        }

        var heading = document.createElement('p');
        heading.className = 'text-xs font-semibold text-navy';
        heading.textContent = 'Créneaux déjà réservés';
        busyInfoEl.appendChild(heading);

        var list = document.createElement('ul');
        list.className = 'mt-1 grid gap-1';
        selected.forEach(function (iso) {
            var li = document.createElement('li');
            li.className = 'text-xs text-navy/70';
            var day = document.createElement('strong');
            day.textContent = fmtFR(iso);
            li.appendChild(day);
            li.appendChild(document.createTextNode(' : ' + busyLabel(iso)));
            list.appendChild(li);
        });
        busyInfoEl.appendChild(list);
        busyInfoEl.classList.remove('hidden');
    }

    /**
     * Warn when the chosen horaire overlaps a booking on one of the selected
     * dates. Informative only: the request is still submitted (bookings are
     * moderated), the visitor just gets to pick another slot first.
     */
    function renderHoraireConflict() {
        if (!conflictEl) { return; }

        var choice = form.querySelector('input[name="horaires"]:checked');
        var range  = choice && CONFIG.slotTimes ? CONFIG.slotTimes[choice.value] : null;
        if (!range) {
            conflictEl.classList.add('hidden');
            return;
        }

        var clashes = [];
        selected.forEach(function (iso) {
            var hours = (busyByDate[iso] || []).filter(function (s) {
                // An open-ended slot is treated as running to the end of the day.
                return s.start && s.start < range[1] && range[0] < (s.end || '23:59');
            }).map(function (s) {
                return s.start + (s.end ? '–' + s.end : '');
            });
            if (hours.length) { clashes.push(fmtFR(iso) + ' (' + hours.join(', ') + ')'); }
        });

        if (!clashes.length) {
            conflictEl.classList.add('hidden');
            return;
        }

        conflictEl.textContent = 'Attention : ce créneau chevauche une réservation existante — '
            + clashes.join(' ; ')
            + '. Votre demande sera tout de même transmise et vérifiée par nos équipes.';
        conflictEl.classList.remove('hidden');
    }

    /** 'occupé 13:00–17:00 · 18:00–22:00', or 'aucun créneau réservé'. */
    function busyLabel(iso) {
        var seen  = {};
        var hours = (busyByDate[iso] || []).map(function (s) {
            return s.start ? s.start + (s.end ? '–' + s.end : '') : '';
        }).filter(function (h) {
            if (h === '' || seen[h]) { return false; }
            seen[h] = true;
            return true;
        }).sort();

        return hours.length ? 'occupé ' + hours.join(' · ') : 'aucun créneau réservé';
    }

    function toggleDate(iso) {
        if (!iso || iso < todayISO()) { return; }
        if (selected.indexOf(iso) === -1) { selected.push(iso); }
        syncDates();
    }

    function buildCalendar(events) {
        if (!window.calendarjs || !window.calendarjs.Calendar || !calHost) { return; }

        // ISO date → its busy slots, for validRange below and renderBusyInfo.
        busyByDate = {};
        events.forEach(function (e) {
            if (e && e.date) { (busyByDate[e.date] = busyByDate[e.date] || []).push(e); }
        });

        calendar = window.calendarjs.Calendar(calHost, {
            type: 'inline',
            format: 'YYYY-MM-DD',
            footer: false,
            startingDay: 1,            // Monday
            data: events,              // existing bookings, indexed in `busy` above
            // CalendarJS binds its own `data-event` marker as a JS property rather
            // than an attribute, so its red-dot CSS never matches. validRange runs
            // on every (re)render, so we flag the busy days ourselves — and it also
            // carries the "no past dates" rule that the array form used to provide.
            validRange: function (day, month, year, cell) {
                var iso = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(day).padStart(2, '0');
                cell.bold = !!busyByDate[iso];   // rendered as data-bold="true"
                return iso < todayISO();   // true → day disabled
            },
            onchange: function (self, value) {
                if (typeof value === 'string') { toggleDate(value.substring(0, 10)); }
            }
        });

        labelNavButtons();

        // Availability arrives after the modal opens: refresh both readouts.
        renderBusyInfo();
        renderHoraireConflict();
    }

    /**
     * The month navigation buttons carry only the ligature name ("expand_less")
     * as their label, which we hide in CSS — name them properly instead. They
     * are created once and survive the month re-renders.
     */
    function labelNavButtons() {
        var buttons = calHost.querySelectorAll('.lm-calendar-navigation button');
        if (buttons.length !== 2) { return; }
        ['Mois précédent', 'Mois suivant'].forEach(function (label, i) {
            buttons[i].setAttribute('aria-label', label);
            buttons[i].title = label;
        });
    }

    function loadAvailability() {
        if (loaded) { return; }
        loaded = true;
        fetch(CONFIG.restUrl + '/availability?room_id=' + encodeURIComponent(CONFIG.roomId), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.ok ? r.json() : []; })
            .then(function (events) { buildCalendar(Array.isArray(events) ? events : []); })
            .catch(function () { buildCalendar([]); });
    }

    function setMessage(text, ok) {
        messageEl.textContent = text;
        messageEl.classList.remove('hidden');
        messageEl.style.color = ok ? '#15803d' : '#dc2626';
    }

    function open(salle) {
        if (salle) {
            salleInput.value = salle;
            if (titleEl) { titleEl.textContent = 'Réserver — ' + salle; }
        }
        modal.showModal();
        document.body.classList.add('resa-open');
        loadAvailability();
    }

    function close() {
        modal.close();
        document.body.classList.remove('resa-open');
    }

    document.addEventListener('click', function (e) {
        var trigger = e.target.closest('[data-resa-open]');
        if (trigger) {
            e.preventDefault();
            open(trigger.getAttribute('data-salle') || '');
            return;
        }
        if (e.target.closest('[data-resa-close]')) { e.preventDefault(); close(); }
    });

    modal.addEventListener('click', function (e) { if (e.target === modal) { close(); } });
    modal.addEventListener('close', function () { document.body.classList.remove('resa-open'); });

    form.addEventListener('change', function (e) {
        if (e.target && e.target.name === 'horaires') { renderHoraireConflict(); }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!selected.length) {
            datesError.classList.remove('hidden');
            return;
        }
        if (!form.checkValidity()) { form.reportValidity(); return; }

        submitBtn.disabled = true;
        setMessage('Envoi en cours…', true);

        var payload = {};
        new FormData(form).forEach(function (value, key) { payload[key] = value; });
        payload.dates_souhaitees = selected;

        fetch(CONFIG.restUrl + '/reservation', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-WP-Nonce': CONFIG.nonce },
            body: JSON.stringify(payload)
        })
            .then(function (r) { return r.json().then(function (b) { return { ok: r.ok, body: b }; }); })
            .then(function (res) {
                if (res.ok) {
                    setMessage(res.body.message || 'Demande envoyée.', true);
                    form.reset();
                    selected = [];
                    syncDates();
                } else {
                    setMessage(res.body.message || 'Une erreur est survenue.', false);
                }
            })
            .catch(function () { setMessage('Une erreur réseau est survenue.', false); })
            .finally(function () { submitBtn.disabled = false; });
    });
})();
</script>
        <?php
    }
}
