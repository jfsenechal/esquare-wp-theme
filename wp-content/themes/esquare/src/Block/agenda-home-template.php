<?php

/**
 * Front-page agenda band, placed under "Nos événements & ateliers".
 *
 * @var list<\Esquare\Theme\Notion\Activity> $activities  Provided by Esquare\Theme\Block\Agenda::render().
 */

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

use Esquare\Theme\Block\Agenda;
?>
<section id="agenda" aria-labelledby="agenda-title" class="bg-stone-warm px-6 py-20 lg:px-8 lg:py-24">
    <div class="mx-auto max-w-5xl">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="inline-flex items-center gap-2 [font-family:var(--font-figtree)] text-xs font-bold text-navy/70 uppercase tracking-widest">
                    <span aria-hidden="true" class="inline-block size-2 rounded-full bg-yellow"></span>
                    Agenda
                </p>
                <h2 id="agenda-title" class="mt-3 [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl">Ce qui se passe ici, prochainement.</h2>
            </div>
            <a href="<?php echo esc_url(Agenda::AGENDA_URL); ?>" class="group inline-flex min-h-[44px] items-center gap-1.5 [font-family:var(--font-figtree)] text-sm font-semibold text-navy underline decoration-navy/30 underline-offset-4 hover:decoration-navy focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy-deep">
                Tout l'agenda
                <span aria-hidden="true" class="inline-block transition-transform duration-500 ease-[cubic-bezier(0.22,1,0.36,1)] group-hover:translate-x-1">→</span>
            </a>
        </div>

        <ol class="mt-10 border-b border-navy/12" role="list">
            <?php Agenda::rows($activities); ?>
        </ol>
    </div>
</section>
