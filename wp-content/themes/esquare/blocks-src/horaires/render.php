<?php

/**
 * Front-end markup of the esquare/horaires block. Class lists mirror edit.js.
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

$eyebrow = $plain($attributes['eyebrow'] ?? '');
// Italic words in the title carry the serif accent (styled through [&_em]).
$title = trim(wp_kses((string) ($attributes['title'] ?? ''), ['em' => []]));
$intro = $inline($attributes['intro'] ?? '');
$note  = $inline($attributes['note'] ?? '');

$slots = array_values(array_filter(
    array_map(
        static fn (mixed $s): array => [
            'label' => $plain($s['label'] ?? ''),
            'from'  => $plain($s['from'] ?? ''),
            'to'    => $plain($s['to'] ?? ''),
        ],
        (array) ($attributes['slots'] ?? [])
    ),
    static fn (array $s): bool => $s['label'] !== '' || $s['from'] !== ''
));

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('horaires')) . '-title';
$wrapper = get_block_wrapper_attributes([
    'class'           => 'px-6 py-20 lg:px-8 lg:py-24',
    'aria-labelledby' => $titleId,
]);
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-yellow ring-1 ring-navy/10">
        <div class="relative isolate grid grid-cols-1 gap-y-8 px-8 py-12 lg:grid-cols-[1.1fr_1.4fr] lg:items-center lg:gap-x-12 lg:px-12 lg:py-14">
            <div aria-hidden="true" class="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
            <div>
                <?php if ($eyebrow !== '') : ?>
                    <p class="inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy-deep">
                        <span aria-hidden="true" class="inline-block size-1.5 rounded-full bg-navy-deep"></span>
                        <?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?>
                    </p>
                <?php endif; ?>
                <h2 id="<?php echo esc_attr($titleId); ?>" class="mt-4 [font-family:var(--font-figtree)] text-3xl font-black uppercase leading-[1] tracking-[-0.03em] text-balance text-navy-deep md:text-4xl [&_em]:font-serif [&_em]:font-normal [&_em]:normal-case [&_em]:tracking-[-0.01em]"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></h2>
                <?php if ($intro !== '') : ?>
                    <p class="mt-4 max-w-[40ch] [font-family:var(--font-figtree)] text-pretty text-navy-deep/80"><?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
                <?php endif; ?>
            </div>
            <div>
                <?php if ($slots !== []) : ?>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <?php foreach ($slots as $slot) : ?>
                            <div class="rounded-2xl bg-white/55 p-6 ring-1 ring-navy-deep/10">
                                <dt class="font-mono text-[11px] font-semibold uppercase tracking-[0.2em] text-navy-deep/70"><?php echo $slot['label']; // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
                                <dd class="mt-2 [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-navy-deep tabular-nums"><?php echo $slot['from']; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ($slot['to'] !== '') : ?> <span class="text-navy-deep/55">–</span> <?php echo $slot['to']; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php endif; ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
                <?php if ($note !== '') : ?>
                    <p class="mt-4 [font-family:var(--font-figtree)] text-sm font-semibold text-navy-deep/70"><?php echo $note; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
