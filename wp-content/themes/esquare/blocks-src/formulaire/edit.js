import { __, sprintf } from '@wordpress/i18n';
import { BlockControls, InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToolbarButton, ToolbarGroup } from '@wordpress/components';

// Fluent Forms list, printed by functions.php: [{ id, title }].
const FORMS = window.esquareForms ?? [];

// Class lists mirror render.php so the editor shows the real design.
const FIGTREE = '[font-family:var(--font-figtree)]';
const ITALIC = '[&_em]:font-serif [&_em]:font-normal [&_em]:italic [&_em]:normal-case [&_em]:tracking-[-0.01em]';
const NOTE_LINKS = '[&_a]:font-semibold [&_a]:text-navy [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4 [&_a]:transition-colors [&_a:hover]:decoration-navy/40';
const VARIANTS = {
    split: {
        section: 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
        inner: 'mx-auto grid max-w-7xl grid-cols-1 items-start gap-x-14 gap-y-10 lg:grid-cols-[1fr_1.2fr]',
        eyebrow: `inline-flex items-center gap-2 ${FIGTREE} text-xs font-bold text-navy/70 uppercase tracking-widest`,
        dot: 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        title: `mt-3 max-w-[15ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl ${ITALIC}`,
        text: `mt-5 max-w-[46ch] ${FIGTREE} text-pretty text-base text-navy/75 lg:text-lg`,
        form: 'esq-form rounded-2xl bg-stone-warm p-6 ring-1 ring-navy/8 lg:p-8',
    },
    poster: {
        section: 'px-6 py-20 lg:px-8 lg:py-24',
        inner: 'relative isolate grid grid-cols-1 gap-y-8 px-8 py-12 lg:grid-cols-[1.1fr_1fr] lg:items-center lg:gap-x-12 lg:px-12 lg:py-14',
        eyebrow: 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-navy-deep',
        dot: 'inline-block size-1.5 rounded-full bg-navy-deep',
        title: `mt-4 max-w-[20ch] ${FIGTREE} text-3xl font-black uppercase leading-[1] tracking-[-0.03em] text-balance text-navy-deep md:text-4xl ${ITALIC}`,
        text: `mt-4 max-w-[40ch] ${FIGTREE} text-pretty text-navy-deep/80`,
        form: 'esq-form esq-form-brass',
    },
};

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { variant, formId, eyebrow, title, text, note } = attributes;
    const v = VARIANTS[variant] ?? VARIANTS.split;
    const isPoster = variant === 'poster';
    const blockProps = useBlockProps({ className: v.section });
    const form = FORMS.find((f) => f.id === formId);

    const field = (tagName, className, value, onChange, placeholder, formats = []) => (
        <RichText tagName={tagName} className={className} value={value} onChange={onChange} placeholder={placeholder} allowedFormats={formats} />
    );

    // Shortcodes don't run in the editor: show which form will appear.
    const formBox = (
        <div className={v.form}>
            <div
                className={`flex min-h-48 flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed p-6 text-center ${FIGTREE} text-base ${
                    isPoster ? 'border-navy-deep/30 text-navy-deep' : 'border-navy/20 text-navy'
                }`}
            >
                <strong>
                    {form
                        ? sprintf(/* translators: %s: form title. */ __('Formulaire « %s »', 'esquare'), form.title)
                        : __('Aucun formulaire choisi', 'esquare')}
                </strong>
                <span className="opacity-75">
                    {form
                        ? __('Il s’affiche sur la page publiée.', 'esquare')
                        : __('Choisissez-le dans la barre latérale.', 'esquare')}
                </span>
            </div>
        </div>
    );

    const textColumn = (
        <div>
            <p className={v.eyebrow}>
                <span aria-hidden="true" className={v.dot}></span>
                {field('span', '', eyebrow, (value) => setAttributes({ eyebrow: value }), __('Surtitre', 'esquare'))}
            </p>
            {field('h2', v.title, title, (value) => setAttributes({ title: value }), __('Titre', 'esquare'), ['core/italic'])}
            {(isSelected || text) &&
                field('p', v.text, text, (value) => setAttributes({ text: value }), __('Texte (optionnel)', 'esquare'), ['core/bold', 'core/italic'])}
            {!isPoster &&
                (isSelected || note) &&
                field(
                    'p',
                    `mt-6 max-w-[46ch] ${FIGTREE} text-base text-navy/75 ${NOTE_LINKS}`,
                    note,
                    (value) => setAttributes({ note: value }),
                    __('Petite note (optionnelle, ex. « Vous préférez le téléphone ? »)', 'esquare'),
                    ['core/bold', 'core/italic', 'core/link']
                )}
        </div>
    );

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="columns"
                        label={__('Texte + carte (fond crème)', 'esquare')}
                        isPressed={!isPoster}
                        onClick={() => setAttributes({ variant: 'split' })}
                    />
                    <ToolbarButton
                        icon="megaphone"
                        label={__('Panneau jaune', 'esquare')}
                        isPressed={isPoster}
                        onClick={() => setAttributes({ variant: 'poster' })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Formulaire', 'esquare')}>
                    <SelectControl
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                        label={__('Formulaire Fluent Forms', 'esquare')}
                        value={String(formId)}
                        options={[
                            { value: '0', label: __('— Choisir —', 'esquare') },
                            ...FORMS.map((f) => ({ value: String(f.id), label: `${f.title} (n° ${f.id})` })),
                        ]}
                        onChange={(value) => setAttributes({ formId: parseInt(value, 10) || 0 })}
                    />
                </PanelBody>
            </InspectorControls>

            <section {...blockProps}>
                {isPoster ? (
                    <div className="mx-auto max-w-7xl overflow-hidden rounded-3xl bg-yellow ring-1 ring-navy/10">
                        <div className={v.inner}>
                            <div aria-hidden="true" className="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
                            {textColumn}
                            {formBox}
                        </div>
                    </div>
                ) : (
                    <div className={v.inner}>
                        {textColumn}
                        {formBox}
                    </div>
                )}
            </section>
        </>
    );
}
