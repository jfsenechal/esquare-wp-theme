<?php

declare(strict_types=1);

namespace Esquare\Theme\Reservation;

/**
 * Salle reservation: renders the Fluent Forms reservation form inside a modal
 * on each salle detail page, enables multi-day selection, and forwards each
 * submission to an external API.
 */
final class ReservationForm
{
    /** Fluent Forms form id for "Réservation de salle". */
    public const FORM_ID = 3;

    /** Parent page id ("Louer une salle") whose children are salle detail pages. */
    public const SALLE_PARENT_ID = 7;

    public static function register(): void
    {
        // Forward every reservation submission to the external API.
        add_action('fluentform_submission_inserted', [self::class, 'forwardToApi'], 20, 3);

        // Render the modal + form on salle detail pages only.
        add_action('wp_footer', [self::class, 'renderModal']);
    }

    private static function isSalleDetailPage(): bool
    {
        if (! is_page()) {
            return false;
        }
        $id = get_queried_object_id();

        return $id > 0 && wp_get_post_parent_id($id) === self::SALLE_PARENT_ID;
    }

    public static function renderModal(): void
    {
        if (! self::isSalleDetailPage()) {
            return;
        }

        $form = do_shortcode('[fluentform id="' . self::FORM_ID . '"]');
        ?>
<dialog id="resa-modal" class="resa-modal" aria-labelledby="resa-modal-title">
    <div class="relative w-full max-w-2xl overflow-hidden rounded-2xl bg-white shadow-[0_24px_48px_-12px_rgba(14,31,51,0.4)]">
        <div class="flex items-start justify-between gap-4 border-b border-navy/10 bg-navy-deep px-6 py-5 text-white lg:px-8">
            <div>
                <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow">Réservation</p>
                <h2 id="resa-modal-title" class="mt-1 [font-family:var(--font-figtree)] text-xl font-bold tracking-tight" data-resa-title>Réserver une salle</h2>
            </div>
            <button type="button" data-resa-close aria-label="Fermer" class="-mr-1 inline-flex size-9 shrink-0 items-center justify-center rounded-full text-white/70 transition-colors hover:bg-white/10 hover:text-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="max-h-[75vh] overflow-y-auto px-6 py-6 lg:px-8">
            <?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput -- Fluent Forms shortcode output. ?>
        </div>
    </div>
</dialog>
<style>
    #resa-modal[open] { display: flex; }
    #resa-modal {
        align-items: center;
        justify-content: center;
        width: 100%;
        max-width: 100%;
        height: 100%;
        max-height: 100%;
        margin: auto;
        padding: 1rem;
        border: 0;
        background: transparent;
        overflow: hidden;
    }
    #resa-modal::backdrop {
        background: color-mix(in oklch, #081523 72%, transparent);
        backdrop-filter: blur(3px);
    }
    body.resa-open { overflow: hidden; }
    /* Light theming for the Fluent Forms inputs inside the modal */
    #resa-modal .ff-el-input--content input[type=text],
    #resa-modal .ff-el-input--content input[type=email],
    #resa-modal .ff-el-input--content input[type=number],
    #resa-modal .ff-el-input--content textarea,
    #resa-modal .ff-el-input--content select {
        border-radius: 0.5rem;
        border-color: rgba(14,31,51,0.18);
    }
    #resa-modal .ff-el-input--content input:focus,
    #resa-modal .ff-el-input--content textarea:focus,
    #resa-modal .ff-el-input--content select:focus {
        border-color: #0E1F33;
        box-shadow: 0 0 0 1px #0E1F33;
    }
    #resa-modal .ff-btn-submit { border-radius: 0.5rem; font-weight: 700; }
