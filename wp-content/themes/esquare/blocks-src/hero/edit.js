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
    FocalPointPicker,
    PanelBody,
    TextareaControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const SECTION = {
    cover: 'relative isolate flex min-h-[62vh] items-end overflow-hidden bg-navy-deep text-white',
    split: 'relative isolate overflow-hidden bg-navy-deep text-white',
};
const IMAGE = {
    cover: 'absolute inset-0 -z-20 size-full object-cover',
    split: 'aspect-[16/10] w-full rounded-2xl object-cover shadow-[0_30px_60px_-20px_color-mix(in_oklch,var(--color-navy-deep)_60%,transparent)] ring-1 ring-white/10',
};
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

function CalendarIcon() {
    return (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="size-4 text-yellow">
            <path fillRule="evenodd" d="M6.75 2.25A.75.75 0 0 1 7.5 3v1.5h9V3A.75.75 0 0 1 18 3v1.5h.75a3 3 0 0 1 3 3v11.25a3 3 0 0 1-3 3H5.25a3 3 0 0 1-3-3V7.5a3 3 0 0 1 3-3H6V3a.75.75 0 0 1 .75-.75Zm13.5 9a1.5 1.5 0 0 0-1.5-1.5H5.25a1.5 1.5 0 0 0-1.5 1.5v7.5a1.5 1.5 0 0 0 1.5 1.5h13.5a1.5 1.5 0 0 0 1.5-1.5v-7.5Z" clipRule="evenodd" />
        </svg>
    );
}

export default function Edit({ attributes, setAttributes, isSelected }) {
    const {
        layout,
        eyebrow,
        title,
        titleAccent,
        intro,
        primaryText,
        primaryUrl,
        secondaryText,
        secondaryUrl,
        badge,
        imageId,
        imageUrl,
        imageAlt,
        focalPoint,
    } = attributes;

    const isCover = layout !== 'split';
    const blockProps = useBlockProps({ className: SECTION[isCover ? 'cover' : 'split'] });
    // Optional parts stay visible while editing so they can be filled in.
    const show = (value) => isSelected || (value ?? '') !== '';

    const onSelectImage = (media) =>
        setAttributes({ imageId: media.id, imageUrl: media.url, imageAlt: media.alt ?? '' });

    const image = imageUrl ? (
        <img
            src={imageUrl}
            alt={imageAlt}
            className={IMAGE[isCover ? 'cover' : 'split']}
            style={{ objectPosition: `${focalPoint.x * 100}% ${focalPoint.y * 100}%` }}
        />
    ) : (
        !isCover && (
            <div className="flex aspect-[16/10] w-full items-center justify-center rounded-2xl bg-white/5 ring-1 ring-white/10 text-white/60">
                {__('Choisissez une photo via « Remplacer » dans la barre d’outils.', 'esquare')}
            </div>
        )
    );

    const content = (
        <>
            {show(eyebrow) && (
                <p className={`${isCover ? 'rounded-full bg-navy-deep/85 px-3.5 py-1.5 ring-1 ring-white/10 ' : ''}inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow`}>
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
                    className={`${isCover ? 'mt-5 max-w-[54ch] text-white/85' : 'mt-6 max-w-[46ch] text-white/75'} [font-family:var(--font-figtree)] text-pretty text-base lg:text-lg`}
                    value={intro}
                    onChange={(value) => setAttributes({ intro: value })}
                    placeholder={__('Introduction', 'esquare')}
                    allowedFormats={['core/bold', 'core/italic', 'core/link']}
                />
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
                            <span aria-hidden="true">→</span>
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
                            <CalendarIcon />
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
                <PanelBody title={__('Liens des boutons', 'esquare')}>
                    <URLInput
                        className="esquare-url-input"
                        label={__('Bouton principal', 'esquare')}
                        value={primaryUrl}
                        onChange={(value) => setAttributes({ primaryUrl: value })}
                    />
                    <URLInput
                        className="esquare-url-input"
                        label={__('Lien secondaire', 'esquare')}
                        value={secondaryUrl}
                        onChange={(value) => setAttributes({ secondaryUrl: value })}
                    />
                </PanelBody>
                <PanelBody title={__('Photo', 'esquare')}>
                    {imageUrl ? (
                        <>
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
