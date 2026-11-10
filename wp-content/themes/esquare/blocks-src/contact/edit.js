import { __, sprintf } from '@wordpress/i18n';
import {
    BlockControls,
    InspectorControls,
    RichText,
    URLInput,
    useBlockProps,
} from '@wordpress/block-editor';
import { Button, PanelBody, ToolbarButton, ToolbarGroup } from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const PRIMARY_BUTTON =
    'inline-flex items-center gap-2 rounded-md bg-yellow px-5 py-2.5 [font-family:var(--font-figtree)] text-sm font-semibold text-navy';
const SECONDARY_BUTTON =
    'inline-flex items-center gap-2 rounded-md bg-white/5 px-5 py-2.5 [font-family:var(--font-figtree)] text-sm font-semibold text-white ring-1 ring-white/15';
const CARD = 'rounded-lg bg-white/5 p-5 ring-1 ring-white/10';

function FacebookIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="size-4">
            <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.52 1.49-3.91 3.78-3.91 1.1 0 2.24.2 2.24.2v2.47h-1.26c-1.24 0-1.63.78-1.63 1.57v1.88h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94Z" />
        </svg>
    );
}

const isFacebook = (url) => /^https?:\/\/([a-z0-9-]+\.)*facebook\.com\//i.test(url ?? '');

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { eyebrow, title, intro, buttons, cards } = attributes;
    const blockProps = useBlockProps({ className: 'bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24' });

    // Lists are stored as arrays of plain objects; always write a fresh copy.
    const updateItem = (list, index, key, value) =>
        setAttributes({
            [list]: attributes[list].map((item, i) => (i === index ? { ...item, [key]: value } : item)),
        });
    const removeItem = (list, index) =>
        setAttributes({ [list]: attributes[list].filter((_, i) => i !== index) });

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="button"
                        label={__('Ajouter un bouton', 'esquare')}
                        onClick={() => setAttributes({ buttons: [...buttons, { text: '', url: '' }] })}
                    />
                    <ToolbarButton
                        icon="index-card"
                        label={__('Ajouter une carte', 'esquare')}
                        onClick={() => setAttributes({ cards: [...cards, { label: '', value: '', note: '' }] })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Boutons', 'esquare')}>
                    {buttons.map((button, index) => (
                        <div key={index} style={{ marginBottom: '16px' }}>
                            <URLInput
                                className="esquare-url-input"
                                label={sprintf(
                                    /* translators: %s: button text. */
                                    __('Lien de « %s »', 'esquare'),
                                    button.text || __('bouton sans texte', 'esquare')
                                )}
                                value={button.url}
                                onChange={(value) => updateItem('buttons', index, 'url', value)}
                            />
                            <Button variant="link" isDestructive onClick={() => removeItem('buttons', index)}>
                                {__('Retirer ce bouton', 'esquare')}
                            </Button>
                        </div>
                    ))}
                    <p>{__('Astuce : « mailto:adresse@esquare.be » ouvre un e-mail, « tel:+3284327054 » lance un appel.', 'esquare')}</p>
                </PanelBody>
                <PanelBody title={__('Cartes d’informations', 'esquare')}>
                    {cards.map((card, index) => (
                        <p key={index}>
                            <strong>{card.label || __('Carte sans titre', 'esquare')}</strong>{' '}
                            <Button variant="link" isDestructive onClick={() => removeItem('cards', index)}>
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    ))}
                    <p>{__('La première carte prend toute la largeur.', 'esquare')}</p>
                </PanelBody>
            </InspectorControls>

            <section {...blockProps}>
                <div className="mx-auto max-w-7xl">
                    <div className="grid items-start gap-10 lg:grid-cols-2">
                        <div>
                            <RichText
                                tagName="p"
                                className="font-mono text-xs tracking-[0.3em] text-yellow uppercase"
                                value={eyebrow}
                                onChange={(value) => setAttributes({ eyebrow: value })}
                                placeholder={__('Surtitre', 'esquare')}
                                allowedFormats={[]}
                            />
                            <RichText
                                tagName="h2"
                                className="mt-3 max-w-[18ch] [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance md:text-4xl lg:text-5xl"
                                value={title}
                                onChange={(value) => setAttributes({ title: value })}
                                placeholder={__('Titre de la section', 'esquare')}
                                allowedFormats={[]}
                            />
                            {(isSelected || intro) && (
                                <RichText
                                    tagName="p"
                                    className="mt-5 max-w-[46ch] [font-family:var(--font-figtree)] text-pretty text-lg text-white/70"
                                    value={intro}
                                    onChange={(value) => setAttributes({ intro: value })}
                                    placeholder={__('Introduction', 'esquare')}
                                    allowedFormats={['core/bold', 'core/italic', 'core/link']}
                                />
                            )}
                            {buttons.length > 0 && (
                                <div className="mt-8 flex flex-wrap gap-3">
                                    {buttons.map((button, index) => (
                                        <span key={index} className={index === 0 ? PRIMARY_BUTTON : SECONDARY_BUTTON}>
                                            {isFacebook(button.url) && <FacebookIcon />}
                                            <RichText
                                                tagName="span"
                                                value={button.text}
                                                onChange={(value) => updateItem('buttons', index, 'text', value)}
                                                placeholder={__('Texte du bouton', 'esquare')}
                                                allowedFormats={[]}
                                            />
                                        </span>
                                    ))}
                                </div>
                            )}
                        </div>

                        <dl className="grid grid-cols-1 gap-4 [font-family:var(--font-figtree)] text-sm sm:grid-cols-2">
                            {cards.map((card, index) => (
                                <div key={index} className={index === 0 ? `${CARD} sm:col-span-2` : CARD}>
                                    <RichText
                                        tagName="dt"
                                        className="font-mono text-xs tracking-widest text-yellow uppercase"
                                        value={card.label}
                                        onChange={(value) => updateItem('cards', index, 'label', value)}
                                        placeholder={__('Intitulé', 'esquare')}
                                        allowedFormats={[]}
                                    />
                                    <dd className={`mt-2 font-semibold text-white${card.value?.includes('@') ? ' break-all' : ''}`}>
                                        <RichText
                                            tagName="span"
                                            value={card.value}
                                            onChange={(value) => updateItem('cards', index, 'value', value)}
                                            placeholder={__('Information', 'esquare')}
                                            allowedFormats={[]}
                                        />
                                        {(isSelected || card.note) && (
                                            <RichText
                                                tagName="span"
                                                className="mt-1 block font-normal text-white/65"
                                                value={card.note}
                                                onChange={(value) => updateItem('cards', index, 'note', value)}
                                                placeholder={__('Précision (optionnelle)', 'esquare')}
                                                allowedFormats={[]}
                                            />
                                        )}
                                    </dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                </div>
            </section>
        </>
    );
}
