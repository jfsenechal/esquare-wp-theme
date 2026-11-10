<?php

/**
 * Front-end markup of the esquare/cartes block. Class lists mirror edit.js.
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

// Shared with edit.js.
$icons = json_decode((string) file_get_contents(get_theme_file_path('assets/icons/cartes.json')), true) ?: [];

$eyebrow = $plain($attributes['eyebrow'] ?? '');
$title   = $plain($attributes['title'] ?? '');
$intro   = $inline($attributes['intro'] ?? '');
$isPhoto = ($attributes['cardStyle'] ?? 'icon') === 'photo';
$grids   = [
    3 => 'grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3',
    4 => 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4',
    6 => 'grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6',
];
$grid = $grids[(int) ($attributes['columns'] ?? 4)] ?? $grids[4];
$tags = [
    'dark'   => 'mt-5 inline-flex w-fit items-center gap-1.5 rounded-full bg-navy-deep px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-yellow',
    'yellow' => 'mt-5 inline-flex w-fit items-center gap-1.5 rounded-full bg-[color-mix(in_oklch,var(--color-yellow)_70%,var(--color-stone-warm))] px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-navy-deep',
];

$cards = array_values(array_filter(
    array_map(
        static fn (mixed $c): array => [
            'icon'     => (string) ($c['icon'] ?? ''),
            'tag'      => $plain($c['tag'] ?? ''),
            'tagStyle' => (string) ($c['tagStyle'] ?? 'dark'),
            'title'    => $plain($c['title'] ?? ''),
            'text'     => $inline($c['text'] ?? ''),
            'imageId'  => (int) ($c['imageId'] ?? 0),
            'imageUrl' => (string) ($c['imageUrl'] ?? ''),
            'imageAlt' => trim((string) ($c['imageAlt'] ?? '')),
        ],
        (array) ($attributes['cards'] ?? [])
    ),
    static fn (array $c): bool => $c['title'] !== '' || $c['text'] !== ''
));
// Cards without a tag keep their title aligned with the tagged ones.
$hasTags = ! $isPhoto && array_filter($cards, static fn (array $c): bool => $c['tag'] !== '') !== [];

$photo = static function (array $card): string {
    $class = 'size-full object-cover transition-transform duration-700 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:scale-[1.04]';
    if ($card['imageId'] > 0) {
        $html = wp_get_attachment_image($card['imageId'], 'large', false, [
            'class'    => $class,
            'alt'      => $card['imageAlt'],
            'loading'  => 'lazy',
            'decoding' => 'async',
            'sizes'    => '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw',
        ]);
        if ($html !== '') {
            return $html;
        }
    }

    return $card['imageUrl'] === '' ? '' : sprintf(
        '<img src="%s" alt="%s" loading="lazy" decoding="async" class="%s" />',
        esc_url($card['imageUrl']),
        esc_attr($card['imageAlt']),
        esc_attr($class)
    );
};

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('cartes')) . '-title';
$wrapper = get_block_wrapper_attributes([
    'class'           => 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
    'aria-labelledby' => $titleId,
]);
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <div class="mx-auto max-w-7xl">
        <div class="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
            <div>
                <?php if ($eyebrow !== '') : ?>
                    <p class="inline-flex items-center gap-2 [font-family:var(--font-figtree)] text-xs font-bold text-navy/70 uppercase tracking-widest">
                        <span aria-hidden="true" class="inline-block size-2 rounded-full bg-yellow opacity-[0.675]"></span>
                        <?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?>
                    </p>
                <?php endif; ?>
                <h2 id="<?php echo esc_attr($titleId); ?>" class="mt-3 max-w-[18ch] [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
            </div>
            <?php if ($intro !== '') : ?>
                <p class="max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-base text-navy/70 lg:text-lg"><?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
            <?php endif; ?>
        </div>

        <?php if ($cards !== []) : ?>
            <ul class="mt-14 <?php echo esc_attr($grid); ?>" role="list">
                <?php foreach ($cards as $card) : ?>
                    <?php if ($isPhoto) : ?>
                        <li class="group flex flex-col overflow-hidden rounded-2xl bg-stone-warm ring-1 ring-navy/8 transition-shadow duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] hover:shadow-[0_18px_40px_-16px_color-mix(in_oklch,var(--color-navy-deep)_22%,transparent)]">
                            <div class="aspect-[4/3] overflow-hidden">
                                <?php echo $photo($card); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above. ?>
                            </div>
                            <div class="flex flex-1 flex-col p-6">
                                <h3 class="[font-family:var(--font-figtree)] text-xl font-bold tracking-tight text-navy"><?php echo $card['title']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                                <?php if ($card['text'] !== '') : ?>
                                    <p class="mt-2 [font-family:var(--font-figtree)] text-pretty text-base text-navy/70"><?php echo $card['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php else :
                        $icon = $icons[$card['icon']] ?? ($icons['ordinateur'] ?? ['d' => '']);
                        ?>
                        <li class="flex flex-col rounded-2xl bg-stone-warm p-6 ring-1 ring-navy/8 transition-shadow duration-200 ease-[cubic-bezier(0.22,1,0.36,1)] hover:shadow-[0_10px_30px_-12px_color-mix(in_oklch,var(--color-navy-deep)_18%,transparent)]">
                            <span aria-hidden="true" class="flex size-11 items-center justify-center rounded-xl bg-yellow/25 text-navy">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" class="size-6"><path stroke-linecap="round" stroke-linejoin="round" d="<?php echo esc_attr($icon['d']); ?>" /></svg>
                            </span>
                            <?php if ($card['tag'] !== '') : ?>
                                <p class="<?php echo esc_attr($tags[$card['tagStyle']] ?? $tags['dark']); ?>"><?php echo $card['tag']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                            <?php endif; ?>
                            <h3 class="<?php echo $card['tag'] !== '' ? 'mt-3' : ($hasTags ? 'mt-[3.25rem]' : 'mt-5'); ?> [font-family:var(--font-figtree)] text-lg font-bold text-navy"><?php echo $card['title']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                            <?php if ($card['text'] !== '') : ?>
                                <p class="mt-2 [font-family:var(--font-figtree)] text-pretty text-base text-navy/70"><?php echo $card['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                            <?php endif; ?>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
