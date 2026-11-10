import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
    BlockControls,
    InspectorControls,
    RichText,
    URLInput,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    Button,
    PanelBody,
    SelectControl,
    ToggleControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const FIGTREE = '[font-family:var(--font-figtree)]';
const THEMES = {
    light: {
        section: 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
        eyebrow: `inline-flex items-center gap-2 ${FIGTREE} text-xs font-bold text-navy/70 uppercase tracking-widest`,
        dot: 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        title: `mt-3 max-w-[15ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl`,
        intro: `max-w-[54ch] ${FIGTREE} text-pretty text-base text-navy/75 lg:text-lg`,
        listTitle: 'font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy/60',
        button: 'bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-navy-deep',
        footnote: `${FIGTREE} text-base text-navy/70`,
        card: 'stone',
        featured: 'navy',
    },
    dark: {
        section: 'relative isolate overflow-hidden bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        eyebrow: 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow',
        dot: 'inline-block size-1.5 rounded-full bg-yellow',
        title: `mt-4 max-w-[14ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance md:text-4xl lg:text-5xl`,
        intro: `max-w-[52ch] ${FIGTREE} text-pretty text-base text-white/70 lg:text-lg`,
        listTitle: 'font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-white/50',
        button: 'bg-yellow text-navy-deep hover:bg-yellow-soft focus-visible:outline-yellow',
        footnote: `${FIGTREE} text-base text-white/60`,
        card: 'glass',
        featured: 'glass',
    },
};
// Colours of everything inside an offer, by the surface it sits on.
const TONES = {
    navy: {
        card: 'bg-navy-deep text-white ring-1 ring-navy/10',
        title: 'text-white',
        tag: 'bg-yellow text-navy-deep',
        text: 'text-white/70',
        rule: 'border-white/15',
        label: 'text-white/70',
        price: 'text-white',
        unit: 'text-white/55',
        item: 'text-white',
        note: 'text-white/65',
    },
    stone: {
        card: 'bg-stone-warm text-navy ring-1 ring-navy/10',
        title: 'text-navy',
        tag: 'bg-navy-deep text-yellow',
        text: 'text-navy/75',
        rule: 'border-navy/12',
        label: 'text-navy/75',
        price: 'text-navy',
        unit: 'text-navy/70',
        item: 'text-navy',
        note: 'text-navy/70',
    },
    glass: {
        card: 'bg-white/[0.06] text-white ring-1 ring-white/15',
        title: 'text-white',
        tag: 'bg-yellow text-navy-deep',
        text: 'text-white/70',
        rule: 'border-white/15',
        label: 'text-white/70',
        price: 'text-yellow',
        unit: 'text-white/60',
        item: 'text-white',
        note: 'text-white/65',
    },
};
// Tailwind only sees literal class names, so every grid is spelled out.
const CARD_GRID = { 1: 'lg:grid-cols-1', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4' };
const FEATURED_GRID = {
    1: 'lg:grid-cols-1',
    2: 'lg:grid-cols-[1.35fr_1fr]',
    3: 'lg:grid-cols-[1.35fr_1fr_1fr]',
    4: 'lg:grid-cols-[1.35fr_1fr_1fr_1fr]',
};
const EMPTY_PRICE = { label: '', amount: '', unit: '' };
const EMPTY_OFFER = { name: '', tag: '', text: '', prices: [{ ...EMPTY_PRICE }], features: [], note: '' };

const isExternal = (url) => /^https?:\/\//i.test(url ?? '') && !(url ?? '').includes(window.location.host);

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { theme, layout, featureFirst, eyebrow, title, intro, listTitle, offers, buttonText, buttonUrl, footnote } = attributes;
    const t = THEMES[theme] ?? THEMES.light;
    const blockProps = useBlockProps({ className: t.section });
    const isList = layout === 'list';

    // Offers are nested arrays of plain objects; always write fresh copies.
    const updateOffer = (index, changes) =>
        setAttributes({ offers: offers.map((o, i) => (i === index ? { ...o, ...changes } : o)) });
    const updateSub = (index, key, subIndex, value) =>
        updateOffer(index, { [key]: offers[index][key].map((item, j) => (j === subIndex ? value : item)) });
    const moveOffer = (index, delta) => {
        const next = [...offers];
        const [offer] = next.splice(index, 1);
        next.splice(index + delta, 0, offer);
        setAttributes({ offers: next });
    };

    const field = (tagName, className, value, onChange, placeholder, formats = []) => (
        <RichText
            tagName={tagName}
            className={className}
            value={value}
            onChange={onChange}
            placeholder={placeholder}
            allowedFormats={formats}
        />
    );
    const amount = (offer, index, p, className, unitClass) => (
        <p className={`${FIGTREE} font-bold tracking-tight tabular-nums ${className}`}>
            {field('span', '', offer.prices[p]?.amount, (v) => updateSub(index, 'prices', p, { ...offer.prices[p], amount: v }), __('Prix', 'esquare'))}{' '}
            {(isSelected || offer.prices[p]?.unit) &&
                field('span', `text-base font-semibold tracking-normal ${unitClass}`, offer.prices[p]?.unit, (v) => updateSub(index, 'prices', p, { ...offer.prices[p], unit: v }), __('/ unité', 'esquare'))}
        </p>
    );

    const card = (offer, index) => {
        const tone = TONES[featureFirst && index === 0 ? t.featured : t.card];
        const single = offer.prices.length === 1 && !offer.prices[0].label;
        return (
            <div key={index} className={`flex flex-col rounded-2xl p-7 lg:p-8 ${tone.card}`}>
                <div className="flex items-start justify-between gap-4">
                    {field('h3', `${FIGTREE} text-2xl font-bold tracking-tight ${tone.title}`, offer.name, (v) => updateOffer(index, { name: v }), __('Nom de la formule', 'esquare'))}
                    {(isSelected || offer.tag) &&
                        field('span', `shrink-0 rounded-full px-3 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] ${tone.tag}`, offer.tag, (v) => updateOffer(index, { tag: v }), __('Badge', 'esquare'))}
                </div>
                {field('p', `mt-3 max-w-[40ch] ${FIGTREE} text-base ${tone.text}`, offer.text, (v) => updateOffer(index, { text: v }), __('Description', 'esquare'), ['core/bold'])}
                {single ? (
                    <div className="mt-7">{amount(offer, index, 0, `text-4xl ${tone.price}`, tone.unit)}</div>
                ) : (
                    <dl className={`mt-7 ${FIGTREE}`}>
                        {offer.prices.map((price, p) => (
                            <div key={p} className={`flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-t py-4 ${tone.rule}`}>
                                {field('dt', `text-base ${tone.label}`, price.label, (v) => updateSub(index, 'prices', p, { ...price, label: v }), __('Pour qui / combien', 'esquare'))}
                                <dd>{amount(offer, index, p, `text-3xl ${tone.price}`, tone.unit)}</dd>
                            </div>
                        ))}
                    </dl>
                )}
                {offer.features.length > 0 && (
                    <ul role="list" className={`mt-6 space-y-2.5 border-t pt-6 ${FIGTREE} text-base ${tone.rule} ${tone.item}`}>
                        {offer.features.map((feature, f) => (
                            <li key={f} className="flex gap-2.5">
                                <span aria-hidden="true" className="mt-2.5 size-1.5 shrink-0 rounded-full bg-yellow"></span>
                                {field('span', '', feature, (v) => updateSub(index, 'features', f, v), __('Point inclus', 'esquare'))}
                            </li>
                        ))}
                    </ul>
                )}
                {(isSelected || offer.note) &&
                    field('p', `mt-auto pt-6 ${FIGTREE} text-base ${tone.note}`, offer.note, (v) => updateOffer(index, { note: v }), __('Note (optionnelle)', 'esquare'))}
            </div>
        );
    };

    const wide = (offer, index) => {
        const tone = TONES[t.featured];
        return (
            <div key={index} className={`mt-14 flex flex-col gap-6 rounded-2xl p-7 sm:flex-row sm:items-center sm:justify-between lg:p-9 ${tone.card}`}>
                <div>
                    {field('h3', `${FIGTREE} text-2xl font-bold tracking-tight ${tone.title}`, offer.name, (v) => updateOffer(index, { name: v }), __('Nom de la formule', 'esquare'))}
                    {field('p', `mt-2 max-w-[46ch] ${FIGTREE} text-pretty text-base ${tone.text}`, offer.text, (v) => updateOffer(index, { text: v }), __('Description', 'esquare'), ['core/bold'])}
                </div>
                <div className="shrink-0">{amount(offer, index, 0, `text-4xl ${tone.price}`, tone.unit)}</div>
            </div>
        );
    };

    const row = (offer, index, last) => {
        const tone = TONES[theme === 'dark' ? 'glass' : 'stone'];
        return (
            <li
                key={index}
                className={`grid grid-cols-1 items-baseline gap-x-8 gap-y-2 border-t py-6 sm:grid-cols-[minmax(0,1fr)_auto] lg:grid-cols-[15rem_minmax(0,1fr)_auto] lg:py-7 ${tone.rule}${last ? ' border-b' : ''}`}
            >
                <div>
                    {field('h4', `${FIGTREE} text-xl font-bold tracking-tight lg:text-2xl ${tone.title}`, offer.name, (v) => updateOffer(index, { name: v }), __('Nom de la formule', 'esquare'))}
                    {field('p', `mt-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.18em] ${theme === 'dark' ? 'text-yellow' : 'text-navy/70'}`, offer.tag, (v) => updateOffer(index, { tag: v }), __('Rythme / précision', 'esquare'))}
                </div>
                {field('p', `${FIGTREE} text-base ${tone.text}`, offer.text, (v) => updateOffer(index, { text: v }), __('Description', 'esquare'), ['core/bold'])}
                <div className="lg:text-right">{amount(offer, index, 0, `text-3xl ${theme === 'dark' ? 'text-white' : tone.price}`, tone.unit)}</div>
            </li>
        );
    };

    const listOffers = isList && featureFirst ? offers.slice(1) : offers;

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="grid-view"
                        label={__('Formules en cartes', 'esquare')}
                        isPressed={!isList}
                        onClick={() => setAttributes({ layout: 'cards' })}
                    />
                    <ToolbarButton
                        icon="list-view"
                        label={__('Formules en liste', 'esquare')}
                        isPressed={isList}
                        onClick={() => setAttributes({ layout: 'list' })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="plus-alt2"
                        label={__('Ajouter une formule', 'esquare')}
                        onClick={() => setAttributes({ offers: [...offers, { ...EMPTY_OFFER, prices: [{ ...EMPTY_PRICE }] }] })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Présentation', 'esquare')}>
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Fond', 'esquare')}
                        value={theme}
                        options={[
                            { value: 'light', label: __('Clair (crème)', 'esquare') },
                            { value: 'dark', label: __('Sombre (bleu nuit)', 'esquare') },
                        ]}
                        onChange={(value) => setAttributes({ theme: value })}
                    />
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Mettre la première formule en avant', 'esquare')}
                        checked={featureFirst}
                        onChange={(value) => setAttributes({ featureFirst: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Bouton', 'esquare')} initialOpen={false}>
                    <URLInput
                        className="esquare-url-input"
                        label={__('Lien du bouton (vide = pas de bouton)', 'esquare')}
                        value={buttonUrl}
                        onChange={(value) => setAttributes({ buttonUrl: value })}
                    />
                </PanelBody>
                {offers.map((offer, index) => (
                    <PanelBody
                        key={index}
                        initialOpen={false}
                        title={sprintf(
                            /* translators: 1: offer number, 2: offer name. */
                            __('Formule %1$d : %2$s', 'esquare'),
                            index + 1,
                            decodeEntities(offer.name) || __('sans nom', 'esquare')
                        )}
                    >
                        {!isList && (
                            <>
                                <p>
                                    <strong>{__('Prix', 'esquare')}</strong>{' '}
                                    {__('Un seul prix sans intitulé s’affiche en grand ; plusieurs prix forment un tableau.', 'esquare')}
                                </p>
                                {offer.prices.map((price, p) => (
                                    <p key={p}>
                                        {decodeEntities(price.label || price.amount) || __('prix vide', 'esquare')}{' '}
                                        {offer.prices.length > 1 && (
                                            <Button
                                                variant="link"
                                                isDestructive
                                                onClick={() => updateOffer(index, { prices: offer.prices.filter((_, j) => j !== p) })}
                                            >
                                                {__('Retirer', 'esquare')}
                                            </Button>
                                        )}
                                    </p>
                                ))}
                                <Button variant="secondary" onClick={() => updateOffer(index, { prices: [...offer.prices, { ...EMPTY_PRICE }] })}>
                                    {__('Ajouter un prix', 'esquare')}
                                </Button>
                                <p style={{ marginTop: '16px' }}>
                                    <strong>{__('Points inclus', 'esquare')}</strong>
                                </p>
                                {offer.features.map((feature, f) => (
                                    <p key={f}>
                                        {decodeEntities(feature) || __('point vide', 'esquare')}{' '}
                                        <Button
                                            variant="link"
                                            isDestructive
                                            onClick={() => updateOffer(index, { features: offer.features.filter((_, j) => j !== f) })}
                                        >
                                            {__('Retirer', 'esquare')}
                                        </Button>
                                    </p>
                                ))}
                                <Button variant="secondary" onClick={() => updateOffer(index, { features: [...offer.features, ''] })}>
                                    {__('Ajouter un point', 'esquare')}
                                </Button>
                            </>
                        )}
                        <p style={{ marginTop: '16px' }}>
                            <Button variant="tertiary" disabled={index === 0} onClick={() => moveOffer(index, -1)}>
                                {__('↑ Monter', 'esquare')}
                            </Button>
                            <Button variant="tertiary" disabled={index === offers.length - 1} onClick={() => moveOffer(index, 1)}>
                                {__('↓ Descendre', 'esquare')}
                            </Button>
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ offers: offers.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer la formule', 'esquare')}
                            </Button>
                        </p>
                    </PanelBody>
                ))}
            </InspectorControls>

            <section {...blockProps}>
                {theme === 'dark' && <div aria-hidden="true" className="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>}
                <div className="mx-auto max-w-7xl">
                    <div className="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
                        <div>
                            <p className={t.eyebrow}>
                                <span aria-hidden="true" className={t.dot}></span>
                                {field('span', '', eyebrow, (v) => setAttributes({ eyebrow: v }), __('Surtitre', 'esquare'))}
                            </p>
                            {field('h2', t.title, title, (v) => setAttributes({ title: v }), __('Titre', 'esquare'))}
                        </div>
                        {(isSelected || intro) &&
                            field('p', t.intro, intro, (v) => setAttributes({ intro: v }), __('Introduction (optionnelle)', 'esquare'), ['core/bold', 'core/italic', 'core/link'])}
                    </div>

                    {isList ? (
                        <>
                            {featureFirst && offers[0] && wide(offers[0], 0)}
                            {(isSelected || listTitle) &&
                                field('h3', `${featureFirst ? 'mt-16' : 'mt-14'} ${t.listTitle}`, listTitle, (v) => setAttributes({ listTitle: v }), __('Titre de la liste (optionnel)', 'esquare'))}
                            <ul role="list" className={listTitle || isSelected ? 'mt-4' : 'mt-14'}>
                                {listOffers.map((offer, i) => {
                                    const index = featureFirst ? i + 1 : i;
                                    return row(offer, index, i === listOffers.length - 1);
                                })}
                            </ul>
                        </>
                    ) : (
                        <div className={`mt-14 grid grid-cols-1 gap-6 ${(featureFirst ? FEATURED_GRID : CARD_GRID)[offers.length] ?? CARD_GRID[3]}`}>
                            {offers.map(card)}
                        </div>
                    )}

                    {(buttonUrl || footnote || isSelected) && (
                        <div className="mt-8 flex flex-wrap items-center gap-x-6 gap-y-4">
                            {buttonUrl && (
                                <span className={`group inline-flex items-center gap-3 px-6 py-3 ${FIGTREE} text-sm font-bold uppercase tracking-[0.14em] ${t.button}`}>
                                    {field('span', '', buttonText, (v) => setAttributes({ buttonText: v }), __('Texte du bouton', 'esquare'))}
                                    <span aria-hidden="true">{isExternal(buttonUrl) ? '↗' : '→'}</span>
                                </span>
                            )}
                            {field('p', t.footnote, footnote, (v) => setAttributes({ footnote: v }), __('Note de bas de section (optionnelle)', 'esquare'))}
                        </div>
                    )}
                </div>
            </section>
        </>
    );
}
