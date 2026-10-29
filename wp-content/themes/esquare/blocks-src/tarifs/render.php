<?php

/**
 * Front-end markup of the esquare/tarifs block. Class lists mirror edit.js.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// RichText stores HTML: single-line fields keep their entities but no tags.
$plain  = static fn (mixed $value): string => trim(wp_kses((string) $value, []));
$inline = static fn (mixed $value): string => trim(wp_kses((string) $value, [
    'strong' => [],
    'em'     => [],
    'a'      => ['href' => true, 'target' => true, 'rel' => true],
]));
// "175 €" must never wrap between the number and the currency.
$money = static fn (mixed $value): string => (string) preg_replace('/(?:\s|&nbsp;)+(€)/u', "\u{00A0}$1", $plain($value));
$isExternal = static fn (string $url): bool => (bool) preg_match('#^https?://#i', $url)
    && wp_parse_url($url, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST);

$figtree = '[font-family:var(--font-figtree)]';
$themes  = [
    'light' => [
        'section'   => 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
        'eyebrow'   => "inline-flex items-center gap-2 $figtree text-xs font-bold text-navy/70 uppercase tracking-widest",
        'dot'       => 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        'title'     => "mt-3 max-w-[15ch] $figtree text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl",
        'intro'     => "max-w-[54ch] $figtree text-pretty text-base text-navy/75 lg:text-lg",
        'listTitle' => 'font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy/60',
        'button'    => 'bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-navy-deep',
        'footnote'  => "$figtree text-base text-navy/70",
        'card'      => 'stone',
        'featured'  => 'navy',
    ],
    'dark'  => [
        'section'   => 'relative isolate overflow-hidden bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        'eyebrow'   => 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow',
        'dot'       => 'inline-block size-1.5 rounded-full bg-yellow',
        'title'     => "mt-4 max-w-[14ch] $figtree text-3xl font-bold tracking-tight text-balance md:text-4xl lg:text-5xl",
        'intro'     => "max-w-[52ch] $figtree text-pretty text-base text-white/70 lg:text-lg",
        'listTitle' => 'font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-white/50',
        'button'    => 'bg-yellow text-navy-deep hover:bg-yellow-soft focus-visible:outline-yellow',
        'footnote'  => "$figtree text-base text-white/60",
        'card'      => 'glass',
        'featured'  => 'glass',
    ],
];
// Colours of everything inside an offer, by the surface it sits on.
$tones = [
    'navy'  => ['card' => 'bg-navy-deep text-white ring-1 ring-navy/10', 'title' => 'text-white', 'tag' => 'bg-yellow text-navy-deep', 'text' => 'text-white/70', 'rule' => 'border-white/15', 'label' => 'text-white/70', 'price' => 'text-white', 'unit' => 'text-white/55', 'item' => 'text-white', 'note' => 'text-white/65'],
    'stone' => ['card' => 'bg-stone-warm text-navy ring-1 ring-navy/10', 'title' => 'text-navy', 'tag' => 'bg-navy-deep text-yellow', 'text' => 'text-navy/75', 'rule' => 'border-navy/12', 'label' => 'text-navy/75', 'price' => 'text-navy', 'unit' => 'text-navy/70', 'item' => 'text-navy', 'note' => 'text-navy/70'],
    'glass' => ['card' => 'bg-white/[0.06] text-white ring-1 ring-white/15', 'title' => 'text-white', 'tag' => 'bg-yellow text-navy-deep', 'text' => 'text-white/70', 'rule' => 'border-white/15', 'label' => 'text-white/70', 'price' => 'text-yellow', 'unit' => 'text-white/60', 'item' => 'text-white', 'note' => 'text-white/65'],
];
$cardGrid     = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'];
$featuredGrid = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-[1.35fr_1fr]', 3 => 'lg:grid-cols-[1.35fr_1fr_1fr]', 4 => 'lg:grid-cols-[1.35fr_1fr_1fr_1fr]'];

$theme        = (string) ($attributes['theme'] ?? 'light');
$t            = $themes[$theme] ?? $themes['light'];
$isDark       = $theme === 'dark';
$isList       = ($attributes['layout'] ?? 'cards') === 'list';
$featureFirst = (bool) ($attributes['featureFirst'] ?? true);
$eyebrow      = $plain($attributes['eyebrow'] ?? '');
$title        = $plain($attributes['title'] ?? '');
$intro        = $inline($attributes['intro'] ?? '');
$listTitle    = $plain($attributes['listTitle'] ?? '');
$btnText      = $plain($attributes['buttonText'] ?? '');
$btnUrl       = trim((string) ($attributes['buttonUrl'] ?? ''));
$footnote     = $plain($attributes['footnote'] ?? '');

$offers = array_values(array_filter(
    array_map(
        static fn (mixed $o): array => [
            'name'     => $plain($o['name'] ?? ''),
            'tag'      => $plain($o['tag'] ?? ''),
            'text'     => trim(wp_kses((string) ($o['text'] ?? ''), ['strong' => []])),
            'prices'   => array_values(array_filter(
                array_map(
                    static fn (mixed $p): array => ['label' => $plain($p['label'] ?? ''), 'amount' => $money($p['amount'] ?? ''), 'unit' => $plain($p['unit'] ?? '')],
                    (array) ($o['prices'] ?? [])
                ),
                static fn (array $p): bool => $p['amount'] !== ''
            )),
            'features' => array_values(array_filter(array_map($plain, (array) ($o['features'] ?? [])), static fn (string $f): bool => $f !== '')),
            'note'     => $plain($o['note'] ?? ''),
        ],
        (array) ($attributes['offers'] ?? [])
    ),
    static fn (array $o): bool => $o['name'] !== ''
));

/** "175 € / an (HTVA)": the amount, then the unit in a smaller, muted span. */
$amount = static fn (array $price, string $class, string $unitClass): string => sprintf(
    '<p class="%s">%s%s</p>',
    esc_attr("$figtree font-bold tracking-tight tabular-nums $class"),
    $price['amount'],
    $price['unit'] !== '' ? ' <span class="' . esc_attr("text-base font-semibold tracking-normal $unitClass") . '">' . $price['unit'] . '</span>' : ''
);

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('tarifs')) . '-title';
$wrapper = get_block_wrapper_attributes([
    'class'           => $t['section'],
    'aria-labelledby' => $titleId,
]);
$listOffers = $isList && $featureFirst ? array_slice($offers, 1) : $offers;
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <?php if ($isDark) : ?>
        <div aria-hidden="true" class="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
    <?php endif; ?>
    <div class="mx-auto max-w-7xl">
        <div class="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
            <div>
                <?php if ($eyebrow !== '') : ?>
                    <p class="<?php echo esc_attr($t['eyebrow']); ?>">
                        <span aria-hidden="true" class="<?php echo esc_attr($t['dot']); ?>"></span>
                        <?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?>
                    </p>
                <?php endif; ?>
                <h2 id="<?php echo esc_attr($titleId); ?>" class="<?php echo esc_attr($t['title']); ?>"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
            </div>
            <?php if ($intro !== '') : ?>
                <p class="<?php echo esc_attr($t['intro']); ?>"><?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
            <?php endif; ?>
        </div>

        <?php if ($isList) : ?>
            <?php if ($featureFirst && isset($offers[0])) :
                $offer = $offers[0];
                $tone  = $tones[$t['featured']];
                ?>
                <div class="mt-14 flex flex-col gap-6 rounded-2xl p-7 sm:flex-row sm:items-center sm:justify-between lg:p-9 <?php echo esc_attr($tone['card']); ?>">
                    <div>
                        <h3 class="<?php echo esc_attr("$figtree text-2xl font-bold tracking-tight {$tone['title']}"); ?>"><?php echo $offer['name']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                        <?php if ($offer['text'] !== '') : ?>
                            <p class="<?php echo esc_attr("mt-2 max-w-[46ch] $figtree text-pretty text-base {$tone['text']}"); ?>"><?php echo $offer['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                        <?php endif; ?>
                    </div>
                    <?php if (isset($offer['prices'][0])) : ?>
                        <div class="shrink-0"><?php echo $amount($offer['prices'][0], "text-4xl {$tone['price']}", $tone['unit']); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            <?php if ($listTitle !== '') : ?>
                <h3 class="<?php echo esc_attr(($featureFirst ? 'mt-16 ' : 'mt-14 ') . $t['listTitle']); ?>"><?php echo $listTitle; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
            <?php endif; ?>
            <?php if ($listOffers !== []) :
                $tone = $tones[$isDark ? 'glass' : 'stone'];
                ?>
                <ul role="list" class="<?php echo $listTitle !== '' ? 'mt-4' : 'mt-14'; ?>">
                    <?php foreach ($listOffers as $index => $offer) : ?>
                        <li class="grid grid-cols-1 items-baseline gap-x-8 gap-y-2 border-t py-6 sm:grid-cols-[minmax(0,1fr)_auto] lg:grid-cols-[15rem_minmax(0,1fr)_auto] lg:py-7 <?php echo esc_attr($tone['rule']); ?><?php echo $index === count($listOffers) - 1 ? ' border-b' : ''; ?>">
                            <div>
                                <h4 class="<?php echo esc_attr("$figtree text-xl font-bold tracking-tight lg:text-2xl {$tone['title']}"); ?>"><?php echo $offer['name']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h4>
                                <?php if ($offer['tag'] !== '') : ?>
                                    <p class="mt-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.18em] <?php echo $isDark ? 'text-yellow' : 'text-navy/70'; ?>"><?php echo $offer['tag']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                <?php endif; ?>
                            </div>
                            <p class="<?php echo esc_attr("$figtree text-base {$tone['text']}"); ?>"><?php echo $offer['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                            <?php if (isset($offer['prices'][0])) : ?>
                                <div class="lg:text-right"><?php echo $amount($offer['prices'][0], 'text-3xl ' . ($isDark ? 'text-white' : $tone['price']), $tone['unit']); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        <?php elseif ($offers !== []) : ?>
            <div class="mt-14 grid grid-cols-1 gap-6 <?php echo esc_attr(($featureFirst ? $featuredGrid : $cardGrid)[count($offers)] ?? $cardGrid[3]); ?>">
                <?php foreach ($offers as $index => $offer) :
                    $tone   = $tones[$featureFirst && $index === 0 ? $t['featured'] : $t['card']];
                    $single = count($offer['prices']) === 1 && $offer['prices'][0]['label'] === '';
                    ?>
                    <div class="flex flex-col rounded-2xl p-7 lg:p-8 <?php echo esc_attr($tone['card']); ?>">
                        <div class="flex items-start justify-between gap-4">
                            <h3 class="<?php echo esc_attr("$figtree text-2xl font-bold tracking-tight {$tone['title']}"); ?>"><?php echo $offer['name']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                            <?php if ($offer['tag'] !== '') : ?>
                                <span class="<?php echo esc_attr("shrink-0 rounded-full px-3 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] {$tone['tag']}"); ?>"><?php echo $offer['tag']; // phpcs:ignore WordPress.Security.EscapeOutput ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if ($offer['text'] !== '') : ?>
                            <p class="<?php echo esc_attr("mt-3 max-w-[40ch] $figtree text-base {$tone['text']}"); ?>"><?php echo $offer['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                        <?php endif; ?>
                        <?php if ($single) : ?>
                            <div class="mt-7"><?php echo $amount($offer['prices'][0], "text-4xl {$tone['price']}", $tone['unit']); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></div>
                        <?php elseif ($offer['prices'] !== []) : ?>
                            <dl class="mt-7 <?php echo esc_attr($figtree); ?>">
                                <?php foreach ($offer['prices'] as $price) : ?>
                                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t py-4 <?php echo esc_attr($tone['rule']); ?>">
                                        <dt class="<?php echo esc_attr("text-base {$tone['label']}"); ?>"><?php echo $price['label']; // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
                                        <dd><?php echo $amount($price, "text-3xl {$tone['price']}", $tone['unit']); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></dd>
                                    </div>
                                <?php endforeach; ?>
                            </dl>
                        <?php endif; ?>
                        <?php if ($offer['features'] !== []) : ?>
                            <ul role="list" class="<?php echo esc_attr("mt-6 space-y-2.5 border-t pt-6 $figtree text-base {$tone['rule']} {$tone['item']}"); ?>">
                                <?php foreach ($offer['features'] as $feature) : ?>
                                    <li class="flex gap-2.5"><span aria-hidden="true" class="mt-2.5 size-1.5 shrink-0 rounded-full bg-yellow"></span><?php echo $feature; // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <?php if ($offer['note'] !== '') : ?>
                            <p class="<?php echo esc_attr("mt-auto pt-6 $figtree text-base {$tone['note']}"); ?>"><?php echo $offer['note']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (($btnText !== '' && $btnUrl !== '') || $footnote !== '') : ?>
            <div class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4">
                <?php if ($btnText !== '' && $btnUrl !== '') :
                    $external = $isExternal($btnUrl);
                    ?>
                    <a href="<?php echo esc_url($btnUrl); ?>"<?php echo $external ? ' target="_blank" rel="noopener"' : ''; ?> class="<?php echo esc_attr("group inline-flex items-center gap-3 px-6 py-3 $figtree text-sm font-bold uppercase tracking-[0.14em] transition-colors duration-300 focus-visible:outline-2 focus-visible:outline-offset-4 {$t['button']}"); ?>">
                        <?php echo $btnText; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        <span aria-hidden="true" class="inline-block transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1.5"><?php echo $external ? '↗' : '→'; ?></span>
                    </a>
                <?php endif; ?>
                <?php if ($footnote !== '') : ?>
                    <p class="<?php echo esc_attr($t['footnote']); ?>"><?php echo $footnote; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
