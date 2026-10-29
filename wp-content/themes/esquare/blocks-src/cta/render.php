<?php

/**
 * Front-end markup of the esquare/cta block. Class lists mirror edit.js.
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

$buttonBase = 'group inline-flex items-center gap-2 px-6 py-3 [font-family:var(--font-figtree)] text-sm font-bold uppercase tracking-[0.14em] transition-colors';
$italic     = '[&_em]:font-serif [&_em]:font-normal [&_em]:italic [&_em]:normal-case [&_em]:tracking-[-0.01em]';
$variants   = [
    'band'   => [
        'section'   => 'bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        'inner'     => 'mx-auto flex max-w-7xl flex-col items-center gap-8 text-center lg:flex-row lg:justify-between lg:gap-12 lg:text-left',
        'eyebrow'   => 'font-mono text-xs tracking-[0.3em] text-yellow uppercase',
        'title'     => "mt-3 max-w-[22ch] [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance md:text-4xl $italic",
        'text'      => 'mt-4 max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-white/70',
        'actions'   => '',
        'row'       => 'flex flex-wrap items-center justify-center gap-3',
        'primary'   => "$buttonBase rounded-md bg-yellow text-navy hover:bg-yellow-soft",
        'secondary' => "$buttonBase rounded-md bg-white/5 text-white ring-1 ring-white/15 hover:bg-white/10",
        'note'      => 'mt-4 [font-family:var(--font-figtree)] text-base text-white/70',
    ],
    'poster' => [
        'section'   => 'px-6 py-20 lg:px-8 lg:py-24',
        'inner'     => 'relative isolate grid grid-cols-1 gap-y-8 px-8 py-12 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:gap-x-12 lg:px-12 lg:py-14',
        'eyebrow'   => 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy-deep',
        'title'     => "mt-4 max-w-[20ch] [font-family:var(--font-figtree)] text-3xl font-black uppercase leading-[1] tracking-[-0.03em] text-balance text-navy-deep md:text-4xl $italic",
        'text'      => 'mt-4 max-w-[46ch] [font-family:var(--font-figtree)] text-pretty text-navy-deep/80',
        'actions'   => 'lg:justify-self-end',
        'row'       => 'flex flex-wrap items-center gap-3',
        'primary'   => "$buttonBase bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-navy-deep",
        'secondary' => "$buttonBase bg-navy-deep/10 text-navy-deep ring-1 ring-navy-deep/25 hover:bg-navy-deep/20",
        'note'      => 'mt-4 max-w-[34ch] [font-family:var(--font-figtree)] text-base font-semibold text-navy-deep/70',
    ],
    'card'   => [
        'section'   => 'bg-stone-warm px-6 py-16 lg:px-8 lg:py-20',
        'inner'     => 'mx-auto flex max-w-7xl flex-col items-center gap-8 rounded-3xl bg-white p-10 text-center ring-1 ring-navy/8 lg:flex-row lg:gap-12 lg:p-12 lg:text-left',
        'eyebrow'   => 'font-mono text-xs tracking-[0.3em] text-navy/70 uppercase',
        'title'     => "[font-family:var(--font-figtree)] text-2xl font-bold tracking-tight text-balance text-navy md:text-3xl $italic",
        'text'      => 'mt-3 max-w-[60ch] [font-family:var(--font-figtree)] text-pretty text-navy/70',
        'actions'   => 'shrink-0',
        'row'       => 'flex flex-wrap items-center justify-center gap-3',
        'primary'   => "$buttonBase bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-navy-deep",
        'secondary' => "$buttonBase bg-navy/5 text-navy ring-1 ring-navy/15 hover:bg-navy/10",
        'note'      => 'mt-3 [font-family:var(--font-figtree)] text-base text-navy/70',
    ],
];

$variant = (string) ($attributes['variant'] ?? 'band');
$variant = isset($variants[$variant]) ? $variant : 'band';
$v       = $variants[$variant];
$eyebrow = $plain($attributes['eyebrow'] ?? '');
$title   = trim(wp_kses((string) ($attributes['title'] ?? ''), ['em' => []]));
$text    = $inline($attributes['text'] ?? '');
$note    = $plain($attributes['note'] ?? '');
$buttons = array_values(array_filter(
    array_map(
        static fn (mixed $b): array => ['text' => $plain($b['text'] ?? ''), 'url' => trim((string) ($b['url'] ?? ''))],
        (array) ($attributes['buttons'] ?? [])
    ),
    static fn (array $b): bool => $b['text'] !== '' && $b['url'] !== ''
));

$logo = '';
if ($variant === 'card') {
    $logoId  = (int) ($attributes['logoId'] ?? 0);
    $logoAlt = trim((string) ($attributes['logoAlt'] ?? ''));
    $logo    = $logoId > 0 ? wp_get_attachment_image($logoId, 'medium', false, [
        'class'    => 'h-16 w-auto shrink-0',
        'alt'      => $logoAlt,
        'loading'  => 'lazy',
        'decoding' => 'async',
    ]) : '';
    $logoUrl = (string) ($attributes['logoUrl'] ?? '');
    if ($logo === '' && $logoUrl !== '') {
        $logo = sprintf(
            '<img src="%s" alt="%s" loading="lazy" decoding="async" class="h-16 w-auto shrink-0" />',
            esc_url($logoUrl),
            esc_attr($logoAlt)
        );
    }
}

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('cta')) . '-title';
$wrapper = get_block_wrapper_attributes([
    'class'           => $v['section'],
    'aria-labelledby' => $titleId,
]);
$titleClass = $variant === 'card' && $eyebrow !== '' ? 'mt-3 ' . $v['title'] : $v['title'];
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <?php if ($variant === 'poster') : ?>
    <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-yellow ring-1 ring-navy/10">
    <?php endif; ?>
        <div class="<?php echo esc_attr($v['inner']); ?>">
            <?php if ($variant === 'poster') : ?>
                <div aria-hidden="true" class="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
            <?php endif; ?>
            <?php echo $logo; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above. ?>
            <div<?php echo $variant === 'poster' ? '' : ' class="lg:flex-1"'; ?>>
                <?php if ($eyebrow !== '') : ?>
                    <p class="<?php echo esc_attr($v['eyebrow']); ?>">
                        <?php if ($variant === 'poster') : ?>
                            <span aria-hidden="true" class="inline-block size-1.5 rounded-full bg-navy-deep"></span>
                        <?php endif; ?>
                        <?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?>
                    </p>
                <?php endif; ?>
                <h2 id="<?php echo esc_attr($titleId); ?>" class="<?php echo esc_attr($titleClass); ?>"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() allowing <em>. ?></h2>
                <?php if ($text !== '') : ?>
                    <p class="<?php echo esc_attr($v['text']); ?>"><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
                <?php endif; ?>
            </div>
            <?php if ($buttons !== [] || $note !== '') : ?>
                <div<?php echo $v['actions'] !== '' ? ' class="' . esc_attr($v['actions']) . '"' : ''; ?>>
                    <?php if ($buttons !== []) : ?>
                        <div class="<?php echo esc_attr($v['row']); ?>">
                            <?php foreach ($buttons as $index => $button) :
                                $isExternal = (bool) preg_match('#^https?://#i', $button['url'])
                                    && wp_parse_url($button['url'], PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST);
                                ?>
                                <a href="<?php echo esc_url($button['url']); ?>"<?php echo $isExternal ? ' target="_blank" rel="noopener"' : ''; ?> class="<?php echo esc_attr($index === 0 ? $v['primary'] : $v['secondary']); ?>">
                                    <?php echo $button['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                    <?php if ($isExternal) : ?>
                                        <span aria-hidden="true" class="inline-block transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1.5">↗</span>
                                    <?php endif; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($note !== '') : ?>
                        <p class="<?php echo esc_attr($v['note']); ?>"><?php echo $note; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php if ($variant === 'poster') : ?>
    </div>
    <?php endif; ?>
</section>
