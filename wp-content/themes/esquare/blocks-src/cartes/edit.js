import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
    BlockControls,
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    Button,
    PanelBody,
    SelectControl,
    TextareaControl,
    TextControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

import ICONS from '../../assets/icons/cartes.json';

// Class lists mirror render.php so the editor shows the real design.
const GRID = {
    3: 'grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3',
    4: 'grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4',
    6: 'grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6',
};
const ICON_CARD = 'flex flex-col rounded-2xl bg-stone-warm p-6 ring-1 ring-navy/8';
const PHOTO_CARD = 'group flex flex-col overflow-hidden rounded-2xl bg-stone-warm ring-1 ring-navy/8';
const TAG = {
    dark: 'mt-5 inline-flex w-fit items-center gap-1.5 rounded-full bg-navy-deep px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-yellow',
    yellow: 'mt-5 inline-flex w-fit items-center gap-1.5 rounded-full bg-[color-mix(in_oklch,var(--color-yellow)_70%,var(--color-stone-warm))] px-2.5 py-1 font-mono text-[10px] font-semibold uppercase tracking-[0.16em] text-navy-deep',
};
const EMPTY_CARD = { icon: 'ordinateur', tag: '', tagStyle: 'dark', title: '', text: '', imageId: 0, imageUrl: '', imageAlt: '' };

