<?php

/**
 * Front-end markup of the esquare/texte-image block. Class lists mirror edit.js.
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
$italic  = '[&_em]:font-serif [&_em]:font-normal [&_em]:italic [&_em]:normal-case [&_em]:tracking-[-0.01em]';
$themes  = [
    'light' => [
        'section'     => 'bg-cream px-6 py-20 text-navy lg:px-8 lg:py-24',
        'eyebrow'     => "inline-flex items-center gap-2 $figtree text-xs font-bold text-navy/70 uppercase tracking-widest",
        'dot'         => 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        'title'       => "mt-3 max-w-[17ch] $figtree text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl $italic",
        'lead'        => "mt-5 max-w-[54ch] $figtree text-pretty text-base text-navy/75 lg:text-lg [&_strong]:font-semibold [&_strong]:text-navy",
        'text'        => "mt-4 max-w-[54ch] $figtree text-pretty text-base text-navy/75 [&_strong]:font-semibold [&_strong]:text-navy",
        'image'       => 'shadow-[0_18px_40px_-16px_color-mix(in_oklch,var(--color-navy-deep)_22%,transparent)] ring-1 ring-navy/10',
        'factRow'     => 'border-navy/12',
        'factLabel'   => 'text-navy/70',
        'factValue'   => 'text-navy',
        'factNote'    => 'text-navy/70',
        'button'      => 'bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-navy-deep',
        'columns'     => 'divide-navy/12 border-navy/12',
        'columnLabel' => 'text-navy/70',
        'columnTitle' => 'text-navy',
        'columnText'  => 'text-navy/75',
        'columnLink'  => 'text-navy hover:decoration-navy/40',
        'checkTitle'  => 'text-navy/70',
        'checkText'   => 'text-navy',
    ],
    'dark'  => [
        'section'     => 'bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        'eyebrow'     => 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow',
        'dot'         => 'inline-block size-1.5 rounded-full bg-yellow',
        'title'       => "mt-4 max-w-[16ch] $figtree text-3xl font-black uppercase leading-[0.95] tracking-[-0.03em] text-balance md:text-4xl lg:text-5xl $italic [&_em]:text-yellow",
        'lead'        => "mt-5 max-w-[46ch] $figtree text-pretty text-lg text-white/75 [&_strong]:font-semibold [&_strong]:text-white",
        'text'        => "mt-4 max-w-[46ch] $figtree text-pretty text-base text-white/75 [&_strong]:font-semibold [&_strong]:text-white",
        'image'       => 'shadow-[0_30px_60px_-20px_color-mix(in_oklch,var(--color-navy-deep)_70%,transparent)] ring-1 ring-white/10',
        'factRow'     => 'border-white/15',
        'factLabel'   => 'text-yellow',
        'factValue'   => 'text-white',
        'factNote'    => 'text-white/70',
        'button'      => 'bg-yellow text-navy-deep hover:bg-yellow-soft focus-visible:outline-yellow',
        'columns'     => 'divide-white/15 border-white/15',
        'columnLabel' => 'text-yellow',
        'columnTitle' => 'text-white',
        'columnText'  => 'text-white/75',
        'columnLink'  => 'text-white hover:decoration-white/40',
        'checkTitle'  => 'text-white/65',
        'checkText'   => 'text-white',
    ],
];
$ratios     = ['16/10' => 'aspect-[16/10]', '4/3' => 'aspect-[4/3]', '4/5' => 'aspect-[4/5]'];
$columnGrid = [1 => 'lg:grid-cols-1', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4'];
$factLink   = '[&_a]:font-semibold [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4';
$arrowClass = 'inline-block transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1.5';

$t        = $themes[(string) ($attributes['theme'] ?? 'light')] ?? $themes['light'];
$imgLeft  = ($attributes['imagePosition'] ?? 'right') === 'left';
$ratio    = $ratios[(string) ($attributes['imageRatio'] ?? '4/3')] ?? $ratios['4/3'];
$eyebrow  = $plain($attributes['eyebrow'] ?? '');
$title    = trim(wp_kses((string) ($attributes['title'] ?? ''), ['em' => []]));
$lead     = $inline($attributes['lead'] ?? '');
$text     = $inline($attributes['text'] ?? '');
$btnText  = $plain($attributes['buttonText'] ?? '');
$btnUrl   = trim((string) ($attributes['buttonUrl'] ?? ''));
$facts    = array_values(array_filter(
    array_map(
        static fn (mixed $f): array => [
            'label' => $plain($f['label'] ?? ''),
            'value' => trim(wp_kses((string) ($f['value'] ?? ''), ['strong' => [], 'a' => ['href' => true, 'target' => true, 'rel' => true]])),
            'note'  => $plain($f['note'] ?? ''),
        ],
        (array) ($attributes['facts'] ?? [])
    ),
    static fn (array $f): bool => $f['label'] !== '' || $f['value'] !== ''
));
$columns = array_values(array_filter(
    array_map(
        static fn (mixed $c): array => [
            'label'    => $plain($c['label'] ?? ''),
            'title'    => $plain($c['title'] ?? ''),
            'text'     => trim(wp_kses((string) ($c['text'] ?? ''), ['strong' => [], 'em' => []])),
            'linkText' => $plain($c['linkText'] ?? ''),
            'linkUrl'  => trim((string) ($c['linkUrl'] ?? '')),
        ],
        (array) ($attributes['columns'] ?? [])
    ),
    static fn (array $c): bool => $c['title'] !== '' || $c['text'] !== ''
));
$hasFacts = $facts !== [];
$checklistTitle = $plain($attributes['checklistTitle'] ?? '');
$checklist      = array_values(array_filter(
    array_map(static fn (mixed $c): string => trim(wp_kses((string) $c, ['strong' => []])), (array) ($attributes['checklist'] ?? [])),
    static fn (string $c): bool => $c !== ''
));

$imageAlt = trim((string) ($attributes['imageAlt'] ?? ''));
$imageCls = "$ratio w-full rounded-2xl object-cover {$t['image']}";
$imageId  = (int) ($attributes['imageId'] ?? 0);
$image    = $imageId > 0 ? wp_get_attachment_image($imageId, 'large', false, [
    'class'    => $imageCls,
    'alt'      => $imageAlt,
    'loading'  => 'lazy',
    'decoding' => 'async',
    'sizes'    => '(min-width: 1280px) 600px, (min-width: 1024px) 50vw, 100vw',
]) : '';
$imageUrl = (string) ($attributes['imageUrl'] ?? '');
if ($image === '' && $imageUrl !== '') {
    $image = sprintf(
        '<img src="%s" alt="%s" loading="lazy" decoding="async" class="%s" />',
        esc_url($imageUrl),
        esc_attr($imageAlt),
        esc_attr($imageCls)
    );
}

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('texte-image')) . '-title';
$wrapper = get_block_wrapper_attributes([
    'class'           => $t['section'],
    'aria-labelledby' => $titleId,
]);
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <div class="mx-auto max-w-7xl">
        <div class="grid grid-cols-1 gap-x-14 gap-y-10 lg:grid-cols-2 <?php echo $hasFacts ? 'items-start' : 'items-center'; ?>">
            <?php if ($image !== '') : ?>
                <div class="order-2<?php echo $imgLeft ? ' lg:order-1' : ''; ?><?php echo $hasFacts ? ' lg:sticky lg:top-24' : ''; ?>">
                    <?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput -- built from escaped parts above. ?>
                </div>
            <?php endif; ?>
            <div class="order-1<?php echo $imgLeft ? ' lg:order-2' : ''; ?>">
                <?php if ($eyebrow !== '') : ?>
                    <p class="<?php echo esc_attr($t['eyebrow']); ?>">
                        <span aria-hidden="true" class="<?php echo esc_attr($t['dot']); ?>"></span>
                        <?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?>
                    </p>
                <?php endif; ?>
                <h2 id="<?php echo esc_attr($titleId); ?>" class="<?php echo esc_attr($t['title']); ?>"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() allowing <em>. ?></h2>
                <?php if ($lead !== '') : ?>
                    <p class="<?php echo esc_attr($t['lead']); ?>"><?php echo $lead; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
                <?php endif; ?>
                <?php if ($text !== '') : ?>
                    <p class="<?php echo esc_attr($t['text']); ?>"><?php echo $text; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
                <?php endif; ?>
                <?php if ($hasFacts) : ?>
                    <dl class="mt-10 <?php echo esc_attr($figtree); ?>">
                        <?php foreach ($facts as $index => $fact) : ?>
                            <div class="grid grid-cols-1 gap-x-8 gap-y-1 border-t py-5 sm:grid-cols-[9rem_minmax(0,1fr)] <?php echo esc_attr($t['factRow']); ?><?php echo $index === count($facts) - 1 ? ' border-b' : ''; ?>">
                                <dt class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] <?php echo esc_attr($t['factLabel']); ?>"><?php echo $fact['label']; // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
                                <dd class="<?php echo esc_attr($t['factValue'] . ' ' . $factLink); ?>"><?php echo $fact['value']; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ($fact['note'] !== '') : ?> <span class="<?php echo esc_attr($t['factNote']); ?>"><?php echo $fact['note']; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
                <?php if ($btnText !== '' && $btnUrl !== '') :
                    $external = $isExternal($btnUrl);
                    ?>
                    <div class="mt-8">
                        <a href="<?php echo esc_url($btnUrl); ?>"<?php echo $external ? ' target="_blank" rel="noopener"' : ''; ?> class="group inline-flex items-center gap-3 px-6 py-3 <?php echo esc_attr($figtree); ?> text-sm font-bold uppercase tracking-[0.14em] transition-colors duration-300 focus-visible:outline-2 focus-visible:outline-offset-4 <?php echo esc_attr($t['button']); ?>">
                            <?php echo $btnText; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                            <span aria-hidden="true" class="<?php echo esc_attr($arrowClass); ?>"><?php echo $external ? '↗' : '→'; ?></span>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($checklist !== []) : ?>
            <div class="mt-16 lg:mt-20">
                <?php if ($checklistTitle !== '') : ?>
                    <h3 class="font-mono text-[11px] font-semibold uppercase tracking-[0.25em] <?php echo esc_attr($t['checkTitle']); ?>"><?php echo $checklistTitle; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                <?php endif; ?>
                <ul role="list" class="mt-6 border-t <?php echo esc_attr($t['factRow']); ?>">
                    <?php foreach ($checklist as $item) : ?>
                        <li class="flex items-start gap-5 border-b py-6 <?php echo esc_attr($t['factRow']); ?>">
                            <span aria-hidden="true" class="mt-1.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-yellow">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-navy-deep"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 0 1 .143 1.052l-8 10.5a.75.75 0 0 1-1.127.075l-4.5-4.5a.75.75 0 0 1 1.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 0 1 1.05-.143Z" clip-rule="evenodd" /></svg>
                            </span>
                            <p class="<?php echo esc_attr("max-w-[62ch] $figtree text-pretty text-lg lg:text-xl [&_strong]:font-semibold {$t['checkText']}"); ?>"><?php echo $item; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() allowing <strong>. ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($columns !== []) : ?>
            <div class="mt-16 grid grid-cols-1 divide-y border-t lg:mt-20 lg:divide-x lg:divide-y-0 <?php echo esc_attr(($columnGrid[count($columns)] ?? $columnGrid[3]) . ' ' . $t['columns']); ?>">
                <?php foreach ($columns as $column) : ?>
                    <div class="border-inherit py-8 last:border-b lg:px-10 lg:py-9 lg:first:pl-0 lg:last:border-b-0 lg:last:pr-0">
                        <?php if ($column['label'] !== '') : ?>
                            <p class="font-mono text-[11px] font-semibold uppercase tracking-[0.18em] <?php echo esc_attr($t['columnLabel']); ?>"><?php echo $column['label']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                        <?php endif; ?>
                        <h3 class="mt-3 <?php echo esc_attr($figtree); ?> text-2xl font-bold tracking-tight <?php echo esc_attr($t['columnTitle']); ?>"><?php echo $column['title']; // phpcs:ignore WordPress.Security.EscapeOutput ?></h3>
                        <?php if ($column['text'] !== '') : ?>
                            <p class="mt-3 max-w-[42ch] <?php echo esc_attr($figtree); ?> text-pretty <?php echo esc_attr($t['columnText']); ?>"><?php echo $column['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?></p>
                        <?php endif; ?>
                        <?php if ($column['linkText'] !== '' && $column['linkUrl'] !== '') :
                            $external = $isExternal($column['linkUrl']);
                            ?>
                            <a href="<?php echo esc_url($column['linkUrl']); ?>"<?php echo $external ? ' target="_blank" rel="noopener"' : ''; ?> class="group mt-5 inline-flex items-center gap-2 <?php echo esc_attr($figtree); ?> text-sm font-bold uppercase tracking-[0.14em] underline decoration-yellow decoration-2 underline-offset-[6px] transition-colors <?php echo esc_attr($t['columnLink']); ?>">
                                <?php echo $column['linkText']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                                <span aria-hidden="true" class="inline-block transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1"><?php echo $external ? '↗' : '→'; ?></span>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
