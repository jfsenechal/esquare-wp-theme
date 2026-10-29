<?php

/**
 * Front-end markup of the esquare/bande-photo block. Class lists mirror edit.js.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$layouts = [
    2 => ['grid' => 'grid-cols-1 sm:grid-cols-2', 'first' => 'aspect-[16/10] sm:aspect-[4/3]', 'other' => 'aspect-[16/10] sm:aspect-[4/3]', 'sizes' => '(min-width: 640px) 50vw, 100vw'],
    3 => ['grid' => 'grid-cols-2 sm:grid-cols-3', 'first' => 'col-span-2 aspect-[16/10] sm:col-span-1 sm:aspect-[4/3]', 'other' => 'aspect-square sm:aspect-[4/3]', 'sizes' => '(min-width: 640px) 33vw, 50vw'],
    4 => ['grid' => 'grid-cols-2 sm:grid-cols-4', 'first' => 'aspect-square sm:aspect-[4/3]', 'other' => 'aspect-square sm:aspect-[4/3]', 'sizes' => '(min-width: 640px) 25vw, 50vw'],
];

$images = array_values(array_filter(
    array_map(
        static fn (mixed $i): array => [
            'id'    => (int) ($i['id'] ?? 0),
            'url'   => (string) ($i['url'] ?? ''),
            'alt'   => trim((string) ($i['alt'] ?? '')),
            'focal' => (array) ($i['focalPoint'] ?? []),
        ],
        (array) ($attributes['images'] ?? [])
    ),
    static fn (array $i): bool => $i['id'] > 0 || $i['url'] !== ''
));
if ($images === []) {
    return;
}
$images = array_slice($images, 0, 4);
$layout = $layouts[count($images)] ?? $layouts[3];
$label  = trim((string) ($attributes['label'] ?? ''));

$wrapper = get_block_wrapper_attributes(array_filter([
    'class'      => 'bg-stone-warm',
    'aria-label' => $label,
]));
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <div class="grid <?php echo esc_attr($layout['grid']); ?>">
        <?php foreach ($images as $index => $image) :
            $class    = ($index === 0 ? $layout['first'] : $layout['other']) . ' w-full object-cover';
            $position = sprintf(
                'object-position: %s%% %s%%',
                round(100 * min(1, max(0, (float) ($image['focal']['x'] ?? 0.5))), 2),
                round(100 * min(1, max(0, (float) ($image['focal']['y'] ?? 0.5))), 2)
            );
            $html = $image['id'] > 0 ? wp_get_attachment_image($image['id'], 'large', false, [
                'class'    => $class,
                'alt'      => $image['alt'],
                'style'    => $position,
                'loading'  => 'lazy',
                'decoding' => 'async',
                'sizes'    => $layout['sizes'],
            ]) : '';
            if ($html === '') {
                $html = sprintf(
                    '<img src="%s" alt="%s" style="%s" loading="lazy" decoding="async" class="%s" />',
                    esc_url($image['url']),
                    esc_attr($image['alt']),
                    esc_attr($position),
                    esc_attr($class)
                );
            }
            echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above.
        endforeach; ?>
    </div>
</section>
