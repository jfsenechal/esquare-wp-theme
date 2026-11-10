import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
    BlockControls,
    InspectorControls,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    URLInput,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    Button,
    PanelBody,
    SelectControl,
    TextareaControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const BUTTON_BASE =
    'group inline-flex items-center gap-2 px-6 py-3 [font-family:var(--font-figtree)] text-sm font-bold uppercase tracking-[0.14em] transition-colors';
const ITALIC = '[&_em]:font-serif [&_em]:font-normal [&_em]:italic [&_em]:normal-case [&_em]:tracking-[-0.01em]';
const VARIANTS = {
    band: {
        label: __('Bande bleu nuit', 'esquare'),
        icon: 'align-wide',
        section: 'bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        inner: 'mx-auto flex max-w-7xl flex-col items-center gap-8 text-center lg:flex-row lg:justify-between lg:gap-12 lg:text-left',
        eyebrow: 'font-mono text-xs tracking-[0.3em] text-yellow uppercase',
        title: `mt-3 max-w-[22ch] [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-balance md:text-4xl ${ITALIC}`,
        text: 'mt-4 max-w-[52ch] [font-family:var(--font-figtree)] text-pretty text-lg text-white/70',
        actions: '',
        row: 'flex flex-wrap items-center justify-center gap-3',
        primary: `${BUTTON_BASE} rounded-md bg-yellow text-navy hover:bg-yellow-soft`,
        secondary: `${BUTTON_BASE} rounded-md bg-white/5 text-white ring-1 ring-white/15 hover:bg-white/10`,
        note: 'mt-4 [font-family:var(--font-figtree)] text-base text-white/70',
    },
    poster: {
        label: __('Panneau jaune', 'esquare'),
        icon: 'format-aside',
        section: 'px-6 py-20 lg:px-8 lg:py-24',
        inner: 'relative isolate grid grid-cols-1 gap-y-8 px-8 py-12 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:gap-x-12 lg:px-12 lg:py-14',
        eyebrow: 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy-deep',
        title: `mt-4 max-w-[20ch] [font-family:var(--font-figtree)] text-3xl font-black uppercase leading-[1] tracking-[-0.03em] text-balance text-navy-deep md:text-4xl ${ITALIC}`,
        text: 'mt-4 max-w-[46ch] [font-family:var(--font-figtree)] text-pretty text-navy-deep/80',
        actions: 'lg:justify-self-end',
        row: 'flex flex-wrap items-center gap-3',
        primary: `${BUTTON_BASE} bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-navy-deep`,
        secondary: `${BUTTON_BASE} bg-navy-deep/10 text-navy-deep ring-1 ring-navy-deep/25 hover:bg-navy-deep/20`,
        note: 'mt-4 max-w-[34ch] [font-family:var(--font-figtree)] text-base font-semibold text-navy-deep/70',
    },
    card: {
        label: __('Carte blanche avec logo', 'esquare'),
        icon: 'id-alt',
        section: 'bg-stone-warm px-6 py-16 lg:px-8 lg:py-20',
        inner: 'mx-auto flex max-w-7xl flex-col items-center gap-8 rounded-3xl bg-white p-10 text-center ring-1 ring-navy/8 lg:flex-row lg:gap-12 lg:p-12 lg:text-left',
        eyebrow: 'font-mono text-xs tracking-[0.3em] text-navy/70 uppercase',
        title: `[font-family:var(--font-figtree)] text-2xl font-bold tracking-tight text-balance text-navy md:text-3xl ${ITALIC}`,
        text: 'mt-3 max-w-[60ch] [font-family:var(--font-figtree)] text-pretty text-navy/70',
        actions: 'shrink-0',
        row: 'flex flex-wrap items-center justify-center gap-3',
        primary: `${BUTTON_BASE} bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-navy-deep`,
        secondary: `${BUTTON_BASE} bg-navy/5 text-navy ring-1 ring-navy/15 hover:bg-navy/10`,
        note: 'mt-3 [font-family:var(--font-figtree)] text-base text-navy/70',
    },
};