</style>
<script>
(function () {
    var modal = document.getElementById('resa-modal');
    if (!modal || typeof modal.showModal !== 'function') { return; }

    var titleEl = modal.querySelector('[data-resa-title]');
    var salleInput = modal.querySelector('input[name="salle"]');

    function enableMultiDate() {
        var input = modal.querySelector('input[name="dates_souhaitees"]');
        if (!input) { return; }
        // Fluent Forms attaches a flatpickr instance to the input once initialised.
        if (input._flatpickr) {
            input._flatpickr.set('mode', 'multiple');
            input._flatpickr.set('dateFormat', 'd/m/Y');
            input._flatpickr.set('conjunction', ', ');
        } else if (window.flatpickr) {
            window.flatpickr(input, { mode: 'multiple', dateFormat: 'd/m/Y', conjunction: ', ' });
        }
    }

    function open(salle) {
        if (salle) {
            if (salleInput) { salleInput.value = salle; }
            if (titleEl) { titleEl.textContent = 'Réserver — ' + salle; }
        }
        modal.showModal();
        document.body.classList.add('resa-open');
        // Give Fluent Forms a tick to (re)initialise the date picker.
        setTimeout(enableMultiDate, 60);
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
        if (e.target.closest('[data-resa-close]')) {
            e.preventDefault();
            close();
        }
    });

    // Click on the backdrop (outside the panel) closes the modal.
    modal.addEventListener('click', function (e) {
        if (e.target === modal) { close(); }
    });
    modal.addEventListener('close', function () {
        document.body.classList.remove('resa-open');
    });
})();
</script>
        <?php
    }

    /**
     * @param int|string $entryId
     * @param array<string,mixed> $formData
     * @param object $form
     */
    public static function forwardToApi($entryId, $formData, $form): void
    {
        if ((int) ($form->id ?? 0) !== self::FORM_ID) {
            return;
        }

        $endpoint = self::endpoint();
        if ($endpoint === '') {
            // No endpoint configured yet — record it so nothing is lost silently.
            error_log('[esquare] Reservation API endpoint not configured; entry #' . $entryId . ' not forwarded.');
            return;
        }

        $dates = isset($formData['dates_souhaitees'])
            ? array_values(array_filter(array_map('trim', explode(',', (string) $formData['dates_souhaitees']))))
            : [];

        $payload = [
            'entry_id'             => (int) $entryId,
            'form_id'              => self::FORM_ID,
            'salle'                => $formData['salle'] ?? '',
            'nom_prenom'           => $formData['nom_prenom'] ?? '',
            'societe'              => $formData['societe'] ?? '',
            'num_tva'              => $formData['num_tva'] ?? '',
            'adresse_facturation'  => $formData['adresse_facturation'] ?? '',
            'telephone'            => $formData['telephone'] ?? '',
            'email'                => $formData['email'] ?? '',
            'dates_souhaitees'     => $dates,
            'horaires'             => $formData['horaires'] ?? '',
            'horaire_precis'       => $formData['horaire_precis'] ?? '',
            'nombre_personnes'     => $formData['nombre_personnes'] ?? '',
            'disposition'          => $formData['disposition'] ?? '',
            'thematique_formation' => $formData['thematique_formation'] ?? '',
            'accepte_cgv'          => ! empty($formData['gdpr_cgv']),
            'accepte_donnees'      => ! empty($formData['gdpr_data']),
            'submitted_at'         => current_time('c'),
            'source_url'           => home_url(add_query_arg([], $GLOBALS['wp']->request ?? '')),
        ];

        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        $token = self::token();
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        $response = wp_remote_post($endpoint, [
            'timeout'  => 15,
            'headers'  => $headers,
            'body'     => wp_json_encode($payload),
        ]);

        if (is_wp_error($response)) {
            error_log('[esquare] Reservation API call failed for entry #' . $entryId . ': ' . $response->get_error_message());
            return;
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            error_log('[esquare] Reservation API returned HTTP ' . $code . ' for entry #' . $entryId . '.');
        }
    }

    private static function endpoint(): string
    {
        $url = getenv('RESERVATION_API_URL') ?: ($_ENV['RESERVATION_API_URL'] ?? '');

        return (string) apply_filters('esquare_reservation_api_url', $url);
    }

    private static function token(): string
    {
        $token = getenv('RESERVATION_API_TOKEN') ?: ($_ENV['RESERVATION_API_TOKEN'] ?? '');

        return (string) apply_filters('esquare_reservation_api_token', $token);
    }
}
