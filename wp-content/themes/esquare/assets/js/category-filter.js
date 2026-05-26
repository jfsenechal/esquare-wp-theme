import { store, getContext } from '@wordpress/interactivity';

const NAMESPACE = 'esquare/category-filter';

const { state } = store(NAMESPACE, {
    state: {
        get isAllActive() {
            return state.activeTermId === 0;
        },
        get isChipActive() {
            const ctx = getContext();
            return state.activeTermId === ctx.termId;
        },
        get isCardHidden() {
            if (state.activeTermId === 0) return false;
            const ctx = getContext();
            if (!ctx || !Array.isArray(ctx.terms)) return true;
            return !ctx.terms.includes(state.activeTermId);
        },
        get isAllHidden() {
            if (state.activeTermId === 0) return false;
            const cards = document.querySelectorAll('.esq-card[data-wp-context]');
            if (cards.length === 0) return false;
            return Array.from(cards).every((card) =>
                card.classList.contains('esq-hidden')
            );
        },
    },
    actions: {
        setFilter(event) {
            if (
                event.metaKey ||
                event.ctrlKey ||
                event.shiftKey ||
                event.altKey ||
                event.button > 0
            ) {
                return;
            }
            event.preventDefault();
            const ctx = getContext();
            const nextId = Number.isFinite(ctx.termId) ? ctx.termId : 0;
            state.activeTermId = nextId;

            const url = new URL(window.location.href);
            if (nextId === 0) {
                url.searchParams.delete('filter');
            } else if (ctx.termSlug) {
                url.searchParams.set('filter', ctx.termSlug);
            }
            window.history.replaceState({}, '', url);
        },
    },
    callbacks: {
        syncFromUrl() {
            const url = new URL(window.location.href);
            const slug = url.searchParams.get('filter');
            if (!slug) {
                state.activeTermId = 0;
                return;
            }
            const map = state.slugToId || {};
            const id = map[slug];
            state.activeTermId = typeof id === 'number' ? id : 0;
        },
    },
});
