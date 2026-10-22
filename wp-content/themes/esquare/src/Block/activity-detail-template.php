<?php

/**
 * Detail page of one activity.
 *
 * @var \Esquare\Theme\Notion\Activity|null $activity  Provided by Esquare\Theme\Block\ActivityDetail::render(); null when Notion failed.
 * @var bool                                $failed    True when Notion could not be reached.
 * @var string                              $body      Safe HTML of the public description (BlockRenderer output), may be ''.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Esquare\Theme\Block\Agenda;
use Esquare\Theme\Block\AgendaFormat;

$link_class = 'font-semibold text-navy underline decoration-navy/30 underline-offset-4 hover:decoration-navy';
?>
<article aria-labelledby="activite-titre" class="bg-cream px-6 pt-10 pb-20 lg:px-8 lg:pt-14 lg:pb-28">
    <div class="mx-auto max-w-5xl">

        <a href="<?php echo esc_url(home_url(Agenda::AGENDA_URL)); ?>" class="article-back"><span class="arrow" aria-hidden="true">←</span> Tout l'agenda</a>

        <?php if ($activity === null) : ?>
            <div class="mt-10 border-t border-navy/12 pt-8">
                <h1 id="activite-titre" class="font-serif text-[2rem] leading-tight text-navy">Activité momentanément indisponible</h1>
                <p class="mt-3 max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-navy/75">Le détail de cette activité n'a pas pu être chargé. Réessayez dans quelques minutes, ou appelez-nous au <a href="tel:+3284327054" class="<?php echo esc_attr($link_class); ?>">+32 (0)84 32 70 54</a>.</p>
            </div>
        <?php else :
            $is_over  = $activity->isOver();
            $eyebrow  = array_values(array_filter([AgendaFormat::tag($activity), $activity->theme ?? '']));
            $time     = AgendaFormat::timeLabel($activity);
            $facts    = array_filter([
                'Date'         => AgendaFormat::fullDate($activity),
                'Horaire'      => $time === 'Journée' ? '' : $time,
                'Durée'        => $activity->duration !== null ? AgendaFormat::duration($activity->duration) : '',
                'Organisé par' => implode(', ', $activity->organisers),
                'Public'       => implode(', ', $activity->audiences),
                'Places'       => $activity->maxParticipants !== null
                    ? sprintf(_n('%d participant maximum', '%d participants maximum', $activity->maxParticipants, 'esquare'), $activity->maxParticipants)
                    : '',
            ], static fn (string $value): bool => $value !== '');
            $cover = $activity->coverImage();
            ?>

            <header class="reveal mt-8">
                <?php if ($eyebrow !== []) : ?>
                    <p class="article-eyebrow"><?php echo esc_html(implode(' · ', array_unique($eyebrow))); ?></p>
                <?php endif; ?>
                <h1 id="activite-titre" class="article-title mt-4"><?php echo esc_html(AgendaFormat::title($activity)); ?></h1>
            </header>

            <div class="mt-10 grid gap-10 lg:mt-14 lg:grid-cols-[minmax(0,1fr)_19rem] lg:gap-14">

                <aside aria-label="Informations pratiques" class="lg:col-start-2 lg:row-start-1">
                    <div class="border-t-2 border-navy pt-5 lg:sticky lg:top-6">
                        <?php if ($is_over) : ?>
                            <p class="mb-5 inline-flex items-center rounded-full border border-navy/20 px-3 py-1 font-mono text-xs font-semibold uppercase tracking-[0.12em] text-navy/70">Activité terminée</p>
                        <?php endif; ?>

                        <dl class="grid gap-4 [font-family:var(--font-figtree)] text-base">
                            <?php foreach ($facts as $label => $value) : ?>
                                <div>
                                    <dt class="font-mono text-xs font-semibold uppercase tracking-[0.14em] text-navy/60"><?php echo esc_html($label); ?></dt>
                                    <dd class="mt-1 text-pretty text-navy"><?php echo esc_html($label === 'Date' ? AgendaFormat::ucfirst($value) : $value); ?></dd>
                                </div>
                            <?php endforeach; ?>
                            <div>
                                <dt class="font-mono text-xs font-semibold uppercase tracking-[0.14em] text-navy/60">Lieu</dt>
                                <dd class="mt-1 text-navy">e-Square, rue Victor Libert, Marche-en-Famenne</dd>
                            </div>
                        </dl>

                        <?php if (! $is_over && $activity->registrationUrl !== null) : ?>
                            <a href="<?php echo esc_url($activity->registrationUrl); ?>" class="mt-7 inline-flex min-h-[48px] w-full items-center justify-center gap-2 rounded-full bg-navy px-6 [font-family:var(--font-figtree)] text-base font-semibold text-white transition-colors hover:bg-navy-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-deep">
                                S'inscrire<span aria-hidden="true">→</span>
                            </a>
                        <?php elseif (! $is_over) : ?>
                            <p class="mt-7 [font-family:var(--font-figtree)] text-base text-pretty text-navy/75">Pour vous inscrire, <a href="/contact/" class="<?php echo esc_attr($link_class); ?>">contactez-nous</a> ou appelez le <a href="tel:+3284327054" class="<?php echo esc_attr($link_class); ?> whitespace-nowrap">+32 (0)84 32 70 54</a>.</p>
                        <?php endif; ?>
                    </div>
                </aside>

                <div class="min-w-0 lg:col-start-1 lg:row-start-1">
                    <?php if ($cover !== null) : ?>
                        <figure class="mb-10">
                            <img src="<?php echo esc_url($cover); ?>" alt="" class="block aspect-[16/9] w-full object-cover" loading="eager" decoding="async">
                        </figure>
                    <?php endif; ?>

                    <?php if ($body !== '') : ?>
                        <div class="article-prose activity-body">
                            <?php
                            // Built by BlockRenderer: allow-listed tags, escaped text, filtered URLs.
                            echo $body; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            ?>
                        </div>
                    <?php else : ?>
                        <p class="max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-navy/75">Le programme détaillé sera bientôt en ligne. Une question ? <a href="/contact/" class="<?php echo esc_attr($link_class); ?>">Écrivez-nous</a>.</p>
                    <?php endif; ?>
                </div>

            </div>
        <?php endif; ?>

    </div>
</article>
