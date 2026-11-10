import { __ } from '@wordpress/i18n';
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
    FocalPointPicker,
    PanelBody,
    SelectControl,
    TextareaControl,
    TextControl,
    ToggleControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const SECTION = {
    cover: 'relative isolate flex min-h-[62vh] items-end overflow-hidden bg-navy-deep text-white',
    split: 'relative isolate overflow-hidden bg-navy-deep text-white',
};
const SPLIT_IMAGE =
    'w-full rounded-2xl object-cover shadow-[0_30px_60px_-20px_color-mix(in_oklch,var(--color-navy-deep)_60%,transparent)] ring-1 ring-white/10';
const RATIO = { '16/10': 'aspect-[16/10]', '4/3': 'aspect-[4/3]', '4/5': 'aspect-[4/5]' };
const COVER_GRADIENT =
    'linear-gradient(180deg, color-mix(in oklch, var(--color-navy-deep) 18%, transparent) 0%, color-mix(in oklch, var(--color-navy-deep) 68%, transparent) 52%, color-mix(in oklch, var(--color-navy-deep) 96%, transparent) 100%)';
const SPLIT_GLOW =
    'radial-gradient(120% 80% at 100% 0%, color-mix(in oklch, var(--color-navy-soft) 85%, transparent), transparent 58%)';
const PRIMARY_BUTTON =
    'group inline-flex items-center gap-3 bg-yellow px-6 py-3 [font-family:var(--font-figtree)] text-sm font-bold uppercase tracking-[0.14em] text-navy-deep';
const SECONDARY_LINK =
    'inline-flex min-h-[44px] items-center px-2 [font-family:var(--font-figtree)] text-sm font-bold uppercase tracking-[0.14em] text-white underline decoration-yellow decoration-[3px] underline-offset-[7px]';
const BADGE =
    'inline-flex items-center gap-2 rounded-full bg-white/10 px-4 py-2 [font-family:var(--font-figtree)] text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur-sm';
const CHIP =
    'inline-flex items-center gap-1.5 rounded-full bg-white/5 px-3.5 py-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.16em] text-white/85 ring-1 ring-white/15';

// Heroicons (solid), same paths as render.php.
const ICONS = {
    calendar:
        'M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z',
    lock: 'M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z',
    check: 'M8.603 3.799A4.49 4.49 0 0 1 12 2.25c1.357 0 2.573.6 3.397 1.549a4.49 4.49 0 0 1 3.498 1.307 4.491 4.491 0 0 1 1.307 3.497A4.49 4.49 0 0 1 21.75 12a4.49 4.49 0 0 1-1.549 3.397 4.491 4.491 0 0 1-1.307 3.497 4.491 4.491 0 0 1-3.497 1.307A4.49 4.49 0 0 1 12 21.75a4.49 4.49 0 0 1-3.397-1.549 4.49 4.49 0 0 1-3.498-1.306 4.491 4.491 0 0 1-1.307-3.498A4.49 4.49 0 0 1 2.25 12c0-1.357.6-2.573 1.549-3.397a4.49 4.49 0 0 1 1.307-3.497 4.49 4.49 0 0 1 3.497-1.307Zm7.007 6.387a.75.75 0 1 0-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 0 0-1.06 1.06l2.25 2.25a.75.75 0 0 0 1.14-.094l3.75-5.25Z',
    people:
        'M4.5 6.375a4.125 4.125 0 1 1 8.25 0 4.125 4.125 0 0 1-8.25 0ZM14.25 8.625a3.375 3.375 0 1 1 6.75 0 3.375 3.375 0 0 1-6.75 0ZM1.5 19.125a7.125 7.125 0 0 1 14.25 0v.003l-.001.119a.75.75 0 0 1-.363.63 13.067 13.067 0 0 1-6.761 1.873c-2.472 0-4.786-.684-6.76-1.873a.75.75 0 0 1-.364-.63l-.001-.122ZM17.25 19.128l-.001.144a2.25 2.25 0 0 1-.233.96 10.088 10.088 0 0 0 5.06-1.01.75.75 0 0 0 .42-.643 4.875 4.875 0 0 0-6.957-4.611 8.586 8.586 0 0 1 1.71 5.157v.003Z',
};

