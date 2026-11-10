import { __, sprintf } from '@wordpress/i18n';
import {
    BlockControls,
    InspectorControls,
    MediaPlaceholder,
    MediaUpload,
    MediaUploadCheck,
    RichText,
    URLInput,
    useBlockProps,
} from '@wordpress/block-editor';
import { Button, PanelBody, TextareaControl, ToolbarButton, ToolbarGroup } from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const SECTION = 'bg-stone-warm px-6 py-14 lg:px-8 lg:py-16';
const CARD = 'mx-auto max-w-7xl rounded-2xl bg-white px-6 py-8 ring-1 ring-navy/8 lg:px-10 lg:py-9';
const TITLE =
    'text-center font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy/70 lg:text-left';
const SINGLE = 'mx-auto mt-6 h-auto w-full max-w-4xl';
const ROW = 'mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-6 lg:justify-start';
const LOGO = 'h-12 w-auto max-w-40 object-contain lg:h-14';
const MAX = 12;

const toLogo = (media) => ({
    id: media.id,
    url: media.url,
    alt: media.alt ?? '',
    width: media.width ?? 0,
    height: media.height ?? 0,
    link: '',
});

export default function Edit({ attributes, setAttributes }) {
    const { title, logos } = attributes;
    const blockProps = useBlockProps({ className: SECTION });

    // Logos are stored as an array of plain objects; always write a fresh copy.
    const updateLogo = (index, changes) =>
        setAttributes({ logos: logos.map((logo, i) => (i === index ? { ...logo, ...changes } : logo)) });
    const moveLogo = (index, delta) => {
        const next = [...logos];
        const [logo] = next.splice(index, 1);
        next.splice(index + delta, 0, logo);
        setAttributes({ logos: next });
    };
    const addLogos = (media) => setAttributes({ logos: [...logos, ...media.map(toLogo)].slice(0, MAX) });

    const heading = (
        <RichText
            tagName="h2"
            className={TITLE}
            value={title}
            onChange={(value) => setAttributes({ title: value })}
            placeholder={__('Avec le soutien de', 'esquare')}
            allowedFormats={[]}
        />
    );

    if (logos.length === 0) {
        return (
            <section {...blockProps}>
                <div className={CARD}>
                    {heading}
                    <MediaPlaceholder
                        icon="awards"
                        labels={{
                            title: __('Logos partenaires', 'esquare'),
                            instructions: __(
                                'Choisissez une image qui regroupe tous les logos, ou un logo par image.',
                                'esquare'
                            ),
                        }}
                        accept="image/*"
                        allowedTypes={['image']}
                        multiple
                        onSelect={addLogos}
                    />
                </div>
            </section>
        );
    }

    return (
        <>
            {logos.length < MAX && (
                <BlockControls>
                    <ToolbarGroup>
                        <MediaUploadCheck>
                            <MediaUpload
                                allowedTypes={['image']}
                                multiple
                                onSelect={addLogos}
                                render={({ open }) => (
                                    <ToolbarButton icon="plus-alt2" label={__('Ajouter des logos', 'esquare')} onClick={open} />
                                )}
                            />
                        </MediaUploadCheck>
                    </ToolbarGroup>
                </BlockControls>
            )}

            <InspectorControls>
                {logos.map((logo, index) => (
                    <PanelBody
                        key={index}
                        initialOpen={logos.length === 1}
                        title={sprintf(/* translators: %d: logo number. */ __('Logo %d', 'esquare'), index + 1)}
                    >
                        <TextareaControl
                            __nextHasNoMarginBottom
                            label={__('Texte alternatif', 'esquare')}
                            help={
                                logos.length === 1
                                    ? __('Nommez tous les partenaires visibles sur l’image.', 'esquare')
                                    : __('Le nom du partenaire.', 'esquare')
                            }
                            value={logo.alt}
                            onChange={(value) => updateLogo(index, { alt: value })}
                        />
                        <div style={{ marginTop: '12px' }}>
                            <URLInput
                                className="esquare-url-input"
                                label={__('Lien (optionnel)', 'esquare')}
                                value={logo.link ?? ''}
                                onChange={(value) => updateLogo(index, { link: value })}
                            />
                        </div>
                        <p style={{ marginTop: '12px' }}>
                            <MediaUploadCheck>
                                <MediaUpload
                                    allowedTypes={['image']}
                                    value={logo.id}
                                    onSelect={(media) => updateLogo(index, { ...toLogo(media), link: logo.link ?? '' })}
                                    render={({ open }) => (
                                        <Button variant="secondary" onClick={open}>
                                            {__('Remplacer', 'esquare')}
                                        </Button>
                                    )}
                                />
                            </MediaUploadCheck>
                        </p>
                        <p>
                            <Button variant="tertiary" disabled={index === 0} onClick={() => moveLogo(index, -1)}>
                                {__('← Avant', 'esquare')}
                            </Button>
                            <Button variant="tertiary" disabled={index === logos.length - 1} onClick={() => moveLogo(index, 1)}>
                                {__('Après →', 'esquare')}
                            </Button>
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ logos: logos.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    </PanelBody>
                ))}
            </InspectorControls>

            <section {...blockProps}>
                <div className={CARD}>
                    {heading}
                    {logos.length === 1 ? (
                        <img src={logos[0].url} alt={logos[0].alt} className={SINGLE} />
                    ) : (
                        <ul role="list" className={ROW}>
                            {logos.map((logo, index) => (
                                <li key={index}>
                                    <img src={logo.url} alt={logo.alt} className={LOGO} />
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </section>
        </>
    );
}
