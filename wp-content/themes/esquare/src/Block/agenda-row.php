<?php

/**
 * One agenda row, shared by the home band and the agenda page.
 *
 * @var \Esquare\Theme\Notion\Activity $activity  Provided by Esquare\Theme\Block\Agenda::rows().
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Esquare\Theme\Block\AgendaFormat;

$is_today = AgendaFormat::isToday($activity);
$meta     = AgendaFormat::meta($activity);
$tag      = AgendaFormat::tag($activity);
?>
<li class="agenda-row">
    <article class="grid grid-cols-[3.5rem_1fr] gap-x-4 border-t border-navy/12 py-5 sm:grid-cols-[4.25rem_minmax(0,1fr)_auto] sm:gap-x-7 sm:py-6">

        <time datetime="<?php echo esc_attr($activity->start?->format('Y-m-d') ?? ''); ?>" class="row-start-1 text-center sm:text-left">
            <span class="sr-only"><?php echo esc_html(AgendaFormat::fullDate($activity)); ?></span>
            <span aria-hidden="true" class="block font-mono text-[0.65rem] font-semibold uppercase tracking-[0.14em] <?php echo $is_today ? 'text-navy' : 'text-navy/70'; ?>"><?php echo esc_html(AgendaFormat::weekday($activity)); ?></span>
            <span aria-hidden="true" class="mt-0.5 block text-[2.15rem] font-black leading-[0.9] tracking-tight tabular-nums <?php echo $is_today ? 'text-navy' : 'text-navy/85'; ?> sm:text-[2.5rem]"><?php echo esc_html(AgendaFormat::day($activity)); ?></span>
            <span aria-hidden="true" class="mt-0.5 block font-mono text-[0.65rem] font-semibold uppercase tracking-[0.14em] <?php echo $is_today ? 'text-navy' : 'text-navy/70'; ?>"><?php echo esc_html(AgendaFormat::month($activity)); ?></span>
        </time>

        <div class="row-start-1 min-w-0 border-l border-navy/10 pl-4 sm:pl-7">
            <h3 class="text-pretty [font-family:var(--font-figtree)] text-[1.0625rem] font-bold leading-snug text-navy sm:text-xl"><?php echo esc_html(AgendaFormat::title($activity)); ?></h3>

            <p class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1.5 [font-family:var(--font-figtree)] text-sm text-navy/70">
                <?php if ($is_today) : ?>
                    <span class="inline-flex items-center rounded-full bg-yellow px-1.5 py-px font-mono text-[0.6rem] font-bold uppercase tracking-[0.1em] text-navy-deep">Aujourd'hui</span>
                <?php endif; ?>
                <?php foreach ($meta as $index => $item) : ?>
                    <?php if ($index > 0) : ?>
                        <span aria-hidden="true" class="text-navy/30">·</span>
                    <?php endif; ?>
                    <span><?php echo esc_html($item); ?></span>
                <?php endforeach; ?>
            </p>

            <?php if ($activity->registrationUrl !== null) : ?>
                <a href="<?php echo esc_url($activity->registrationUrl); ?>" class="mt-3 inline-flex min-h-[44px] items-center gap-1.5 rounded-full bg-navy px-4 [font-family:var(--font-figtree)] text-sm font-semibold text-white transition-colors hover:bg-navy-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-deep">
                    S'inscrire<span aria-hidden="true">→</span>
                    <span class="sr-only"> à « <?php echo esc_html(AgendaFormat::title($activity)); ?> »</span>
                </a>
            <?php endif; ?>
        </div>

        <?php if ($tag !== '') : ?>
            <div class="col-start-2 border-l border-navy/10 pt-2.5 pl-4 sm:col-start-3 sm:row-start-1 sm:border-l-0 sm:pt-1 sm:pl-0">
                <p class="inline-block rounded-full border border-navy/15 px-2.5 py-1 font-mono text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-navy/70"><?php echo esc_html($tag); ?></p>
            </div>
        <?php endif; ?>

    </article>
</li>
