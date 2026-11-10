<?php

/**
 * Front-end markup of the esquare/contact block. Class lists mirror edit.js.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

// RichText stores HTML: single-line fields keep their entities but no tags.
$plain = static fn (mixed $value): string => trim(wp_kses((string) $value, []));

$eyebrow = $plain($attributes['eyebrow'] ?? '');
$title   = $plain($attributes['title'] ?? '');
$intro   = trim(wp_kses((string) ($attributes['intro'] ?? ''), [
    'strong' => [],
    'em'     => [],
    'a'      => ['href' => true, 'target' => true, 'rel' => true],
]));

$buttons = array_values(array_filter(
    array_map(
        static fn (mixed $b): array => ['text' => $plain($b['text'] ?? ''), 'url' => trim((string) ($b['url'] ?? ''))],
        (array) ($attributes['buttons'] ?? [])
    ),
    static fn (array $b): bool => $b['text'] !== '' && $b['url'] !== ''
));
$cards = array_values(array_filter(
    array_map(
        static fn (mixed $c): array => [
            'label' => $plain($c['label'] ?? ''),
            'value' => $plain($c['value'] ?? ''),
            'note'  => $plain($c['note'] ?? ''),
        ],
        (array) ($attributes['cards'] ?? [])
    ),
    static fn (array $c): bool => $c['label'] !== '' || $c['value'] !== ''
));

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('contact')) . '-title';
$wrapper = get_block_wrapper_attributes([
    'class'           => 'bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
    'aria-labelledby' => $titleId,
]);

$facebookIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-4"><path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.52 1.49-3.91 3.78-3.91 1.1 0 2.24.2 2.24.2v2.47h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94Z" /></svg>';
?>
<section <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped by get_block_wrapper_attributes(). ?>>
    <div class="mx-auto max-w-7xl">
        <div class="grid items-start gap-10 lg:grid-cols-2">
            <div>
                <?php if ($eyebrow !== '') : ?>
                    <p class="font-mono text-xs tracking-[0.3em] text-yellow uppercase"><?php echo $eyebrow; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_kses() with no allowed tags. ?></p>
                <?php endif; ?>
                <h2 id="<?php echo esc_attr($titleId); ?>" class="mt-3 max-w-[18ch] [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance md:text-4xl lg:text-5xl"><?php echo $title; // phpcs:ignore WordPress.Security.EscapeOutput ?></h2>
                <?php if ($intro !== '') : ?>
                    <p class="mt-5 max-w-[46ch] [font-family:var(--font-figtree)] text-pretty text-lg text-white/70"><?php echo $intro; // phpcs:ignore WordPress.Security.EscapeOutput -- filtered by wp_kses() above. ?></p>
                <?php endif; ?>
                <?php if ($buttons !== []) : ?>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <?php foreach ($buttons as $index => $button) :
                            $isExternal = (bool) preg_match('#^https?://#i', $button['url'])
                                && wp_parse_url($button['url'], PHP_URL_HOST) !== wp_parse_url(home_url(), PHP_URL_HOST);
                            $isFacebook = (bool) preg_match('#^https?://([a-z0-9-]+\.)*facebook\.com/#i', $button['url']);
                            ?>
                            <a href="<?php echo esc_url($button['url']); ?>"<?php echo $isExternal ? ' target="_blank" rel="noopener"' : ''; ?> class="<?php echo $index === 0
                                ? 'inline-flex items-center gap-2 rounded-md bg-yellow px-5 py-2.5 [font-family:var(--font-figtree)] text-sm font-semibold text-navy transition-colors hover:bg-yellow-soft'
                                : 'inline-flex items-center gap-2 rounded-md bg-white/5 px-5 py-2.5 [font-family:var(--font-figtree)] text-sm font-semibold text-white ring-1 ring-white/15 transition-colors hover:bg-white/10'; ?>">
                                <?php echo $isFacebook ? $facebookIcon : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?>
                                <?php echo $button['text']; // phpcs:ignore WordPress.Security.EscapeOutput ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php if ($cards !== []) : ?>
                <dl class="grid grid-cols-1 gap-4 [font-family:var(--font-figtree)] text-sm sm:grid-cols-2">
                    <?php foreach ($cards as $index => $card) : ?>
                        <div class="rounded-lg bg-white/5 p-5 ring-1 ring-white/10<?php echo $index === 0 ? ' sm:col-span-2' : ''; ?>">
                            <dt class="font-mono text-xs tracking-widest text-yellow uppercase"><?php echo $card['label']; // phpcs:ignore WordPress.Security.EscapeOutput ?></dt>
                            <dd class="mt-2 font-semibold text-white<?php echo str_contains($card['value'], '@') ? ' break-all' : ''; ?>"><?php echo $card['value']; // phpcs:ignore WordPress.Security.EscapeOutput ?><?php if ($card['note'] !== '') : ?><span class="mt-1 block font-normal text-white/65"><?php echo $card['note']; // phpcs:ignore WordPress.Security.EscapeOutput ?></span><?php endif; ?></dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            <?php endif; ?>
        </div>
    </div>
</section>
