<?php

/**
 * "Le parc de machines" section of the Fablab page.
 *
 * @var list<\WP_Post> $featured  Machines with a photo, shown as cards.
 * @var list<\WP_Post> $others    The rest, in the "Et aussi" list.
 *
 * Provided by Esquare\Theme\Block\Machines::render().
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Esquare\Theme\Block\Machines;
use Esquare\Theme\Machine\MachinePostType;
?>
<section id="equipements" aria-labelledby="equipements-title" class="relative isolate overflow-hidden bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24">
    <div aria-hidden="true" class="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>

    <div class="mx-auto max-w-7xl">
        <div class="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
            <div>
                <p class="inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow">
                    <span aria-hidden="true" class="inline-block size-1.5 rounded-full bg-yellow"></span>
                    Le parc de machines
                </p>
                <h2 id="equipements-title" class="mt-4 max-w-[18ch] [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance md:text-4xl lg:text-5xl">Un parc de machines, formation comprise.</h2>
            </div>
            <p class="max-w-[54ch] [font-family:var(--font-figtree)] text-pretty text-base text-white/70 lg:text-lg">L'abonnement comprend la formation, l'accès au parc et le suivi de votre projet. Certaines machines sont facturées au temps d'utilisation ; les matériaux restent à votre charge.</p>
        </div>

        <?php if ($featured === [] && $others === []) : ?>
            <div class="mt-14 border-t border-white/15 pt-8">
                <p class="max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-white">La liste des machines arrive bientôt.</p>
                <p class="mt-2 max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-white/70">En attendant, <a href="/contact/" class="font-semibold text-white underline decoration-white/30 underline-offset-4 hover:decoration-white">racontez-nous votre projet</a> : nous vous dirons quelle machine s'y prête.</p>
            </div>
        <?php endif; ?>

        <?php if ($featured !== []) : ?>
            <ul role="list" class="mt-14 grid grid-cols-1 gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($featured as $machine) :
                    $title   = get_the_title($machine);
                    $price   = MachinePostType::price($machine);
                    $excerpt = trim($machine->post_excerpt);
                    $booking = MachinePostType::bookingUrl($machine);
                    $url     = Machines::hasSheet($machine) ? (string) get_permalink($machine) : '';
                    ?>
                    <li class="group/card relative flex flex-col">
                        <?php echo wp_get_attachment_image((int) get_post_thumbnail_id($machine), 'medium_large', false, [
                            'alt'      => '',
                            'loading'  => 'lazy',
                            'decoding' => 'async',
                            'sizes'    => '(min-width: 1024px) 400px, (min-width: 640px) 50vw, 100vw',
                            'class'    => 'aspect-[4/3] w-full rounded-2xl bg-white object-cover ring-1 ring-white/12',
                        ]); ?>
                        <h3 class="mt-5 [font-family:var(--font-figtree)] text-lg font-bold text-white">
                            <?php if ($url !== '') : ?>
                                <a href="<?php echo esc_url($url); ?>" class="underline decoration-transparent underline-offset-4 transition-colors after:absolute after:inset-0 after:content-[''] group-hover/card:decoration-yellow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow"><?php echo esc_html($title); ?></a>
                            <?php else : ?>
                                <?php echo esc_html($title); ?>
                            <?php endif; ?>
                        </h3>
                        <?php if ($price !== '') : ?>
                            <p class="mt-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-yellow"><?php echo esc_html($price); ?></p>
                        <?php endif; ?>
                        <?php if ($excerpt !== '') : ?>
                            <p class="mt-2 [font-family:var(--font-figtree)] text-base text-white/70"><?php echo nl2br(esc_html($excerpt), false); ?></p>
                        <?php endif; ?>
                        <?php if ($booking !== '') : ?>
                            <a href="<?php echo esc_url($booking); ?>" class="group relative z-10 mt-3 inline-flex min-h-[44px] items-center gap-1.5 self-start [font-family:var(--font-figtree)] text-base font-semibold text-white underline decoration-white/30 underline-offset-4 hover:decoration-yellow focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-yellow">
                                Réserver<span class="sr-only"> : <?php echo esc_html($title); ?></span>
                                <span aria-hidden="true" class="inline-block transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1">→</span>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <?php if ($others !== []) : ?>
            <!-- Le reste du parc -->
            <div class="mt-16 grid grid-cols-1 gap-x-14 gap-y-8 border-t border-white/15 pt-10 lg:grid-cols-[16rem_minmax(0,1fr)]">
                <h3 class="font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-white/65"><?php echo $featured === [] ? 'Au FabLab' : 'Et aussi au FabLab'; ?></h3>
                <div>
                    <ul role="list" class="grid grid-cols-1 gap-x-10 gap-y-5 [font-family:var(--font-figtree)] sm:grid-cols-2">
                        <?php foreach ($others as $machine) :
                            $price   = MachinePostType::price($machine);
                            $excerpt = trim($machine->post_excerpt);
                            ?>
                            <li>
                                <p class="font-semibold text-white">
                                    <?php if (Machines::hasSheet($machine)) : ?>
                                        <a href="<?php echo esc_url((string) get_permalink($machine)); ?>" class="underline decoration-white/30 underline-offset-4 hover:decoration-yellow"><?php echo esc_html(get_the_title($machine)); ?></a>
                                    <?php else : ?>
                                        <?php echo esc_html(get_the_title($machine)); ?>
                                    <?php endif; ?>
                                </p>
                                <?php if ($excerpt !== '') : ?>
                                    <p class="text-base text-white/70"><?php echo nl2br(esc_html($excerpt), false); ?></p>
                                <?php endif; ?>
                                <?php if ($price !== '') : ?>
                                    <p class="text-base text-white/70"><?php echo esc_html($price); ?></p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <p class="mt-8 max-w-[62ch] [font-family:var(--font-figtree)] text-base text-white/65">Deux règles d'atelier : pas de peinture sur place, et pas de matériaux dégageant du chlore ou des agents nocifs à la découpe laser.</p>
    </div>
</section>
