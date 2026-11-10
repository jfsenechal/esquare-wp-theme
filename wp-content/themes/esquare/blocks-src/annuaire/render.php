<?php

/**
 * Front-end markup of the esquare/annuaire block. Class lists mirror edit.js.
 *
 * An optional "jump to" table of contents, then one card per service: name,
 * subtitle, contact lines (each with one or more links) and a description.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$figtree = '[font-family:var(--font-figtree)]';
$link    = 'font-semibold text-navy underline decoration-yellow decoration-2 underline-offset-4 transition-colors hover:decoration-navy/40';
$inline  = ['strong' => [], 'em' => []];
$rich    = $inline + ['a' => ['href' => true, 'target' => true, 'rel' => true]];
$homeHost = (string) wp_parse_url(home_url(), PHP_URL_HOST);

// An empty link URL is derived from its text: e-mail address or phone number.
$resolveUrl = static function (string $text, string $url): string {
    if ($url !== '') {
        return $url;
    }
    if (is_email($text)) {
        return 'mailto:' . $text;
    }
    $digits = preg_replace('/[^0-9+]/', '', $text) ?? '';
    if (strlen(ltrim($digits, '+')) >= 9) {
        return 'tel:' . (str_starts_with($digits, '+') ? $digits : '+32' . ltrim($digits, '0'));
    }

    return '';
};

$usedAnchors = [];
$services = [];
foreach ((array) ($attributes['services'] ?? []) as $raw) {
    $name = trim(wp_kses((string) ($raw['name'] ?? ''), []));
    if ($name === '') {
        continue;
    }
    $contacts = [];
    foreach ((array) ($raw['contacts'] ?? []) as $c) {
        $links = [];
        foreach ((array) ($c['links'] ?? []) as $l) {
            $text = trim((string) ($l['text'] ?? ''));
            if ($text !== '') {
                $links[] = ['text' => $text, 'url' => $resolveUrl($text, trim((string) ($l['url'] ?? '')))];
            }
        }
        if ($links !== []) {
            $contacts[] = ['label' => trim((string) ($c['label'] ?? '')), 'links' => $links];
        }
    }
    $anchor = sanitize_title((string) ($raw['anchor'] ?? '')) ?: sanitize_title(wp_strip_all_tags($name));
    while (isset($usedAnchors[$anchor])) {
        $anchor .= '-2';
    }
    $usedAnchors[$anchor] = true;

    $services[] = [
        'anchor'     => $anchor,
        'name'       => $name,
        'tocLabel'   => trim((string) ($raw['tocLabel'] ?? '')) ?: html_entity_decode(wp_strip_all_tags($name), ENT_QUOTES),
        'subtitle'   => trim(wp_kses((string) ($raw['subtitle'] ?? ''), [])),
        'contacts'   => $contacts,
        'paragraphs' => array_values(array_filter(array_map(
            static fn (mixed $p): string => trim(wp_kses((string) $p, $rich)),
            (array) ($raw['paragraphs'] ?? [])
        ), static fn (string $p): bool => $p !== '')),
        'bullets'    => array_values(array_filter(array_map(
            static fn (mixed $b): string => trim(wp_kses((string) $b, $inline)),
            (array) ($raw['bullets'] ?? [])
        ), static fn (string $b): bool => $b !== '')),
    ];
}
if ($services === []) {
    return;
}

$renderLink = static function (array $l, bool $alone) use ($link, $homeHost): string {
    $url = $l['url'];
    if ($url === '') {
        return sprintf('<span class="font-semibold text-navy">%s</span>', esc_html($l['text']));
    }
    $isTel    = str_starts_with($url, 'tel:');
    $isWeb    = (bool) preg_match('#^https?://#i', $url);
    $external = $isWeb && wp_parse_url($url, PHP_URL_HOST) !== $homeHost;
    // Long unbroken strings (addresses, URLs) may break anywhere on narrow screens.
    $breakAll = ! $isTel && ! str_contains($l['text'], ' ') && (str_contains($l['text'], '.') || str_contains($l['text'], '@'));
    $class    = $link . ($isTel ? ' tabular-nums' : '') . ($breakAll ? ' break-all' : '');

    return sprintf(
        '<a href="%s"%s class="%s">%s%s</a>',
        esc_url($url, ['http', 'https', 'mailto', 'tel']),
        $external ? ' target="_blank" rel="noopener"' : '',
        esc_attr($class),
        esc_html($l['text']),
        $external && $alone ? ' <span aria-hidden="true">↗</span>' : ''
    );
};

$anchor   = sanitize_title((string) ($attributes['anchor'] ?? '')) ?: wp_unique_id('annuaire');
$showToc  = (bool) ($attributes['showToc'] ?? true) && count($services) > 1;
$tocTitle = trim(wp_kses((string) ($attributes['tocTitle'] ?? ''), []));
$eyebrow  = trim(wp_kses((string) ($attributes['eyebrow'] ?? ''), []));
$title    = trim(wp_kses((string) ($attributes['title'] ?? ''), []));
$intro    = trim(wp_kses((string) ($attributes['intro'] ?? ''), $rich));

$wrapper = get_block_wrapper_attributes(['id' => $anchor]);
$last    = count($services) - 1;
// phpcs:disable WordPress.Security.EscapeOutput -- values are passed through wp_kses()/esc_*() above.
?>
<div <?php echo $wrapper; ?>>
    <?php if ($showToc) : ?>
        <nav <?php echo $tocTitle !== '' ? 'aria-labelledby="' . esc_attr($anchor . '-sommaire-title') . '"' : 'aria-label="' . esc_attr__('Sommaire', 'esquare') . '"'; ?> class="relative isolate overflow-hidden bg-navy-deep px-6 py-12 text-white lg:px-8 lg:py-14">
            <div aria-hidden="true" class="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
            <div class="mx-auto max-w-7xl">
                <?php if ($tocTitle !== '') : ?>
                    <h2 id="<?php echo esc_attr($anchor . '-sommaire-title'); ?>" class="font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow"><?php echo $tocTitle; ?></h2>
                <?php endif; ?>
                <ul role="list" class="<?php echo $tocTitle !== '' ? 'mt-5 ' : ''; ?>flex flex-wrap gap-2.5">
                    <?php foreach ($services as $service) : ?>
                        <li><a href="#<?php echo esc_attr($service['anchor']); ?>" class="inline-flex min-h-[44px] items-center rounded-full bg-white/5 px-4 <?php echo $figtree; ?> text-base font-semibold text-white ring-1 ring-white/15 transition-colors hover:bg-white/12 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-yellow"><?php echo esc_html($service['tocLabel']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </nav>
    <?php endif; ?>

    <section <?php echo $title !== '' ? 'aria-labelledby="' . esc_attr($anchor . '-title') . '"' : ''; ?> class="bg-cream px-6 py-20 lg:px-8 lg:py-24">
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
                            <h2 id="<?php echo esc_attr($anchor . '-title'); ?>" class="mt-3 max-w-[16ch] <?php echo $figtree; ?> text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl"><?php echo $title; ?></h2>
                        <?php endif; ?>
                    </div>
                    <?php if ($intro !== '') : ?>
                        <p class="max-w-[54ch] <?php echo $figtree; ?> text-pretty text-base text-navy/75 lg:text-lg"><?php echo $intro; ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div<?php echo $eyebrow !== '' || $title !== '' || $intro !== '' ? ' class="mt-16"' : ''; ?>>
                <?php foreach ($services as $index => $service) : ?>
                    <article id="<?php echo esc_attr($service['anchor']); ?>" class="grid grid-cols-1 gap-x-14 gap-y-6 border-t <?php echo $index === $last ? 'border-b ' : ''; ?>border-navy/12 py-10 lg:grid-cols-[1fr_1.55fr] lg:py-12">
                        <div>
                            <h3 class="<?php echo $figtree; ?> text-2xl font-bold tracking-tight text-navy md:text-3xl"><?php echo $service['name']; ?></h3>
                            <?php if ($service['subtitle'] !== '') : ?>
                                <p class="mt-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-navy/70"><?php echo $service['subtitle']; ?></p>
                            <?php endif; ?>
                            <?php if ($service['contacts'] !== []) : ?>
                                <ul role="list" class="mt-6 space-y-3 <?php echo $figtree; ?> text-base">
                                    <?php foreach ($service['contacts'] as $contact) :
                                        $alone = count($contact['links']) === 1; ?>
                                        <li>
                                            <?php if ($contact['label'] !== '') : ?>
                                                <span class="block font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-navy/70"><?php echo esc_html($contact['label']); ?></span>
                                            <?php endif; ?>
                                            <?php if ($alone) : ?>
                                                <?php echo $renderLink($contact['links'][0], true); ?>
                                            <?php else : ?>
                                                <span class="flex flex-wrap gap-x-4 gap-y-1">
                                                    <?php foreach ($contact['links'] as $l) {
                                                        echo $renderLink($l, false), "\n";
                                                    } ?>
                                                </span>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                        <div class="<?php echo $figtree; ?> text-pretty text-base text-navy/75 lg:text-lg [&_a]:font-semibold [&_a]:text-navy [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4">
                            <?php foreach ($service['paragraphs'] as $i => $paragraph) : ?>
                                <p<?php echo $i > 0 ? ' class="mt-4"' : ''; ?>><?php echo $paragraph; ?></p>
                            <?php endforeach; ?>
                            <?php if ($service['bullets'] !== []) : ?>
                                <ul role="list" class="<?php echo $service['paragraphs'] !== [] ? 'mt-4 ' : ''; ?>space-y-2">
                                    <?php foreach ($service['bullets'] as $bullet) : ?>
                                        <li class="flex gap-2.5"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-yellow"></span><span><?php echo $bullet; ?></span></li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</div>
<?php
// phpcs:enable