function Icon({ name, className }) {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className={className}>
            <path fillRule="evenodd" d={ICONS[name]} clipRule="evenodd" />
        </svg>
    );
}

const isExternal = (url) => /^https?:\/\//i.test(url ?? '') && !(url ?? '').startsWith(window.location.origin);

export default function Edit({ attributes, setAttributes, isSelected }) {
    const {
        layout,
        eyebrow,
        title,
        titleAccent,
        intro,
        primaryText,
        primaryUrl,
        primaryArrow,
        secondaryType,
        secondaryText,
        secondaryUrl,
        badge,
        badgeIcon,
        chips,
        backText,
        backUrl,
        imageId,
        imageUrl,
        imageAlt,
        imageRatio,
        focalPoint,
    } = attributes;

    const isCover = layout !== 'split';
    const blockProps = useBlockProps({ className: SECTION[isCover ? 'cover' : 'split'] });
    // Optional parts stay visible while editing so they can be filled in.
    const show = (value) => isSelected || (value ?? '') !== '';
    const arrow = primaryArrow === 'down' ? '↓' : isExternal(primaryUrl) ? '↗' : '→';

    // Chips are stored as an array of plain objects; always write a fresh copy.
    const updateChip = (index, changes) =>
        setAttributes({ chips: chips.map((chip, i) => (i === index ? { ...chip, ...changes } : chip)) });

    const onSelectImage = (media) =>
        setAttributes({ imageId: media.id, imageUrl: media.url, imageAlt: media.alt ?? '' });

    const image = imageUrl ? (
        <img
            src={imageUrl}
            alt={imageAlt}
            className={isCover ? 'absolute inset-0 -z-20 size-full object-cover' : `${RATIO[imageRatio] ?? RATIO['16/10']} ${SPLIT_IMAGE}`}
            style={{ objectPosition: `${focalPoint.x * 100}% ${focalPoint.y * 100}%` }}
        />
    ) : (
        !isCover && (
            <div className={`flex ${RATIO[imageRatio] ?? RATIO['16/10']} w-full items-center justify-center rounded-2xl bg-white/5 text-white/60 ring-1 ring-white/10`}>
                {__('Choisissez une photo via « Ajouter une photo » dans la barre d’outils.', 'esquare')}
            </div>
        )
    );

    const content = (
        <>
            {backText && (
                <span className="group flex w-fit items-center gap-2 [font-family:var(--font-figtree)] text-sm font-semibold text-white/70">
                    <span aria-hidden="true">←</span>
                    {backText}
                </span>
            )}

            {show(eyebrow) && (
                <p className={`${backText ? 'mt-6 ' : ''}${isCover ? 'rounded-full bg-navy-deep/85 px-3.5 py-1.5 ring-1 ring-white/10 ' : ''}inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow`}>
                    <span aria-hidden="true" className="inline-block size-1.5 rounded-full bg-yellow"></span>
                    <RichText
                        tagName="span"
                        value={eyebrow}
                        onChange={(value) => setAttributes({ eyebrow: value })}
                        placeholder={__('Surtitre', 'esquare')}
                        allowedFormats={[]}
                    />
                </p>
            )}

            <h1
                className={`mt-5 max-w-[15ch] [font-family:var(--font-figtree)] font-black uppercase ${isCover ? 'leading-[0.92]' : 'leading-[0.94]'} tracking-[-0.04em] text-balance`}
                style={{ fontSize: isCover ? 'clamp(2.5rem, 6vw, 5rem)' : 'clamp(2.5rem, 5.5vw, 4.5rem)' }}
            >
                <RichText
                    tagName="span"
                    value={title}
                    onChange={(value) => setAttributes({ title: value })}
                    placeholder={__('Titre', 'esquare')}
                    allowedFormats={[]}
                />{' '}
                {show(titleAccent) && (
                    <RichText
                        tagName="em"
                        className="font-serif font-normal italic normal-case tracking-[-0.01em] text-yellow"
                        value={titleAccent}
                        onChange={(value) => setAttributes({ titleAccent: value })}
                        placeholder={__('fin en italique', 'esquare')}
                        allowedFormats={[]}
                    />
                )}
            </h1>

            {show(intro) && (
                <RichText
                    tagName="p"
                    className={`${isCover ? 'mt-5 max-w-[54ch] text-white/85' : 'mt-6 max-w-[46ch] text-white/75'} [font-family:var(--font-figtree)] text-pretty text-base lg:text-lg [&_strong]:font-semibold [&_strong]:text-white`}
                    value={intro}
                    onChange={(value) => setAttributes({ intro: value })}
                    placeholder={__('Introduction', 'esquare')}
                    allowedFormats={['core/bold', 'core/italic', 'core/link']}
                />
            )}

            {chips.length > 0 && (
                <ul className="mt-6 flex flex-wrap gap-2" role="list">
                    {chips.map((chip, index) => (
                        <li key={index} className={CHIP}>
                            {chip.people && <Icon name="people" className="size-3.5 text-yellow" />}
                            <RichText
                                tagName="span"
                                value={chip.text}
                                onChange={(value) => updateChip(index, { text: value })}
                                placeholder={__('Étiquette', 'esquare')}
                                allowedFormats={[]}
                            />
                        </li>
                    ))}
                </ul>
            )}

            {(show(primaryText) || show(secondaryText) || show(badge)) && (
                <div className={`${isCover ? 'mt-7' : 'mt-8'} flex flex-wrap items-center gap-x-4 gap-y-3`}>
                    {show(primaryText) && (
                        <span className={PRIMARY_BUTTON}>
                            <RichText
                                tagName="span"
                                value={primaryText}
                                onChange={(value) => setAttributes({ primaryText: value })}
                                placeholder={__('Bouton principal', 'esquare')}
                                allowedFormats={[]}
                            />
                            <span aria-hidden="true">{arrow}</span>
                        </span>
                    )}
                    {show(secondaryText) && (
                        <RichText
                            tagName="span"
                            className={SECONDARY_LINK}
                            value={secondaryText}
                            onChange={(value) => setAttributes({ secondaryText: value })}
                            placeholder={__('Lien secondaire', 'esquare')}
                            allowedFormats={[]}
                        />
                    )}
                    {show(badge) && (
                        <span className={BADGE}>
                            {badgeIcon !== 'none' && <Icon name={badgeIcon} className="size-4 text-yellow" />}
                            <RichText
                                tagName="span"
                                value={badge}
                                onChange={(value) => setAttributes({ badge: value })}
                                placeholder={__('Pastille (optionnelle)', 'esquare')}
                                allowedFormats={[]}
                            />
                        </span>
                    )}
                </div>
            )}
        </>
    );

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="cover-image"
                        label={__('Photo en fond', 'esquare')}
                        isPressed={isCover}
                        onClick={() => setAttributes({ layout: 'cover' })}
                    />
                    <ToolbarButton
                        icon="align-pull-right"
                        label={__('Photo à droite', 'esquare')}
                        isPressed={!isCover}
                        onClick={() => setAttributes({ layout: 'split' })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="tag"
                        label={__('Ajouter une étiquette', 'esquare')}
                        onClick={() => setAttributes({ chips: [...chips, { text: '', people: false }] })}
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
                <PanelBody title={__('Boutons', 'esquare')}>
                    <URLInput
                        className="esquare-url-input"
                        label={__('Lien du bouton principal', 'esquare')}
                        value={primaryUrl}
                        onChange={(value) => setAttributes({ primaryUrl: value })}
                    />
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Flèche du bouton principal', 'esquare')}
                        value={primaryArrow}
                        options={[
                            { value: 'auto', label: __('→ (↗ pour un lien externe)', 'esquare') },
                            { value: 'down', label: __('↓ (vers le bas de la page)', 'esquare') },
                        ]}
                        onChange={(value) => setAttributes({ primaryArrow: value })}
                    />
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Lien secondaire', 'esquare')}
                        value={secondaryType}
                        options={[
                            { value: 'link', label: __('Lien vers une page ou une ancre', 'esquare') },
                            { value: 'reservation', label: __('Ouvre le formulaire de réservation (salles)', 'esquare') },
                        ]}
                        onChange={(value) => setAttributes({ secondaryType: value })}
                    />
                    {secondaryType !== 'reservation' && (
                        <URLInput
                            className="esquare-url-input"
                            label={__('Lien secondaire : adresse', 'esquare')}
                            value={secondaryUrl}
                            onChange={(value) => setAttributes({ secondaryUrl: value })}
                        />
                    )}
                </PanelBody>
                <PanelBody title={__('Pastille et étiquettes', 'esquare')} initialOpen={false}>
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Icône de la pastille', 'esquare')}
                        value={badgeIcon}
                        options={[
                            { value: 'calendar', label: __('Calendrier', 'esquare') },
                            { value: 'check', label: __('Validation', 'esquare') },
                            { value: 'lock', label: __('Cadenas', 'esquare') },
                            { value: 'none', label: __('Aucune', 'esquare') },
                        ]}
                        onChange={(value) => setAttributes({ badgeIcon: value })}
                    />
                    {chips.map((chip, index) => (
                        <div key={index} style={{ marginBottom: '12px' }}>
                            <strong>{chip.text || __('Étiquette vide', 'esquare')}</strong>
                            <ToggleControl
                                __nextHasNoMarginBottom
                                label={__('Icône de capacité', 'esquare')}
                                checked={!!chip.people}
                                onChange={(value) => updateChip(index, { people: value })}
                            />
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ chips: chips.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer cette étiquette', 'esquare')}
                            </Button>
                        </div>
                    ))}
                </PanelBody>
                <PanelBody title={__('Lien de retour', 'esquare')} initialOpen={false}>
                    <TextControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Texte (vide = pas de lien)', 'esquare')}
                        value={backText}
                        onChange={(value) => setAttributes({ backText: value })}
                    />
                    <URLInput
                        className="esquare-url-input"
                        label={__('Adresse', 'esquare')}
                        value={backUrl}
                        onChange={(value) => setAttributes({ backUrl: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Photo', 'esquare')}>
                    {imageUrl ? (
                        <>
                            {!isCover && (
                                <SelectControl
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                    label={__('Format de la photo', 'esquare')}
                                    value={imageRatio}
                                    options={[
                                        { value: '16/10', label: __('Paysage (16/10)', 'esquare') },
                                        { value: '4/3', label: __('Classique (4/3)', 'esquare') },
                                        { value: '4/5', label: __('Portrait (4/5)', 'esquare') },
                                    ]}
                                    onChange={(value) => setAttributes({ imageRatio: value })}
                                />
                            )}
                            <FocalPointPicker
                                __nextHasNoMarginBottom
                                label={__('Point de cadrage', 'esquare')}
                                url={imageUrl}
                                value={focalPoint}
                                onChange={(value) => setAttributes({ focalPoint: value })}
                            />
                            <TextareaControl
                                __nextHasNoMarginBottom
                                label={__('Texte alternatif', 'esquare')}
                                help={__('Laissez vide si la photo est purement décorative.', 'esquare')}
                                value={imageAlt}
                                onChange={(value) => setAttributes({ imageAlt: value })}
                            />
                        </>
                    ) : (
                        <p>{__('Ajoutez une photo depuis la barre d’outils du bloc.', 'esquare')}</p>
                    )}
                </PanelBody>
            </InspectorControls>

            <section {...blockProps}>
                {isCover ? (
                    <>
                        {image}
                        <div aria-hidden="true" className="absolute inset-0 -z-10" style={{ background: COVER_GRADIENT }}></div>
                        <div className="relative z-10 mx-auto w-full max-w-7xl px-6 py-16 lg:px-8 lg:py-20">{content}</div>
                    </>
                ) : (
                    <>
                        <div aria-hidden="true" className="poster-grid absolute inset-0 z-0 opacity-[0.12]"></div>
                        <div aria-hidden="true" className="absolute inset-0 z-0" style={{ background: SPLIT_GLOW }}></div>
                        <div className="relative z-10 mx-auto grid max-w-7xl grid-cols-1 items-center gap-x-10 gap-y-10 px-6 py-14 lg:grid-cols-12 lg:px-8 lg:py-20">
                            <div className="lg:col-span-6">{content}</div>
                            <div className="lg:col-span-6">{image}</div>
                        </div>
                    </>
                )}
            </section>
        </>
    );
}
