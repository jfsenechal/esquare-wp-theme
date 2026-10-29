import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
    BlockControls,
    InspectorControls,
    MediaReplaceFlow,
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
const FIGTREE = '[font-family:var(--font-figtree)]';
const ITALIC = '[&_em]:font-serif [&_em]:font-normal [&_em]:italic [&_em]:normal-case [&_em]:tracking-[-0.01em]';
const THEMES = {
    light: {
        section: 'bg-cream px-6 py-20 text-navy lg:px-8 lg:py-24',
        eyebrow: `inline-flex items-center gap-2 ${FIGTREE} text-xs font-bold text-navy/70 uppercase tracking-widest`,
        dot: 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        title: `mt-3 max-w-[17ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl ${ITALIC}`,
        lead: `mt-5 max-w-[54ch] ${FIGTREE} text-pretty text-base text-navy/75 lg:text-lg [&_strong]:font-semibold [&_strong]:text-navy`,
        text: `mt-4 max-w-[54ch] ${FIGTREE} text-pretty text-base text-navy/75 [&_strong]:font-semibold [&_strong]:text-navy`,
        image: 'shadow-[0_18px_40px_-16px_color-mix(in_oklch,var(--color-navy-deep)_22%,transparent)] ring-1 ring-navy/10',
        factRow: 'border-navy/12',
        factLabel: 'text-navy/70',
        factValue: 'text-navy',
        factNote: 'text-navy/70',
        button: 'bg-navy-deep text-yellow hover:bg-navy focus-visible:outline-navy-deep',
        columns: 'divide-navy/12 border-navy/12',
        columnLabel: 'text-navy/70',
        columnTitle: 'text-navy',
        columnText: 'text-navy/75',
        columnLink: 'text-navy hover:decoration-navy/40',
    },
    dark: {
        section: 'bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        eyebrow: 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow',
        dot: 'inline-block size-1.5 rounded-full bg-yellow',
        title: `mt-4 max-w-[16ch] ${FIGTREE} text-3xl font-black uppercase leading-[0.95] tracking-[-0.03em] text-balance md:text-4xl lg:text-5xl ${ITALIC} [&_em]:text-yellow`,
        lead: `mt-5 max-w-[46ch] ${FIGTREE} text-pretty text-lg text-white/75 [&_strong]:font-semibold [&_strong]:text-white`,
        text: `mt-4 max-w-[46ch] ${FIGTREE} text-pretty text-base text-white/75 [&_strong]:font-semibold [&_strong]:text-white`,
        image: 'shadow-[0_30px_60px_-20px_color-mix(in_oklch,var(--color-navy-deep)_70%,transparent)] ring-1 ring-white/10',
        factRow: 'border-white/15',
        factLabel: 'text-yellow',
        factValue: 'text-white',
        factNote: 'text-white/70',
        button: 'bg-yellow text-navy-deep hover:bg-yellow-soft focus-visible:outline-yellow',
        columns: 'divide-white/15 border-white/15',
        columnLabel: 'text-yellow',
        columnTitle: 'text-white',
        columnText: 'text-white/75',
        columnLink: 'text-white hover:decoration-white/40',
    },
};
const RATIOS = { '16/10': 'aspect-[16/10]', '4/3': 'aspect-[4/3]', '4/5': 'aspect-[4/5]' };
const COLUMN_GRID = { 1: 'lg:grid-cols-1', 2: 'lg:grid-cols-2', 3: 'lg:grid-cols-3', 4: 'lg:grid-cols-4' };
const FACT_LINK = '[&_a]:font-semibold [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4';

const isExternal = (url) => /^https?:\/\//i.test(url ?? '') && !(url ?? '').includes(window.location.host);