function Icon({ name }) {
    const icon = ICONS[name] ?? ICONS.ordinateur;
    return (
        <span aria-hidden="true" className="flex size-11 items-center justify-center rounded-xl bg-yellow/25 text-navy">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth="1.6" stroke="currentColor" className="size-6">
                <path strokeLinecap="round" strokeLinejoin="round" d={icon.d} />
            </svg>
        </span>
    );
}

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { eyebrow, title, intro, cardStyle, columns, cards } = attributes;
    const blockProps = useBlockProps({ className: 'bg-cream px-6 py-20 lg:px-8 lg:py-24' });
    const isPhoto = cardStyle === 'photo';
    // Cards without a tag keep their title aligned with the tagged ones.
    const hasTags = !isPhoto && cards.some((card) => card.tag);

    // Cards are stored as an array of plain objects; always write a fresh copy.
    const updateCard = (index, changes) =>
        setAttributes({ cards: cards.map((card, i) => (i === index ? { ...card, ...changes } : card)) });
    const moveCard = (index, delta) => {
        const next = [...cards];
        const [card] = next.splice(index, 1);
        next.splice(index + delta, 0, card);
        setAttributes({ cards: next });
    };

    const cardText = (card, index, titleClass) => (
        <>
            <RichText
                tagName="h3"
                className={titleClass}
                value={card.title}
                onChange={(value) => updateCard(index, { title: value })}
                placeholder={__('Titre de la carte', 'esquare')}
                allowedFormats={[]}
            />
            <RichText
                tagName="p"
                className="mt-2 [font-family:var(--font-figtree)] text-pretty text-base text-navy/70"
                value={card.text}
                onChange={(value) => updateCard(index, { text: value })}
                placeholder={__('Texte de la carte', 'esquare')}
                allowedFormats={['core/bold', 'core/italic', 'core/link']}
            />
        </>
    );

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="star-filled"
                        label={__('Cartes à icône', 'esquare')}
                        isPressed={!isPhoto}
                        onClick={() => setAttributes({ cardStyle: 'icon' })}
                    />
                    <ToolbarButton
                        icon="format-image"
                        label={__('Cartes à photo', 'esquare')}
                        isPressed={isPhoto}
                        onClick={() => setAttributes({ cardStyle: 'photo' })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="plus-alt2"
                        label={__('Ajouter une carte', 'esquare')}
                        onClick={() => setAttributes({ cards: [...cards, { ...EMPTY_CARD }] })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Grille', 'esquare')}>
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Cartes par ligne (grand écran)', 'esquare')}
                        value={String(columns)}
                        options={[
                            { value: '3', label: '3' },
                            { value: '4', label: '4' },
                            { value: '6', label: __('6 (cartes compactes)', 'esquare') },
                        ]}
                        onChange={(value) => setAttributes({ columns: Number(value) })}
                    />
                </PanelBody>
                {cards.map((card, index) => (
                    <PanelBody
                        key={index}
                        initialOpen={false}
                        title={sprintf(
                            /* translators: 1: card number, 2: card title. */
                            __('Carte %1$d : %2$s', 'esquare'),
                            index + 1,
                            decodeEntities(card.title) || __('sans titre', 'esquare')
                        )}
                    >
                        {isPhoto ? (
                            <>
                                <MediaUploadCheck>
                                    <MediaUpload
                                        allowedTypes={['image']}
                                        value={card.imageId}
                                        onSelect={(media) =>
                                            updateCard(index, { imageId: media.id, imageUrl: media.url, imageAlt: media.alt ?? '' })
                                        }
                                        render={({ open }) => (
                                            <Button variant="secondary" onClick={open}>
                                                {card.imageUrl ? __('Remplacer la photo', 'esquare') : __('Choisir une photo', 'esquare')}
                                            </Button>
                                        )}
                                    />
                                </MediaUploadCheck>
                                {card.imageUrl && (
                                    <TextareaControl
                                        __nextHasNoMarginBottom
                                        label={__('Texte alternatif', 'esquare')}
                                        value={card.imageAlt}
                                        onChange={(value) => updateCard(index, { imageAlt: value })}
                                    />
                                )}
                            </>
                        ) : (
                            <>
                                <SelectControl
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                    label={__('Icône', 'esquare')}
                                    value={card.icon}
                                    options={Object.entries(ICONS).map(([value, icon]) => ({ value, label: icon.label }))}
                                    onChange={(value) => updateCard(index, { icon: value })}
                                />
                                <TextControl
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                    label={__('Étiquette (optionnelle), ex. « Gratuit »', 'esquare')}
                                    value={card.tag}
                                    onChange={(value) => updateCard(index, { tag: value })}
                                />
                                <SelectControl
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                    label={__('Couleur de l’étiquette', 'esquare')}
                                    value={card.tagStyle}
                                    options={[
                                        { value: 'dark', label: __('Bleu nuit', 'esquare') },
                                        { value: 'yellow', label: __('Jaune (prix)', 'esquare') },
                                    ]}
                                    onChange={(value) => updateCard(index, { tagStyle: value })}
                                />
                            </>
                        )}
                        <p style={{ marginTop: '12px' }}>
                            <Button variant="tertiary" disabled={index === 0} onClick={() => moveCard(index, -1)}>
                                {__('↑ Monter', 'esquare')}
                            </Button>
                            <Button variant="tertiary" disabled={index === cards.length - 1} onClick={() => moveCard(index, 1)}>
                                {__('↓ Descendre', 'esquare')}
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
                            <p className="inline-flex items-center gap-2 [font-family:var(--font-figtree)] text-xs font-bold text-navy/70 uppercase tracking-widest">
                                <span aria-hidden="true" className="inline-block size-2 rounded-full bg-yellow opacity-[0.675]"></span>
                                <RichText
                                    tagName="span"
                                    value={eyebrow}
                                    onChange={(value) => setAttributes({ eyebrow: value })}
                                    placeholder={__('Surtitre', 'esquare')}
                                    allowedFormats={[]}
                                />
                            </p>
                            <RichText
                                tagName="h2"
                                className="mt-3 max-w-[18ch] [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl"
                                value={title}
                                onChange={(value) => setAttributes({ title: value })}
                                placeholder={__('Titre de la section', 'esquare')}
                                allowedFormats={[]}
                            />
                        </div>
                        {(isSelected || intro) && (
                            <RichText
                                tagName="p"
                                className="max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-base text-navy/70 lg:text-lg"
                                value={intro}
                                onChange={(value) => setAttributes({ intro: value })}
                                placeholder={__('Introduction (optionnelle)', 'esquare')}
                                allowedFormats={['core/bold', 'core/italic', 'core/link']}
                            />
                        )}
                    </div>

                    <ul className={`mt-14 ${GRID[columns] ?? GRID[4]}`} role="list">
                        {cards.map((card, index) =>
                            isPhoto ? (
                                <li key={index} className={PHOTO_CARD}>
                                    <div className="aspect-[4/3] overflow-hidden bg-navy/10">
                                        {card.imageUrl && <img src={card.imageUrl} alt={card.imageAlt} className="size-full object-cover" />}
                                    </div>
                                    <div className="flex flex-1 flex-col p-6">
                                        {cardText(card, index, '[font-family:var(--font-figtree)] text-xl font-bold tracking-tight text-navy')}
                                    </div>
                                </li>
                            ) : (
                                <li key={index} className={ICON_CARD}>
                                    <Icon name={card.icon} />
                                    {card.tag && <p className={TAG[card.tagStyle] ?? TAG.dark}>{card.tag}</p>}
                                    {cardText(
                                        card,
                                        index,
                                        `${card.tag ? 'mt-3' : hasTags ? 'mt-[3.25rem]' : 'mt-5'} [font-family:var(--font-figtree)] text-lg font-bold text-navy`
                                    )}
                                </li>
                            )
                        )}
                    </ul>
                </div>
            </section>
        </>
    );
}
