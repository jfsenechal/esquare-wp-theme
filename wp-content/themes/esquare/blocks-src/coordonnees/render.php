<?php

/**
 * Front-end markup of the esquare/coordonnees block. Class lists mirror edit.js.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$icons = json_decode((string) file_get_contents(get_theme_file_path('assets/icons/coordonnees.json')), true) ?: [];

// RichText stores HTML: single-line fields keep their entities but no tags.
$plain = static fn (mixed $value): string => trim(wp_kses((string) $value, []));
$homeHost = (string) wp_parse_url(home_url(), PHP_URL_HOST);
$isExternal = static fn (string $url): bool => (bool) preg_match('#^https?://#i', $url)
    && wp_parse_url($url, PHP_URL_HOST) !== $homeHost;

// Same rule as edit.js: an empty link is derived from an e-mail or phone value.
$resolveUrl = static function (string $value, string $url): string {
    if ($url !== '') {
        return $url;
    }
    $text = trim(html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES));
    if (is_email($text)) {
        return 'mailto:' . $text;
    }
    $digits = preg_replace('/[^0-9+]/', '', $text) ?? '';
    if (strlen(ltrim($digits, '+')) >= 9 && ! preg_match('/[a-z]/i', $text)) {
        return 'tel:' . (str_starts_with($digits, '+') ? $digits : '+32' . ltrim($digits, '0'));
    }

    return '';
};

$cards = array_values(array_filter(
    array_map(
        static fn (mixed $c): array => [
            'icon'     => (string) ($c['icon'] ?? 'adresse'),
            'label'    => $plain($c['label'] ?? ''),
            // The value may hold line breaks (Shift+Enter in the editor).
            'value'    => trim(wp_kses((string) ($c['value'] ?? ''), ['br' => []])),
            'url'      => trim((string) ($c['url'] ?? '')),
            'note'     => $plain($c['note'] ?? ''),
            'linkText' => $plain($c['linkText'] ?? ''),
            'linkUrl'  => trim((string) ($c['linkUrl'] ?? '')),
        ],
        (array) ($attributes['cards'] ?? [])
    ),
    static fn (array $c): bool => $c['label'] !== '' || $c['value'] !== ''
));
$grids = [2 => 'sm:grid-cols-2', 4 => 'sm:grid-cols-2 lg:grid-cols-4'];
$grid  = $grids[count($cards)] ?? 'sm:grid-cols-2 lg:grid-cols-3';

$figtree   = '[font-family:var(--font-figtree)]';
$underline = 'underline decoration-yellow decoration-2 underline-offset-4 transition-colors hover:decoration-navy/40';

$eyebrow = $plain($attributes['eyebrow'] ?? '');
$title   = $plain($attributes['title'] ?? '');
$intro   = trim(wp_kses((string) ($attributes['intro'] ?? ''), [
    'strong' => [],
    'em'     => [],
    'a'      => ['href' => true, 'target' => true, 'rel' => true],
]));

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('coordonnees')) . '-title';
$wrapper = get_block_wrapper_attributes(array_filter([
    'class'           => 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
    'aria-labelledby' => $title !== '' ? $titleId : '',
]));
// phpcs:disable WordPress.Security.EscapeOutput -- values are passed through wp_kses()/esc_*() above.
?>
<section <?php echo $wrapper; ?>>
    <div class="mx-auto max-w-7xl">
        <?php if ($eyebrow !== '' || $title !== '' || $intro !== '') : ?>
            <div class="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
                <div>
                    <?php if ($eyebrow !== '') : ?>
                        <p class="inline-flex items-center gap-2 <?php echo $figtree; ?> text-xs font-bold text-navy/70 uppercase tracking-widest">
                            <span aria-hidden="true" class="inline-block size-2 rounded-full bg-yellow opacity-[0.675]"></span>
                            <?php echo $eyebrow; ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($title !== '') : ?>
                        <h2 id="<?php echo esc_attr($titleId); ?>" class="mt-3 max-w-[16ch] <?php echo $figtree; ?> text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl"><?php echo $title; ?></h2>
                    <?php endif; ?>
                </div>
                <?php if ($intro !== '') : ?>
                    <p class="max-w-[52ch] <?php echo $figtree; ?> text-pretty text-base text-navy/75 lg:text-lg"><?php echo $intro; ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($cards !== []) : ?>
            <dl class="mt-14 grid grid-cols-1 gap-5 <?php echo esc_attr($grid); ?>">
                <?php foreach ($cards as $card) :
                    $icon = $icons[$card['icon']] ?? ($icons['adresse'] ?? ['d' => '']);
                    $url  = $resolveUrl($card['value'], $card['url']);
                    ?>
                    <div class="rounded-2xl bg-stone-warm p-6 ring-1 ring-navy/8">
                        <dt class="<?php echo $figtree; ?> text-base font-bold text-navy">
                            <span aria-hidden="true" class="flex size-10 items-center justify-center rounded-xl bg-yellow/25 text-navy">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-5"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo esc_attr($icon['d']); ?>" /></svg>
                            </span>
                            <span class="mt-4 block font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-navy/70"><?php echo $card['label']; ?></span>
                        </dt>
                        <dd class="mt-2 <?php echo $figtree; ?> text-navy">
                            <?php if ($url !== '') :
                                $class = "text-xl font-bold tracking-tight text-navy $underline"
                                    . (str_starts_with($url, 'tel:') ? ' tabular-nums' : '')
                                    . (str_starts_with($url, 'mailto:') || $isExternal($url) ? ' break-all' : '');
                                ?>
                                <a href="<?php echo esc_url($url, ['http', 'https', 'mailto', 'tel']); ?>"<?php echo $isExternal($url) ? ' target="_blank" rel="noopener"' : ''; ?> class="<?php echo esc_attr($class); ?>"><?php echo $card['value']; ?></a>
                            <?php else : ?>
                                <span class="block"><?php echo $card['value']; ?></span>
                            <?php endif; ?>
                            <?php if ($card['note'] !== '') : ?>
                                <span class="mt-2 block text-base text-navy/75"><?php echo $card['note']; ?></span>
                            <?php endif; ?>
                            <?php if ($card['linkText'] !== '' && $card['linkUrl'] !== '') :
                                $external = $isExternal($card['linkUrl']);
                                ?>
                                <a href="<?php echo esc_url($card['linkUrl']); ?>"<?php echo $external ? ' target="_blank" rel="noopener"' : ''; ?> class="mt-3 flex w-fit items-center gap-2 <?php echo $figtree; ?> text-base font-bold text-navy <?php echo $underline; ?>"><?php echo $card['linkText']; ?><?php echo $external ? ' <span aria-hidden="true">↗</span>' : ''; ?></a>
                            <?php endif; ?>
                        </dd>
                    </div>
                <?php endforeach; ?>
            </dl>
        <?php endif; ?>
    </div>
</section>
<?php
// phpcs:enable
