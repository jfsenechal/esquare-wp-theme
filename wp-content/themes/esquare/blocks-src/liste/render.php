<?php

/**
 * Front-end markup of the esquare/liste block. Class lists mirror edit.js.
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
$isExternal = static fn (string $url): bool => (bool) preg_match('#^https?://#i', $url)
    && wp_parse_url($url, PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST);

$figtree = '[font-family:var(--font-figtree)]';
$link    = 'font-semibold underline decoration-yellow decoration-2 underline-offset-4 transition-colors';
$themes  = [
    'light' => [
        'section'      => 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
        'eyebrow'      => "inline-flex items-center gap-2 $figtree text-xs font-bold text-navy/70 uppercase tracking-widest",
        'dot'          => 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        'title'        => "mt-3 max-w-[16ch] $figtree text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl",
        'intro'        => "max-w-[54ch] $figtree text-pretty text-base text-navy/75 lg:text-lg",
        'rule'         => 'border-navy/12',
        'itemTitle'    => 'text-navy',
        'itemText'     => 'text-navy/75',
        'link'         => "$link text-navy hover:decoration-navy/40",
        'bulletsTitle' => 'text-navy/70',
        'bullets'      => 'text-navy',
        'outro'        => 'text-navy/75 [&_a]:font-semibold [&_a]:text-navy [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4',
    ],
    'dark'  => [
        'section'      => 'relative isolate overflow-hidden bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        'eyebrow'      => 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow',
        'dot'          => 'inline-block size-1.5 rounded-full bg-yellow',
        'title'        => "mt-4 max-w-[16ch] $figtree text-3xl font-bold tracking-tight text-balance md:text-4xl lg:text-5xl",
        'intro'        => "max-w-[54ch] $figtree text-pretty text-base text-white/70 lg:text-lg",
        'rule'         => 'border-white/15',
        'itemTitle'    => 'text-white',
        'itemText'     => 'text-white/70',
        'link'         => "$link text-white hover:decoration-white/50",
        'bulletsTitle' => 'text-white/65',
        'bullets'      => 'text-white',
        'outro'        => 'text-white/70 [&_a]:font-semibold [&_a]:text-white [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4',
    ],
];

$theme   = (string) ($attributes['theme'] ?? 'dark');
$t       = $themes[$theme] ?? $themes['dark'];
$isGrid  = ($attributes['layout'] ?? 'rows') === 'grid';
$eyebrow = $plain($attributes['eyebrow'] ?? '');
$title   = $plain($attributes['title'] ?? '');
$intro   = $inline($attributes['intro'] ?? '');
$outro   = $inline($attributes['outro'] ?? '');
$bulletsTitle = $plain($attributes['bulletsTitle'] ?? '');
$bullets = array_values(array_filter(array_map($plain, (array) ($attributes['bullets'] ?? [])), static fn (string $b): bool => $b !== ''));
$items   = array_values(array_filter(
    array_map(
        static fn (mixed $i): array => [
            'title'    => $plain($i['title'] ?? ''),
            'text'     => trim(wp_kses((string) ($i['text'] ?? ''), ['strong' => [], 'em' => []])),
            'linkText' => $plain($i['linkText'] ?? ''),
            'linkUrl'  => trim((string) ($i['linkUrl'] ?? '')),
        ],
        (array) ($attributes['items'] ?? [])
    ),
    static fn (array $i): bool => $i['title'] !== '' || $i['text'] !== ''
));

$itemLink = static function (array $item) use ($t, $isExternal): string {
    if ($item['linkText'] === '' || $item['linkUrl'] === '') {
        return '';
    }
    $external = $isExternal($item['linkUrl']);
    return sprintf(
        '<a href="%s"%s class="%s">%s%s</a>',
        esc_url($item['linkUrl']),
        $external ? ' target="_blank" rel="noopener"' : '',
        esc_attr($t['link']),
        $item['linkText'],
        $external ? ' <span aria-hidden="true">↗</span>' : ''
    );
};

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('liste')) . '-title';
$wrapper = get_block_wrapper_attributes([
    'class'           => $t['section'],
    'aria-labelledby' => $titleId,
]);
$itemTitleClass = "$figtree text-xl font-bold tracking-tight lg:text-2xl {$t['itemTitle']}";
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <?php if ($theme === 'dark') : ?>
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

        <?php if ($items !== [] && $isGrid) : ?>
            <ul role="list" class="mt-14 grid grid-cols-1 gap-x-10 gap-y-9 sm:grid-cols-2">
                <?php foreach ($items as $item) :
                    $a = $itemLink($item);
                    ?>
                    <li class="border-t pt-6 <?php echo esc_attr($t['rule']); ?>">
                        <h3 class="<?php echo esc_attr($itemTitleClass); ?>"><?php echo $item['title']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                        <p class="<?php echo esc_attr("mt-3 max-w-[46ch] $figtree text-pretty {$t['itemText']}"); ?>"><?php echo $item['text'] . ($a !== '' ? ' ' . $a : ''); // phpcs:ignore WordPress.Security.EscapeOutput -- filtered above. ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php elseif ($items !== []) : ?>
            <ul role="list" class="mt-14">
                <?php foreach ($items as $index => $item) :
                    $a = $itemLink($item);
                    ?>
                    <li class="grid grid-cols-1 items-baseline gap-x-8 gap-y-2 border-t py-6 lg:grid-cols-[15rem_minmax(0,1fr)_auto] lg:py-7 <?php echo esc_attr($t['rule']); ?><?php echo $index === count($items) - 1 ? ' border-b' : ''; ?>">
                        <h3 class="<?php echo esc_attr($itemTitleClass); ?>"><?php echo $item['title']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                        <p class="<?php echo esc_attr("$figtree text-base {$t['itemText']}"); ?>"><?php echo $item['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                        <?php if ($a !== '') : ?>
                            <p class="<?php echo esc_attr("$figtree text-base lg:text-right"); ?>"><?php echo $a; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts. ?></p>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($bullets !== []) : ?>
            <div class="mt-16 grid grid-cols-1 gap-x-14 gap-y-6 border-t pt-10 lg:grid-cols-[16rem_minmax(0,1fr)] <?php echo esc_attr($t['rule']); ?>">
                <?php if ($bulletsTitle !== '') : ?>
                    <h3 class="font-mono text-[11px] font-semibold uppercase tracking-[0.25em] <?php echo esc_attr($t['bulletsTitle']); ?>"><?php echo $bulletsTitle; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                <?php endif; ?>
                <ul role="list" class="<?php echo esc_attr("grid grid-cols-1 gap-x-10 gap-y-4 $figtree text-lg font-semibold sm:grid-cols-2 lg:text-xl {$t['bullets']}"); ?>">
                    <?php foreach ($bullets as $bullet) : ?>
                        <li class="flex items-baseline gap-3"><span aria-hidden="true" class="size-1.5 shrink-0 rounded-full bg-yellow"></span><?php echo $bullet; // phpcs:ignore WordPress.Security.EscapeOutput ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($outro !== '') : ?>
            <p class="<?php echo esc_attr("mt-10 max-w-[62ch] $figtree text-base {$t['outro']}"); ?>"><?php echo $outro; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
        <?php endif; ?>
    </div>
</section>
