<?php

/**
 * Full agenda: every published activity still to come, grouped by month.
 *
 * @var list<\Esquare\Theme\Notion\Activity> $activities  Provided by Esquare\Theme\Block\Agenda::render().
 * @var bool                                 $failed      True when Notion could not be reached.
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Esquare\Theme\Block\Agenda;
use Esquare\Theme\Block\AgendaFormat;

$months = AgendaFormat::byMonth($activities);
$total  = count($activities);
$span   = AgendaFormat::span($activities);
?>
<section aria-labelledby="agenda-page-title" class="bg-cream px-6 pt-14 pb-20 lg:px-8 lg:pt-20 lg:pb-28">
    <div class="mx-auto max-w-5xl">

        <header class="reveal">
            <p class="inline-flex items-center gap-2 [font-family:var(--font-figtree)] text-xs font-bold text-navy/70 uppercase tracking-widest">
                <span aria-hidden="true" class="inline-block size-2 rounded-full bg-yellow"></span>
                Agenda
            </p>
            <h1 id="agenda-page-title" class="article-title mt-4">Tout ce qui se passe à l'e-Square.</h1>
            <?php if ($total > 0) : ?>
                <p class="mt-5 max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-navy/75">
                    <?php echo esc_html(sprintf(
                        _n('%d activité à venir', '%d activités à venir', $total, 'esquare'),
                        $total
                    )); ?><?php echo $span !== '' ? esc_html(', ' . $span) : ''; ?>. Ateliers, formations, permanences et rendez-vous, rue Victor Libert à Marche-en-Famenne.
                </p>
            <?php endif; ?>
        </header>

        <?php if ($failed) : ?>
            <div class="mt-12 border-t border-navy/12 pt-8">
                <p class="max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-navy">L'agenda n'a pas pu être chargé pour le moment.</p>
                <p class="mt-2 max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-navy/70">Réessayez dans quelques minutes, ou appelez-nous au <a href="tel:+3284327054" class="font-semibold text-navy underline decoration-navy/30 underline-offset-4 hover:decoration-navy">+32 (0)84 32 70 54</a> pour connaître le programme.</p>
            </div>
        <?php elseif ($total === 0) : ?>
            <div class="mt-12 border-t border-navy/12 pt-8">
                <p class="max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-navy">Aucune activité n'est programmée pour l'instant.</p>
                <p class="mt-2 max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-navy/70">Le programme se remplit au fil des semaines. <a href="/contact/" class="font-semibold text-navy underline decoration-navy/30 underline-offset-4 hover:decoration-navy">Écrivez-nous</a> pour être prévenu, ou pour proposer une activité.</p>
            </div>
        <?php else : ?>
            <?php foreach ($months as $month) : ?>
                <section aria-labelledby="agenda-mois-<?php echo esc_attr($month['key']); ?>" class="mt-14 first:mt-12">
                    <h2 id="agenda-mois-<?php echo esc_attr($month['key']); ?>" class="sticky top-0 z-10 bg-cream pt-3 pb-3 font-serif text-[1.75rem] leading-none tracking-[-0.01em] text-navy lg:text-[2.25rem]">
                        <?php echo esc_html($month['label']); ?>
                        <span class="ml-3 align-middle font-mono text-[0.7rem] font-semibold uppercase tracking-[0.14em] text-navy/70"><?php echo esc_html(sprintf(
                            _n('%d activité', '%d activités', count($month['activities']), 'esquare'),
                            count($month['activities'])
                        )); ?></span>
                    </h2>
                    <ol class="mt-1 border-b border-navy/12" role="list">
                        <?php Agenda::rows($month['activities']); ?>
                    </ol>
                </section>
            <?php endforeach; ?>

            <p class="mt-12 max-w-[60ch] [font-family:var(--font-figtree)] text-sm text-pretty text-navy/60">
                Programme tenu à jour par l'équipe de l'e-Square. Une question sur une activité ?
                <a href="/contact/" class="font-semibold text-navy underline decoration-navy/30 underline-offset-4 hover:decoration-navy">Contactez-nous</a>.
            </p>
        <?php endif; ?>

    </div>
</section>