const isExternal = (url) => /^https?:\/\//i.test(url ?? '') && !(url ?? '').includes(window.location.host);

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { variant, eyebrow, title, text, buttons, note, logoId, logoUrl, logoAlt } = attributes;
    const v = VARIANTS[variant] ?? VARIANTS.band;
    const blockProps = useBlockProps({ className: v.section });

    // Buttons are stored as an array of plain objects; always write a fresh copy.
    const updateButton = (index, key, value) =>
        setAttributes({ buttons: buttons.map((b, i) => (i === index ? { ...b, [key]: value } : b)) });

    const content = (
        <>
            {variant === 'card' && logoUrl && <img src={logoUrl} alt={logoAlt} className="h-16 w-auto shrink-0" />}
            <div className={variant === 'poster' ? '' : 'lg:flex-1'}>
                {(isSelected || eyebrow) && (
                    <p className={v.eyebrow}>
                        {variant === 'poster' && (
                            <span aria-hidden="true" className="inline-block size-1.5 rounded-full bg-navy-deep"></span>
                        )}
                        <RichText
                            tagName="span"
                            value={eyebrow}
                            onChange={(value) => setAttributes({ eyebrow: value })}
                            placeholder={__('Surtitre (optionnel)', 'esquare')}
                            allowedFormats={[]}
                        />
                    </p>
                )}
                <RichText
                    tagName="h2"
                    className={variant === 'card' && eyebrow ? `mt-3 ${v.title}` : v.title}
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                    placeholder={__('Titre', 'esquare')}
                    allowedFormats={['core/italic']}
                />
                <RichText
                    tagName="p"
                    className={v.text}
                    value={text}
                    onChange={(value) => setAttributes({ text: value })}
                    placeholder={__('Texte', 'esquare')}
                    allowedFormats={['core/bold', 'core/italic', 'core/link']}
                />
            </div>
            <div className={v.actions}>
                <div className={v.row}>
                    {buttons.map((button, index) => (
                        <span key={index} className={index === 0 ? v.primary : v.secondary}>
                            <RichText
                                tagName="span"
                                value={button.text}
                                onChange={(value) => updateButton(index, 'text', value)}
                                placeholder={__('Texte du bouton', 'esquare')}
                                allowedFormats={[]}
                            />
                            {isExternal(button.url) && <span aria-hidden="true">↗</span>}
                        </span>
                    ))}
                </div>
                {(isSelected || note) && (
                    <RichText
                        tagName="p"
                        className={v.note}
                        value={note}
                        onChange={(value) => setAttributes({ note: value })}
                        placeholder={__('Précision sous les boutons (optionnelle)', 'esquare')}
                        allowedFormats={[]}
                    />
                )}
            </div>
        </>
    );

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    {Object.entries(VARIANTS).map(([key, { label, icon }]) => (
                        <ToolbarButton
                            key={key}
                            icon={icon}
                            label={label}
                            isPressed={variant === key}
                            onClick={() => setAttributes({ variant: key })}
                        />
                    ))}
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="button"
                        label={__('Ajouter un bouton', 'esquare')}
                        onClick={() => setAttributes({ buttons: [...buttons, { text: '', url: '' }] })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Style', 'esquare')}>
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Présentation', 'esquare')}
                        value={variant}
                        options={Object.entries(VARIANTS).map(([value, { label }]) => ({ value, label }))}
                        onChange={(value) => setAttributes({ variant: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Boutons', 'esquare')}>
                    {buttons.map((button, index) => (
                        <div key={index} style={{ marginBottom: '16px' }}>
                            <URLInput
                                className="esquare-url-input"
                                label={sprintf(
                                    /* translators: %s: button text. */
                                    __('Lien de « %s »', 'esquare'),
                                    decodeEntities(button.text) || __('bouton sans texte', 'esquare')
                                )}
                                value={button.url}
                                onChange={(value) => updateButton(index, 'url', value)}
                            />
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ buttons: buttons.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer ce bouton', 'esquare')}
                            </Button>
                        </div>
                    ))}
                    <p>
                        {__('Le premier bouton est mis en avant. Un lien vers un autre site s’ouvre dans un nouvel onglet. « tel:+3284327054 » lance un appel.', 'esquare')}
                    </p>
                </PanelBody>
                {variant === 'card' && (
                    <PanelBody title={__('Logo', 'esquare')}>
                        <MediaUploadCheck>
                            <MediaUpload
                                allowedTypes={['image']}
                                value={logoId}
                                onSelect={(media) =>
                                    setAttributes({ logoId: media.id, logoUrl: media.url, logoAlt: media.alt ?? '' })
                                }
                                render={({ open }) => (
                                    <Button variant="secondary" onClick={open}>
                                        {logoUrl ? __('Remplacer le logo', 'esquare') : __('Choisir un logo', 'esquare')}
                                    </Button>
                                )}
                            />
                        </MediaUploadCheck>
                        {logoUrl && (
                            <>
                                <TextareaControl
                                    __nextHasNoMarginBottom
                                    label={__('Texte alternatif', 'esquare')}
                                    value={logoAlt}
                                    onChange={(value) => setAttributes({ logoAlt: value })}
                                />
                                <Button
                                    variant="link"
                                    isDestructive
                                    onClick={() => setAttributes({ logoId: 0, logoUrl: '', logoAlt: '' })}
                                >
                                    {__('Retirer le logo', 'esquare')}
                                </Button>
                            </>
                        )}
                    </PanelBody>
                )}
            </InspectorControls>

            <section {...blockProps}>
                {variant === 'poster' ? (
                    <div className="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-yellow ring-1 ring-navy/10">
                        <div className={v.inner}>
                            <div aria-hidden="true" className="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
                            {content}
                        </div>
                    </div>
                ) : (
                    <div className={v.inner}>{content}</div>
                )}
            </section>
        </>
    );
}