export default function Edit({ attributes, setAttributes, isSelected }) {
    const {
        theme, imagePosition, imageRatio, imageId, imageUrl, imageAlt,
        eyebrow, title, lead, text, facts, buttonText, buttonUrl, columns,
    } = attributes;
    const t = THEMES[theme] ?? THEMES.light;
    const blockProps = useBlockProps({ className: t.section });
    const hasFacts = facts.length > 0;

    // Lists are stored as arrays of plain objects; always write a fresh copy.
    const updateItem = (list, index, key, value) =>
        setAttributes({ [list]: attributes[list].map((item, i) => (i === index ? { ...item, [key]: value } : item)) });
    const removeItem = (list, index) => setAttributes({ [list]: attributes[list].filter((_, i) => i !== index) });

    const onSelectImage = (media) => setAttributes({ imageId: media.id, imageUrl: media.url, imageAlt: media.alt ?? '' });

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="align-pull-left"
                        label={__('Image à gauche', 'esquare')}
                        isPressed={imagePosition === 'left'}
                        onClick={() => setAttributes({ imagePosition: 'left' })}
                    />
                    <ToolbarButton
                        icon="align-pull-right"
                        label={__('Image à droite', 'esquare')}
                        isPressed={imagePosition === 'right'}
                        onClick={() => setAttributes({ imagePosition: 'right' })}
                    />
                </ToolbarGroup>
                <MediaReplaceFlow
                    mediaId={imageId}
                    mediaURL={imageUrl}
                    allowedTypes={['image']}
                    accept="image/*"
                    onSelect={onSelectImage}
                    name={imageUrl ? __('Remplacer la photo', 'esquare') : __('Ajouter une photo', 'esquare')}
                />
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
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Format de la photo', 'esquare')}
                        value={imageRatio}
                        options={[
                            { value: '16/10', label: __('Paysage large (16:10)', 'esquare') },
                            { value: '4/3', label: __('Paysage (4:3)', 'esquare') },
                            { value: '4/5', label: __('Portrait (4:5)', 'esquare') },
                        ]}
                        onChange={(value) => setAttributes({ imageRatio: value })}
                    />
                    {imageUrl && (
                        <TextareaControl
                            __nextHasNoMarginBottom
                            label={__('Texte alternatif de la photo', 'esquare')}
                            value={imageAlt}
                            onChange={(value) => setAttributes({ imageAlt: value })}
                        />
                    )}
                </PanelBody>
                <PanelBody title={__('Bouton', 'esquare')} initialOpen={false}>
                    <URLInput
                        className="esquare-url-input"
                        label={__('Lien du bouton (vide = pas de bouton)', 'esquare')}
                        value={buttonUrl}
                        onChange={(value) => setAttributes({ buttonUrl: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Fiche (intitulé / valeur)', 'esquare')} initialOpen={false}>
                    {facts.map((fact, index) => (
                        <p key={index}>
                            <strong>{decodeEntities(fact.label) || __('Ligne sans intitulé', 'esquare')}</strong>{' '}
                            <Button variant="link" isDestructive onClick={() => removeItem('facts', index)}>
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    ))}
                    <Button
                        variant="secondary"
                        onClick={() => setAttributes({ facts: [...facts, { label: '', value: '', note: '' }] })}
                    >
                        {__('Ajouter une ligne', 'esquare')}
                    </Button>
                </PanelBody>
                <PanelBody title={__('Colonnes sous la section', 'esquare')} initialOpen={false}>
                    {columns.map((column, index) => (
                        <div key={index} style={{ marginBottom: '16px' }}>
                            <URLInput
                                className="esquare-url-input"
                                label={sprintf(
                                    /* translators: %s: column title. */
                                    __('Lien de « %s »', 'esquare'),
                                    decodeEntities(column.title) || __('colonne sans titre', 'esquare')
                                )}
                                value={column.linkUrl}
                                onChange={(value) => updateItem('columns', index, 'linkUrl', value)}
                            />
                            <Button variant="link" isDestructive onClick={() => removeItem('columns', index)}>
                                {__('Retirer cette colonne', 'esquare')}
                            </Button>
                        </div>
                    ))}
                    {columns.length < 4 && (
                        <Button
                            variant="secondary"
                            onClick={() =>
                                setAttributes({ columns: [...columns, { label: '', title: '', text: '', linkText: '', linkUrl: '' }] })
                            }
                        >
                            {__('Ajouter une colonne', 'esquare')}
                        </Button>
                    )}
                </PanelBody>
            </InspectorControls>

            <section {...blockProps}>
                <div className="mx-auto max-w-7xl">
                    <div className={`grid grid-cols-1 gap-x-14 gap-y-10 lg:grid-cols-2 ${hasFacts ? 'items-start' : 'items-center'}`}>
                        <div className={`order-2 ${imagePosition === 'left' ? 'lg:order-1' : ''} ${hasFacts ? 'lg:sticky lg:top-24' : ''}`}>
                            {imageUrl ? (
                                <img src={imageUrl} alt={imageAlt} className={`${RATIOS[imageRatio] ?? RATIOS['4/3']} w-full rounded-2xl object-cover ${t.image}`} />
                            ) : (
                                <div className={`${RATIOS[imageRatio] ?? RATIOS['4/3']} flex w-full items-center justify-center rounded-2xl bg-navy/10`}>
                                    <MediaReplaceFlow
                                        allowedTypes={['image']}
                                        accept="image/*"
                                        onSelect={onSelectImage}
                                        name={__('Ajouter une photo', 'esquare')}
                                    />
                                </div>
                            )}
                        </div>
                        <div className={`order-1 ${imagePosition === 'left' ? 'lg:order-2' : ''}`}>
                            {(isSelected || eyebrow) && (
                                <p className={t.eyebrow}>
                                    <span aria-hidden="true" className={t.dot}></span>
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
                                className={t.title}
                                value={title}
                                onChange={(value) => setAttributes({ title: value })}
                                placeholder={__('Titre', 'esquare')}
                                allowedFormats={['core/italic']}
                            />
                            <RichText
                                tagName="p"
                                className={t.lead}
                                value={lead}
                                onChange={(value) => setAttributes({ lead: value })}
                                placeholder={__('Texte principal', 'esquare')}
                                allowedFormats={['core/bold', 'core/italic', 'core/link']}
                            />
                            {(isSelected || text) && (
                                <RichText
                                    tagName="p"
                                    className={t.text}
                                    value={text}
                                    onChange={(value) => setAttributes({ text: value })}
                                    placeholder={__('Second paragraphe (optionnel)', 'esquare')}
                                    allowedFormats={['core/bold', 'core/italic', 'core/link']}
                                />
                            )}
                            {hasFacts && (
                                <dl className={`mt-10 ${FIGTREE}`}>
                                    {facts.map((fact, index) => (
                                        <div
                                            key={index}
                                            className={`grid grid-cols-1 gap-x-8 gap-y-1 border-t py-5 sm:grid-cols-[9rem_minmax(0,1fr)] ${t.factRow}${index === facts.length - 1 ? ' border-b' : ''}`}
                                        >
                                            <RichText
                                                tagName="dt"
                                                className={`font-mono text-[11px] font-semibold uppercase tracking-[0.18em] ${t.factLabel}`}
                                                value={fact.label}
                                                onChange={(value) => updateItem('facts', index, 'label', value)}
                                                placeholder={__('Intitulé', 'esquare')}
                                                allowedFormats={[]}
                                            />
                                            <dd className={`${t.factValue} ${FACT_LINK}`}>
                                                <RichText
                                                    tagName="span"
                                                    value={fact.value}
                                                    onChange={(value) => updateItem('facts', index, 'value', value)}
                                                    placeholder={__('Valeur', 'esquare')}
                                                    allowedFormats={['core/bold', 'core/link']}
                                                />
                                                {(isSelected || fact.note) && (
                                                    <>
                                                        {' '}
                                                        <RichText
                                                            tagName="span"
                                                            className={t.factNote}
                                                            value={fact.note}
                                                            onChange={(value) => updateItem('facts', index, 'note', value)}
                                                            placeholder={__('Précision (optionnelle)', 'esquare')}
                                                            allowedFormats={[]}
                                                        />
                                                    </>
                                                )}
                                            </dd>
                                        </div>
                                    ))}
                                </dl>
                            )}
                            {(buttonUrl || (isSelected && buttonText)) && (
                                <div className="mt-8">
                                    <span className={`group inline-flex items-center gap-3 px-6 py-3 ${FIGTREE} text-sm font-bold uppercase tracking-[0.14em] ${t.button}`}>
                                        <RichText
                                            tagName="span"
                                            value={buttonText}
                                            onChange={(value) => setAttributes({ buttonText: value })}
                                            placeholder={__('Texte du bouton', 'esquare')}
                                            allowedFormats={[]}
                                        />
                                        <span aria-hidden="true">{isExternal(buttonUrl) ? '↗' : '→'}</span>
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>

                    {columns.length > 0 && (
                        <div className={`mt-16 grid grid-cols-1 divide-y border-t lg:mt-20 lg:divide-x lg:divide-y-0 ${COLUMN_GRID[columns.length] ?? COLUMN_GRID[3]} ${t.columns}`}>
                            {columns.map((column, index) => (
                                <div key={index} className="py-8 last:border-b lg:px-10 lg:py-9 lg:first:pl-0 lg:last:border-b-0 lg:last:pr-0 border-inherit">
                                    <RichText
                                        tagName="p"
                                        className={`font-mono text-[11px] font-semibold uppercase tracking-[0.18em] ${t.columnLabel}`}
                                        value={column.label}
                                        onChange={(value) => updateItem('columns', index, 'label', value)}
                                        placeholder={__('Petit intitulé', 'esquare')}
                                        allowedFormats={[]}
                                    />
                                    <RichText
                                        tagName="h3"
                                        className={`mt-3 ${FIGTREE} text-2xl font-bold tracking-tight ${t.columnTitle}`}
                                        value={column.title}
                                        onChange={(value) => updateItem('columns', index, 'title', value)}
                                        placeholder={__('Titre de la colonne', 'esquare')}
                                        allowedFormats={[]}
                                    />
                                    <RichText
                                        tagName="p"
                                        className={`mt-3 max-w-[42ch] ${FIGTREE} text-pretty ${t.columnText}`}
                                        value={column.text}
                                        onChange={(value) => updateItem('columns', index, 'text', value)}
                                        placeholder={__('Texte', 'esquare')}
                                        allowedFormats={['core/bold', 'core/italic']}
                                    />
                                    {(isSelected || column.linkText) && (
                                        <span className={`group mt-5 inline-flex items-center gap-2 ${FIGTREE} text-sm font-bold uppercase tracking-[0.14em] underline decoration-yellow decoration-2 underline-offset-[6px] ${t.columnLink}`}>
                                            <RichText
                                                tagName="span"
                                                value={column.linkText}
                                                onChange={(value) => updateItem('columns', index, 'linkText', value)}
                                                placeholder={__('Texte du lien', 'esquare')}
                                                allowedFormats={[]}
                                            />
                                            <span aria-hidden="true">{isExternal(column.linkUrl) ? '↗' : '→'}</span>
                                        </span>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </>
    );
}
