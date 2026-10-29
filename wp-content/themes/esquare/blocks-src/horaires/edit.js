import { __ } from '@wordpress/i18n';
import { BlockControls, InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { Button, PanelBody, ToolbarButton, ToolbarGroup } from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
// Words set in italic in the title (<em>) take the serif accent.
const TITLE =
    'mt-4 [font-family:var(--font-figtree)] text-3xl font-black uppercase leading-[1] tracking-[-0.03em] text-balance text-navy-deep md:text-4xl [&_em]:font-serif [&_em]:font-normal [&_em]:normal-case [&_em]:tracking-[-0.01em]';
const SLOT = 'rounded-2xl bg-white/55 p-6 ring-1 ring-navy-deep/10';

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { eyebrow, title, intro, slots, note } = attributes;
    const blockProps = useBlockProps({ className: 'px-6 py-20 lg:px-8 lg:py-24' });

    // Slots are stored as an array of plain objects; always write a fresh copy.
    const updateSlot = (index, key, value) =>
        setAttributes({ slots: slots.map((slot, i) => (i === index ? { ...slot, [key]: value } : slot)) });

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="clock"
                        label={__('Ajouter un créneau', 'esquare')}
                        onClick={() => setAttributes({ slots: [...slots, { label: '', from: '', to: '' }] })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Créneaux', 'esquare')}>
                    {slots.map((slot, index) => (
                        <p key={index}>
                            <strong>{slot.label || __('Créneau sans intitulé', 'esquare')}</strong>{' '}
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ slots: slots.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    ))}
                    <p>{__('Laissez l’heure de fin vide pour une mention seule (ex. « Sur rendez-vous »).', 'esquare')}</p>
                    <p>{__('Dans le titre, mettez des mots en italique (Ctrl+I) pour l’accent serif.', 'esquare')}</p>
                </PanelBody>
            </InspectorControls>

            <section {...blockProps}>
                <div className="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-yellow ring-1 ring-navy/10">
                    <div className="relative isolate grid grid-cols-1 gap-y-8 px-8 py-12 lg:grid-cols-[1.1fr_1.4fr] lg:items-center lg:gap-x-12 lg:px-12 lg:py-14">
                        <div aria-hidden="true" className="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
                        <div>
                            <p className="inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy-deep">
                                <span aria-hidden="true" className="inline-block size-1.5 rounded-full bg-navy-deep"></span>
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
                                className={TITLE}
                                value={title}
                                onChange={(value) => setAttributes({ title: value })}
                                placeholder={__('Titre (mots en italique pour l’accent)', 'esquare')}
                                allowedFormats={['core/italic']}
                            />
                            {(isSelected || intro) && (
                                <RichText
                                    tagName="p"
                                    className="mt-4 max-w-[40ch] [font-family:var(--font-figtree)] text-pretty text-navy-deep/80"
                                    value={intro}
                                    onChange={(value) => setAttributes({ intro: value })}
                                    placeholder={__('Introduction', 'esquare')}
                                    allowedFormats={['core/bold', 'core/italic', 'core/link']}
                                />
                            )}
                        </div>
                        <div>
                            <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {slots.map((slot, index) => (
                                    <div key={index} className={SLOT}>
                                        <RichText
                                            tagName="dt"
                                            className="font-mono text-[11px] font-semibold uppercase tracking-[0.2em] text-navy-deep/70"
                                            value={slot.label}
                                            onChange={(value) => updateSlot(index, 'label', value)}
                                            placeholder={__('Jours', 'esquare')}
                                            allowedFormats={[]}
                                        />
                                        <dd className="mt-2 [font-family:var(--font-figtree)] text-3xl font-bold tracking-tight text-navy-deep tabular-nums">
                                            <RichText
                                                tagName="span"
                                                value={slot.from}
                                                onChange={(value) => updateSlot(index, 'from', value)}
                                                placeholder="09:00"
                                                allowedFormats={[]}
                                            />
                                            {(isSelected || slot.to) && (
                                                <>
                                                    {' '}
                                                    <span className="text-navy-deep/55">–</span>{' '}
                                                    <RichText
                                                        tagName="span"
                                                        value={slot.to}
                                                        onChange={(value) => updateSlot(index, 'to', value)}
                                                        placeholder="17:00"
                                                        allowedFormats={[]}
                                                    />
                                                </>
                                            )}
                                        </dd>
                                    </div>
                                ))}
                            </dl>
                            {(isSelected || note) && (
                                <RichText
                                    tagName="p"
                                    className="mt-4 [font-family:var(--font-figtree)] text-sm font-semibold text-navy-deep/70"
                                    value={note}
                                    onChange={(value) => setAttributes({ note: value })}
                                    placeholder={__('Note (optionnelle), ex. « Fermé les jours fériés. »', 'esquare')}
                                    allowedFormats={['core/bold', 'core/italic', 'core/link']}
                                />
                            )}
                        </div>
                    </div>
                </div>
            </section>
        </>
    );
}
