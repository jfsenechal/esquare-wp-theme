<?php

/**
 * Sheet of one FabLab machine.
 *
 * @var \WP_Post $machine  Provided by Esquare\Theme\Block\MachineDetail::render().
 * @var string   $body     The machine's content, already filtered by `the_content`.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Esquare\Theme\Machine\MachinePostType;

$price   = MachinePostType::price($machine);
$booking = MachinePostType::bookingUrl($machine);
$excerpt = trim($machine->post_excerpt);
$link_class = 'font-semibold text-navy underline decoration-navy/30 underline-offset-4 hover:decoration-navy';
?>
<article aria-labelledby="machine-titre" class="bg-cream px-6 pt-10 pb-20 lg:px-8 lg:pt-14 lg:pb-28">
    <div class="mx-auto max-w-5xl">

        <a href="<?php echo esc_url(home_url(MachinePostType::LIST_URL)); ?>" class="article-back"><span class="arrow" aria-hidden="true">←</span> Tout le parc de machines</a>

        <header class="reveal mt-8">
            <p class="article-eyebrow">FabLab · Machine</p>
            <h1 id="machine-titre" class="article-title mt-4"><?php echo esc_html(get_the_title($machine)); ?></h1>
            <?php if ($excerpt !== '') : ?>
                <div class="article-standfirst mt-6"><p><?php echo nl2br(esc_html($excerpt), false); ?></p></div>
            <?php endif; ?>
        </header>

        <div class="mt-10 grid gap-10 lg:mt-14 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-14">

            <aside aria-label="Informations pratiques" class="lg:col-start-2 lg:row-start-1">
                <div class="border-t-2 border-navy pt-5 lg:sticky lg:top-6">
                    <dl class="grid gap-4 [font-family:var(--font-figtree)] text-base">
                        <?php if ($price !== '') : ?>
                            <div>
                                <dt class="font-mono text-xs font-semibold uppercase tracking-[0.14em] text-navy/60">Tarif</dt>
                                <dd class="mt-1 text-pretty text-navy"><?php echo esc_html($price); ?></dd>
                            </div>
                        <?php endif; ?>
                        <div>
                            <dt class="font-mono text-xs font-semibold uppercase tracking-[0.14em] text-navy/60">Lieu</dt>
                            <dd class="mt-1 text-navy">FabLab de l'e-Square, Marche-en-Famenne</dd>
                        </div>
                    </dl>

                    <?php if ($booking !== '') : ?>
                        <a href="<?php echo esc_url($booking); ?>" class="mt-7 inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-full bg-navy px-6 [font-family:var(--font-figtree)] text-base font-semibold text-white transition-colors hover:bg-navy-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-deep">
                            Réserver cette machine<span aria-hidden="true">→</span>
                        </a>
                        <p class="mt-4 [font-family:var(--font-figtree)] text-base text-pretty text-navy/75">La réservation est réservée aux membres du FabLab. <a href="<?php echo esc_url(home_url('/fablab/#tarifs')); ?>" class="<?php echo esc_attr($link_class); ?>">Voir les tarifs</a>.</p>
                    <?php else : ?>
                        <p class="mt-7 [font-family:var(--font-figtree)] text-base text-pretty text-navy/75">Pour utiliser cette machine, <a href="<?php echo esc_url(home_url('/contact/')); ?>" class="<?php echo esc_attr($link_class); ?>">contactez le FabLab</a>. <a href="<?php echo esc_url(home_url('/fablab/#tarifs')); ?>" class="<?php echo esc_attr($link_class); ?>">Voir les tarifs</a>.</p>
                    <?php endif; ?>
                </div>
            </aside>

            <div class="min-w-0 lg:col-start-1 lg:row-start-1">
                <?php if (has_post_thumbnail($machine)) : ?>
                    <figure class="mb-10">
                        <?php echo wp_get_attachment_image((int) get_post_thumbnail_id($machine), 'large', false, [
                            'alt'      => '',
                            'loading'  => 'eager',
                            'decoding' => 'async',
                            'class'    => 'block h-auto max-h-[34rem] w-auto max-w-full rounded-2xl',
                        ]); ?>
                    </figure>
                <?php endif; ?>

                <?php if (trim($body) !== '') : ?>
                    <div class="article-prose machine-body">
                        <?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the_content output. ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>

    </div>
</article>
