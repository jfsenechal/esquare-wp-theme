<?php

/**
 * Front-end markup of the esquare/logos block. Class lists mirror edit.js.
 *
 * One logo is shown wide (a single image grouping every partner); several logos
 * are laid out on a wrapping row, each optionally linked.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$logos = array_values(array_filter(
    array_map(
        static fn (mixed $l): array => [
            'id'     => (int) ($l['id'] ?? 0),
            'url'    => (string) ($l['url'] ?? ''),
            'alt'    => trim((string) ($l['alt'] ?? '')),
            'width'  => (int) ($l['width'] ?? 0),
            'height' => (int) ($l['height'] ?? 0),
            'link'   => trim((string) ($l['link'] ?? '')),
        ],
        (array) ($attributes['logos'] ?? [])
    ),
    static fn (array $l): bool => $l['id'] > 0 || $l['url'] !== ''
));
if ($logos === []) {
    return;
}
$logos = array_slice($logos, 0, 12);

$single = count($logos) === 1;
$class  = $single ? 'mx-auto mt-6 h-auto w-full max-w-4xl' : 'h-12 w-auto max-w-40 object-contain lg:h-14';
$sizes  = $single ? '(min-width: 896px) 896px, 100vw' : '160px';

$image = static function (array $logo) use ($class, $sizes, $single): string {
    $html = $logo['id'] > 0 ? wp_get_attachment_image($logo['id'], $single ? 'full' : 'medium', false, [
        'class'    => $class,
        'alt'      => $logo['alt'],
        'loading'  => 'lazy',
        'decoding' => 'async',
        'sizes'    => $sizes,
    ]) : '';
    if ($html === '') {
        $html = sprintf(
            '<img src="%s" alt="%s"%s loading="lazy" decoding="async" class="%s" />',
            esc_url($logo['url']),
            esc_attr($logo['alt']),
            $logo['width'] > 0 && $logo['height'] > 0
                ? sprintf(' width="%d" height="%d"', $logo['width'], $logo['height'])
                : '',
            esc_attr($class)
        );
    }
    if ($logo['link'] === '') {
        return $html;
    }
    $external = ! str_starts_with($logo['link'], '/') && ! str_starts_with($logo['link'], '#')
        && wp_parse_url($logo['link'], PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST);

    return sprintf(
        '<a href="%s"%s class="%s">%s</a>',
        esc_url($logo['link']),
        $external ? ' target="_blank" rel="noopener"' : '',
        $single ? 'block rounded-lg focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-navy-deep' : 'block rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-navy-deep',
        $html
    );
};

$title   = trim(wp_kses((string) ($attributes['title'] ?? ''), []));
$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('logos')) . '-title';
$wrapper = get_block_wrapper_attributes(array_filter([
    'class'           => 'bg-stone-warm px-6 py-14 lg:px-8 lg:py-16',
    'aria-labelledby' => $title !== '' ? $titleId : '',
]));
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <div class="mx-auto max-w-7xl rounded-2xl bg-white px-6 py-8 ring-1 ring-navy/8 lg:px-10 lg:py-9">
        <?php if ($title !== '') : ?>
            <h2 id="<?php echo esc_attr($titleId); ?>" class="text-center font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy/70 lg:text-left"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no tags allowed. ?></h2>
        <?php endif; ?>
        <?php if ($single) : ?>
            <?php echo $image($logos[0]); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above. ?>
        <?php else : ?>
            <ul role="list" class="mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-6 lg:justify-start">
                <?php foreach ($logos as $logo) : ?>
                    <li><?php echo $image($logo); // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above. ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</section>
