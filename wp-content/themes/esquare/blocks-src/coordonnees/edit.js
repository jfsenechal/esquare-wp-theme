import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { BlockControls, InspectorControls, RichText, URLInput, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, SelectControl, TextControl, ToolbarButton, ToolbarGroup } from '@wordpress/components';

import ICONS from '../../assets/icons/coordonnees.json';

// Class lists mirror render.php so the editor shows the real design.
const FIGTREE = '[font-family:var(--font-figtree)]';
const EYEBROW = `inline-flex items-center gap-2 ${FIGTREE} text-xs font-bold text-navy/70 uppercase tracking-widest`;
const TITLE = `mt-3 max-w-[16ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl`;
const INTRO = `max-w-[52ch] ${FIGTREE} text-pretty text-base text-navy/75 lg:text-lg`;
const CARD = 'rounded-2xl bg-stone-warm p-6 ring-1 ring-navy/8';
const ICON_BOX = 'flex size-10 items-center justify-center rounded-xl bg-yellow/25 text-navy';
const LABEL = 'mt-4 block font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-navy/70';
const UNDERLINE = 'underline decoration-yellow decoration-2 underline-offset-4 transition-colors hover:decoration-navy/40';
const VALUE_LINK = `text-xl font-bold tracking-tight text-navy ${UNDERLINE}`;
const NOTE = 'mt-2 block text-base text-navy/75';
const SECONDARY = `mt-3 flex w-fit items-center gap-2 ${FIGTREE} text-base font-bold text-navy ${UNDERLINE}`;
const GRIDS = { 2: 'sm:grid-cols-2', 4: 'sm:grid-cols-2 lg:grid-cols-4' };
const MAX = 6;

const EMPTY_CARD = { icon: 'adresse', label: '', value: '', url: '', note: '', linkText: '', linkUrl: '' };
const plain = (html) => decodeEntities((html ?? '').replace(/<[^>]*>/g, ' ')).replace(/\s+/g, ' ').trim();

