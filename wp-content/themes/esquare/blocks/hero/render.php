<?php

/**
 * Front-end markup of the esquare/hero block, in its `cover` or `split` layout.
 * Class lists mirror edit.js.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// RichText stores HTML: single-line fields keep their entities but no tags.
$text = static fn (string $key): string => trim(wp_kses((string) ($attributes[$key] ?? ''), []));

$isCover = ($attributes['layout'] ?? 'cover') !== 'split';
$intro   = trim(wp_kses((string) ($attributes['intro'] ?? ''), [
    'strong' => [],
    'em'     => [],
    'a'      => ['href' => true, 'target' => true, 'rel' => true],
]));

$focal         = (array) ($attributes['focalPoint'] ?? []);
$imagePosition = sprintf(
    '%s%% %s%%',
    round(100 * min(1, max(0, (float) ($focal['x'] ?? 0.5))), 2),
    round(100 * min(1, max(0, (float) ($focal['y'] ?? 0.5))), 2)
);

$imageAlt   = trim((string) ($attributes['imageAlt'] ?? ''));
$imageClass = $isCover
    ? 'absolute inset-0 -z-20 size-full object-cover'
    : 'aspect-[16/10] w-full rounded-2xl object-cover shadow-[0_30px_60px_-20px_color-mix(in_oklch,var(--color-navy-deep)_60%,transparent)] ring-1 ring-white/10';
$imageAttrs = [
    'class'         => $imageClass,
    'alt'           => $imageAlt,
    'style'         => 'object-position: ' . $imagePosition,
    'fetchpriority' => 'high',
    'decoding'      => 'async',
    'loading'       => false,
];
if ($imageAlt === '') {
    $imageAttrs['aria-hidden'] = 'true';
}

// The media library entry gives real dimensions and a srcset; the URL is the fallback.
$imageId  = (int) ($attributes['imageId'] ?? 0);
$imageUrl = (string) ($attributes['imageUrl'] ?? '');
$image    = $imageId > 0 ? wp_get_attachment_image($imageId, 'full', false, $imageAttrs) : '';
if ($image === '' && $imageUrl !== '') {
    $image = sprintf(
        '<img src="%s" alt="%s" class="%s" style="%s" fetchpriority="high" decoding="async"%s />',
        esc_url($imageUrl),
        esc_attr($imageAlt),
        esc_attr($imageClass),
        esc_attr($imageAttrs['style']),
        $imageAlt === '' ? ' aria-hidden="true"' : ''
    );
}

$wrapper = get_block_wrapper_attributes([
    'class' => $isCover
        ? 'relative isolate flex min-h-[62vh] items-end overflow-hidden bg-navy-deep text-white'
        : 'relative isolate overflow-hidden bg-navy-deep text-white',
]);
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
<?php if ($isCover) : ?>
    <?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above. ?>
    <div aria-hidden="true" class="absolute inset-0 -z-10" style="background: linear-gradient(180deg, color-mix(in oklch, var(--color-navy-deep) 18%, transparent) 0%, color-mix(in oklch, var(--color-navy-deep) 68%, transparent) 52%, color-mix(in oklch, var(--color-navy-deep) 96%, transparent) 100%);"></div>

    <div class="relative z-10 mx-auto w-full max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
<?php else : ?>
    <div aria-hidden="true" class="poster-grid absolute inset-0 z-0 opacity-[0.12]"></div>
    <div aria-hidden="true" class="absolute inset-0 z-0" style="background: radial-gradient(120% 80% at 100% 0%, color-mix(in oklch, var(--color-navy-soft) 85%, transparent), transparent 58%);"></div>
    <div class="relative z-10 mx-auto grid max-w-7xl grid-cols-1 items-center gap-x-10 gap-y-10 px-6 py-14 lg:grid-cols-12 lg:px-8 lg:py-20">
        <div class="lg:col-span-6">
<?php endif; ?>

        <?php if ($text('eyebrow') !== '') : ?>
            <p class="<?php echo $isCover ? 'rounded-full bg-navy-deep/85 px-3.5 py-1.5 ring-1 ring-white/10 ' : ''; ?>inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow">
                <span aria-hidden="true" class="inline-block size-1.5 rounded-full bg-yellow"></span>
                <?php echo $text('eyebrow'); // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?>
            </p>
        <?php endif; ?>

        <h1 class="mt-5 max-w-[15ch] [font-family:var(--font-figtree)] font-black uppercase <?php echo $isCover ? 'leading-[0.92]' : 'leading-[0.94]'; ?> tracking-[-0.04em] text-balance" style="font-size: <?php echo $isCover ? 'clamp(2.5rem, 6vw, 5rem)' : 'clamp(2.5rem, 5.5vw, 4.5rem)'; ?>;">
            <?php echo $text('title'); // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?>
            <?php if ($text('titleAccent') !== '') : ?>
                <em class="font-serif font-normal italic normal-case tracking-[-0.01em] text-yellow"><?php echo $text('titleAccent'); // phpcs:ignore WordPress.Security.EscapeOutput ?></em>
            <?php endif; ?>
        </h1>

        <?php if ($intro !== '') : ?>
            <p class="<?php echo $isCover ? 'mt-5 max-w-[54ch] text-white/85' : 'mt-6 max-w-[46ch] text-white/75'; ?> [font-family:var(--font-figtree)] text-pretty text-base lg:text-lg">
                <?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?>
            </p>
        <?php endif; ?>

        <?php if ($text('primaryText') !== '' || $text('secondaryText') !== '' || $text('badge') !== '') : ?>
            <div class="<?php echo $isCover ? 'mt-7' : 'mt-8'; ?> flex flex-wrap items-center gap-x-4 gap-y-3">
                <?php if ($text('primaryText') !== '') : ?>
                    <a href="<?php echo esc_url((string) ($attributes['primaryUrl'] ?? '')); ?>" class="group inline-flex items-center gap-3 bg-yellow px-6 py-3 [font-family:var(--font-figtree)] text-sm font-bold uppercase tracking-[0.14em] text-navy-deep transition-colors duration-300 hover:bg-yellow-soft focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-yellow">
                        <?php echo $text('primaryText'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                        <span aria-hidden="true" class="inline-block transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1.5">→</span>
                    </a>
                <?php endif; ?>
                <?php if ($text('secondaryText') !== '') : ?>
                    <a href="<?php echo esc_url((string) ($attributes['secondaryUrl'] ?? '')); ?>" class="inline-flex min-h-[44px] items-center px-2 [font-family:var(--font-figtree)] text-sm font-bold uppercase tracking-[0.14em] text-white underline decoration-yellow decoration-[3px] underline-offset-[7px] transition-colors hover:decoration-white/60 focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-yellow">
                        <?php echo $text('secondaryText'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    </a>
                <?php endif; ?>
                <?php if ($text('badge') !== '') : ?>
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 [font-family:var(--font-figtree)] text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-4 text-yellow"><path fill-rule="evenodd" d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z" clip-rule="evenodd" /></svg>
                        <?php echo $text('badge'); // phpcs:ignore WordPress.Security.EscapeOutput ?>
                    </span>
                <?php endif; ?>
            </div>
        <?php endif; ?>

<?php if ($isCover) : ?>
    </div>
<?php else : ?>
        </div>
        <div class="lg:col-span-6">
            <?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above. ?>
        </div>
    </div>
<?php endif; ?>
</section>
