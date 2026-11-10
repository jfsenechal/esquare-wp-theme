<?php

/**
 * Front-end markup of the esquare/formulaire block. Class lists mirror edit.js.
 *
 * Renders a Fluent Forms form (by id) beside its title and text, either as a
 * split cream section or as a yellow poster panel.
 *
 * @var array<string, mixed> $attributes
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

$formId = (int) ($attributes['formId'] ?? 0);
$form   = $formId > 0 ? trim(do_shortcode(sprintf('[fluentform id="%d"]', $formId))) : '';
if ($form === '') {
    return;
}

$figtree = '[font-family:var(--font-figtree)]';
$italic  = '[&_em]:font-serif [&_em]:font-normal [&_em]:italic [&_em]:normal-case [&_em]:tracking-[-0.01em]';
$variants = [
    'split'  => [
        'section' => 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
        'inner'   => 'mx-auto grid max-w-7xl grid-cols-1 items-start gap-x-14 gap-y-10 lg:grid-cols-[1fr_1.2fr]',
        'eyebrow' => "inline-flex items-center gap-2 $figtree text-xs font-bold text-navy/70 uppercase tracking-widest",
        'dot'     => 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        'title'   => "mt-3 max-w-[15ch] $figtree text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl $italic",
        'text'    => "mt-5 max-w-[46ch] $figtree text-pretty text-base text-navy/75 lg:text-lg",
        'form'    => 'esq-form rounded-2xl bg-stone-warm p-6 ring-1 ring-navy/8 lg:p-8',
    ],
    'poster' => [
        'section' => 'px-6 py-20 lg:px-8 lg:py-24',
        'inner'   => 'relative isolate grid grid-cols-1 gap-y-8 px-8 py-12 lg:grid-cols-[1.1fr_1fr] lg:items-center lg:gap-x-12 lg:px-12 lg:py-14',
        'eyebrow' => 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy-deep',
        'dot'     => 'inline-block size-1.5 rounded-full bg-navy-deep',
        'title'   => "mt-4 max-w-[20ch] $figtree text-3xl font-black uppercase leading-[1] tracking-[-0.03em] text-balance text-navy-deep md:text-4xl $italic",
        'text'    => "mt-4 max-w-[40ch] $figtree text-pretty text-navy-deep/80",
        'form'    => 'esq-form esq-form-brass',
    ],
];
$variant  = (string) ($attributes['variant'] ?? 'split');
$isPoster = $variant === 'poster';
$v        = $variants[$variant] ?? $variants['split'];

$eyebrow = trim(wp_kses((string) ($attributes['eyebrow'] ?? ''), []));
$title   = trim(wp_kses((string) ($attributes['title'] ?? ''), ['em' => []]));
$text    = trim(wp_kses((string) ($attributes['text'] ?? ''), ['strong' => [], 'em' => []]));
$note    = $isPoster ? '' : trim(wp_kses((string) ($attributes['note'] ?? ''), [
    'strong' => [],
    'em'     => [],
    'a'      => ['href' => true, 'target' => true, 'rel' => true],
]));
// Phone and mail links in the note keep the theme's link style.
$noteClass = "mt-6 max-w-[46ch] $figtree text-base text-navy/75 [&_a]:font-semibold [&_a]:text-navy [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4 [&_a]:transition-colors [&_a:hover]:decoration-navy/40";

$anchor  = trim((string) ($attributes['anchor'] ?? ''));
$titleId = ($anchor !== '' ? $anchor : wp_unique_id('formulaire')) . '-title';
$wrapper = get_block_wrapper_attributes(array_filter([
    'class'           => $v['section'],
    'aria-labelledby' => $title !== '' ? $titleId : '',
]));
// phpcs:disable WordPress.Security.EscapeOutput -- values are passed through wp_kses()/esc_*() above; $form is Fluent Forms' own markup.
?>
<section <?php echo $wrapper; ?>>
    <?php if ($isPoster) : ?>
    <div class="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-yellow ring-1 ring-navy/10">
    <?php endif; ?>
        <div class="<?php echo esc_attr($v['inner']); ?>">
            <?php if ($isPoster) : ?>
                <div aria-hidden="true" class="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
            <?php endif; ?>
            <div>
                <?php if ($eyebrow !== '') : ?>
                    <p class="<?php echo esc_attr($v['eyebrow']); ?>">
                        <span aria-hidden="true" class="<?php echo esc_attr($v['dot']); ?>"></span>
                        <?php echo $eyebrow; ?>
                    </p>
                <?php endif; ?>
                <?php if ($title !== '') : ?>
                    <h2 id="<?php echo esc_attr($titleId); ?>" class="<?php echo esc_attr($v['title']); ?>"><?php echo $title; ?></h2>
                <?php endif; ?>
                <?php if ($text !== '') : ?>
                    <p class="<?php echo esc_attr($v['text']); ?>"><?php echo $text; ?></p>
                <?php endif; ?>
                <?php if ($note !== '') : ?>
                    <p class="<?php echo esc_attr($noteClass); ?>"><?php echo $note; ?></p>
                <?php endif; ?>
            </div>
            <div class="<?php echo esc_attr($v['form']); ?>">
                <?php echo $form; ?>
            </div>
        </div>
    <?php if ($isPoster) : ?>
    </div>
    <?php endif; ?>
</section>
<?php
// phpcs:enable