// Same rule as render.php: without a URL, an e-mail or phone value still becomes a link.
const isLinkValue = (value, url) => {
    if (url) {
        return true;
    }
    const text = plain(value);
    if (/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(text)) {
        return true;
    }
    return text.replace(/[^0-9]/g, '').length >= 9 && !/[a-z]/i.test(text);
};

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { eyebrow, title, intro, cards } = attributes;
    const blockProps = useBlockProps({ className: 'bg-cream px-6 py-20 lg:px-8 lg:py-24' });

    // Cards are stored as an array of plain objects; always write a fresh copy.
    const updateCard = (index, changes) =>
        setAttributes({ cards: cards.map((card, i) => (i === index ? { ...card, ...changes } : card)) });
    const moveCard = (index, delta) => {
        const next = [...cards];
        const [card] = next.splice(index, 1);
        next.splice(index + delta, 0, card);
        setAttributes({ cards: next });
    };

    const field = (tagName, className, value, onChange, placeholder, formats = []) => (
        <RichText tagName={tagName} className={className} value={value} onChange={onChange} placeholder={placeholder} allowedFormats={formats} />
    );

    return (
        <>
            {cards.length < MAX && (
                <BlockControls>
                    <ToolbarGroup>
                        <ToolbarButton
                            icon="plus-alt2"
                            label={__('Ajouter une carte', 'esquare')}
                            onClick={() => setAttributes({ cards: [...cards, EMPTY_CARD] })}
                        />
                    </ToolbarGroup>
                </BlockControls>
            )}

            <InspectorControls>
                {cards.map((card, index) => (
                    <PanelBody
                        key={index}
                        initialOpen={false}
                        title={sprintf(
                            /* translators: 1: card number, 2: card label. */
                            __('Carte %1$d : %2$s', 'esquare'),
                            index + 1,
                            plain(card.label) || __('sans intitulé', 'esquare')
                        )}
                    >
                        <SelectControl
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            label={__('Icône', 'esquare')}
                            value={card.icon}
                            options={Object.entries(ICONS).map(([value, icon]) => ({ value, label: icon.label }))}
                            onChange={(value) => updateCard(index, { icon: value })}
                        />
                        <div style={{ marginTop: '12px' }}>
                            <TextControl
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                                label={__('Lien de la valeur', 'esquare')}
                                help={__('Vide = déduit pour un téléphone ou un e-mail, sinon texte simple.', 'esquare')}
                                value={card.url}
                                onChange={(value) => updateCard(index, { url: value.trim() })}
                            />
                        </div>
                        <div style={{ marginTop: '12px' }}>
                            <URLInput
                                className="esquare-url-input"
                                label={__('Lien secondaire (ex. « Voir sur la carte »)', 'esquare')}
                                value={card.linkUrl}
                                onChange={(value) => updateCard(index, { linkUrl: value })}
                            />
                        </div>
                        <p style={{ marginTop: '12px' }}>
                            <Button variant="tertiary" disabled={index === 0} onClick={() => moveCard(index, -1)}>
                                {__('← Avant', 'esquare')}
                            </Button>
                            <Button variant="tertiary" disabled={index === cards.length - 1} onClick={() => moveCard(index, 1)}>
                                {__('Après →', 'esquare')}
                            </Button>
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ cards: cards.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    </PanelBody>
                ))}
            </InspectorControls>

            <section {...blockProps}>
                <div className="mx-auto max-w-7xl">
                    <div className="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
                        <div>
                            <p className={EYEBROW}>
                                <span aria-hidden="true" className="inline-block size-2 rounded-full bg-yellow opacity-[0.675]"></span>
                                {field('span', '', eyebrow, (v) => setAttributes({ eyebrow: v }), __('Surtitre', 'esquare'))}
                            </p>
                            {field('h2', TITLE, title, (v) => setAttributes({ title: v }), __('Titre', 'esquare'))}
                        </div>
                        {(isSelected || intro) &&
                            field('p', INTRO, intro, (v) => setAttributes({ intro: v }), __('Introduction (optionnelle)', 'esquare'), ['core/bold', 'core/italic', 'core/link'])}
                    </div>

                    <dl className={`mt-14 grid grid-cols-1 gap-5 ${GRIDS[cards.length] ?? 'sm:grid-cols-2 lg:grid-cols-3'}`}>
                        {cards.map((card, index) => {
                            const isLink = isLinkValue(card.value, card.url);
                            return (
                                <div key={index} className={CARD}>
                                    <dt className={`${FIGTREE} text-base font-bold text-navy`}>
                                        <span aria-hidden="true" className={ICON_BOX}>
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.6" stroke="currentColor" className="size-5">
                                                <path strokeLinecap="round" strokeLinejoin="round" d={(ICONS[card.icon] ?? ICONS.adresse).d} />
                                            </svg>
                                        </span>
                                        {field('span', LABEL, card.label, (v) => updateCard(index, { label: v }), __('Intitulé', 'esquare'))}
                                    </dt>
                                    <dd className={`mt-2 ${FIGTREE} text-navy`}>
                                        {field(
                                            'span',
                                            isLink ? VALUE_LINK : 'block',
                                            card.value,
                                            (v) => updateCard(index, { value: v }),
                                            __('Valeur (Maj+Entrée pour un retour à la ligne)', 'esquare')
                                        )}
                                        {(isSelected || card.note) &&
                                            field('span', NOTE, card.note, (v) => updateCard(index, { note: v }), __('Note (optionnelle)', 'esquare'))}
                                        {(isSelected || card.linkText) && (
                                            <span className={SECONDARY}>
                                                {field('span', '', card.linkText, (v) => updateCard(index, { linkText: v }), __('Lien secondaire (optionnel)', 'esquare'))}
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            );
                        })}
                    </dl>
                </div>
            </section>
        </>
    );
}
