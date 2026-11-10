import { __, sprintf } from '@wordpress/i18n';
import {
    BlockControls,
    InspectorControls,
    MediaPlaceholder,
    MediaUpload,
    MediaUploadCheck,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    Button,
    FocalPointPicker,
    PanelBody,
    TextareaControl,
    TextControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const LAYOUTS = {
    2: { grid: 'grid-cols-1 sm:grid-cols-2', first: 'aspect-[16/10] sm:aspect-[4/3]', other: 'aspect-[16/10] sm:aspect-[4/3]' },
    3: { grid: 'grid-cols-2 sm:grid-cols-3', first: 'col-span-2 aspect-[16/10] sm:col-span-1 sm:aspect-[4/3]', other: 'aspect-square sm:aspect-[4/3]' },
    4: { grid: 'grid-cols-2 sm:grid-cols-4', first: 'aspect-square sm:aspect-[4/3]', other: 'aspect-square sm:aspect-[4/3]' },
};
const MAX = 4;

const toImage = (media) => ({ id: media.id, url: media.url, alt: media.alt ?? '', focalPoint: { x: 0.5, y: 0.5 } });
const position = (focal) => `${Math.round((focal?.x ?? 0.5) * 100)}% ${Math.round((focal?.y ?? 0.5) * 100)}%`;

export default function Edit({ attributes, setAttributes }) {
    const { label, images } = attributes;
    const blockProps = useBlockProps({ className: 'bg-stone-warm' });
    const layout = LAYOUTS[images.length] ?? LAYOUTS[3];

    // Images are stored as an array of plain objects; always write a fresh copy.
    const updateImage = (index, changes) =>
        setAttributes({ images: images.map((image, i) => (i === index ? { ...image, ...changes } : image)) });
    const moveImage = (index, delta) => {
        const next = [...images];
        const [image] = next.splice(index, 1);
        next.splice(index + delta, 0, image);
        setAttributes({ images: next });
    };
    const addImages = (media) => setAttributes({ images: [...images, ...media.map(toImage)].slice(0, MAX) });

    if (images.length === 0) {
        return (
            <div {...blockProps}>
                <MediaPlaceholder
                    icon="format-gallery"
                    labels={{
                        title: __('Bande photo', 'esquare'),
                        instructions: __('Choisissez 2 à 4 photos. Elles s’affichent bord à bord sur toute la largeur.', 'esquare'),
                    }}
                    accept="image/*"
                    allowedTypes={['image']}
                    multiple
                    onSelect={addImages}
                />
            </div>
        );
    }

    return (
        <>
            {images.length < MAX && (
                <BlockControls>
                    <ToolbarGroup>
                        <MediaUploadCheck>
                            <MediaUpload
                                allowedTypes={['image']}
                                multiple
                                onSelect={addImages}
                                render={({ open }) => (
                                    <ToolbarButton icon="plus-alt2" label={__('Ajouter des photos', 'esquare')} onClick={open} />
                                )}
                            />
                        </MediaUploadCheck>
                    </ToolbarGroup>
                </BlockControls>
            )}

            <InspectorControls>
                <PanelBody title={__('Accessibilité', 'esquare')}>
                    <TextControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Description de la bande (lecteurs d’écran)', 'esquare')}
                        help={__('Ex. « L’espace de coworking en images ».', 'esquare')}
                        value={label}
                        onChange={(value) => setAttributes({ label: value })}
                    />
                </PanelBody>
                {images.map((image, index) => (
                    <PanelBody
                        key={index}
                        initialOpen={false}
                        title={sprintf(/* translators: %d: photo number. */ __('Photo %d', 'esquare'), index + 1)}
                    >
                        <FocalPointPicker
                            __nextHasNoMarginBottom
                            label={__('Point de cadrage', 'esquare')}
                            url={image.url}
                            value={image.focalPoint ?? { x: 0.5, y: 0.5 }}
                            onChange={(value) => updateImage(index, { focalPoint: value })}
                        />
                        <TextareaControl
                            __nextHasNoMarginBottom
                            label={__('Texte alternatif', 'esquare')}
                            value={image.alt}
                            onChange={(value) => updateImage(index, { alt: value })}
                        />
                        <p style={{ marginTop: '12px' }}>
                            <MediaUploadCheck>
                                <MediaUpload
                                    allowedTypes={['image']}
                                    value={image.id}
                                    onSelect={(media) => updateImage(index, { ...toImage(media), focalPoint: { x: 0.5, y: 0.5 } })}
                                    render={({ open }) => (
                                        <Button variant="secondary" onClick={open}>
                                            {__('Remplacer', 'esquare')}
                                        </Button>
                                    )}
                                />
                            </MediaUploadCheck>
                        </p>
                        <p>
                            <Button variant="tertiary" disabled={index === 0} onClick={() => moveImage(index, -1)}>
                                {__('← Avant', 'esquare')}
                            </Button>
                            <Button variant="tertiary" disabled={index === images.length - 1} onClick={() => moveImage(index, 1)}>
                                {__('Après →', 'esquare')}
                            </Button>
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ images: images.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    </PanelBody>
                ))}
            </InspectorControls>

            <section {...blockProps}>
                <div className={`grid ${layout.grid}`}>
                    {images.map((image, index) => (
                        <img
                            key={index}
                            src={image.url}
                            alt={image.alt}
                            style={{ objectPosition: position(image.focalPoint) }}
                            className={`${index === 0 ? layout.first : layout.other} w-full object-cover`}
                        />
                    ))}
                </div>
            </section>
        </>
    );
}
