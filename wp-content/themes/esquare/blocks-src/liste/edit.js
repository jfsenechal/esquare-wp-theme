import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import {
    BlockControls,
    InspectorControls,
    RichText,
    URLInput,
    useBlockProps,
} from '@wordpress/block-editor';
import {
    Button,
    PanelBody,
    SelectControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const FIGTREE = '[font-family:var(--font-figtree)]';
const LINK = 'font-semibold underline decoration-yellow decoration-2 underline-offset-4 transition-colors';
const THEMES = {
    light: {
        section: 'bg-cream px-6 py-20 lg:px-8 lg:py-24',
        eyebrow: `inline-flex items-center gap-2 ${FIGTREE} text-xs font-bold text-navy/70 uppercase tracking-widest`,
        dot: 'inline-block size-2 rounded-full bg-yellow opacity-[0.675]',
        title: `mt-3 max-w-[16ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl`,
        intro: `max-w-[54ch] ${FIGTREE} text-pretty text-base text-navy/75 lg:text-lg`,
        rule: 'border-navy/12',
        itemTitle: 'text-navy',
        itemText: 'text-navy/75',
        link: `${LINK} text-navy hover:decoration-navy/40`,
        bulletsTitle: 'text-navy/70',
        bullets: 'text-navy',
        outro: 'text-navy/75',
    },
    dark: {
        section: 'relative isolate overflow-hidden bg-navy-deep px-6 py-20 text-white lg:px-8 lg:py-24',
        eyebrow: 'inline-flex items-center gap-2 font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow',
        dot: 'inline-block size-1.5 rounded-full bg-yellow',
        title: `mt-4 max-w-[16ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance md:text-4xl lg:text-5xl`,
        intro: `max-w-[54ch] ${FIGTREE} text-pretty text-base text-white/70 lg:text-lg`,
        rule: 'border-white/15',
        itemTitle: 'text-white',
        itemText: 'text-white/70',
        link: `${LINK} text-white hover:decoration-white/50`,
        bulletsTitle: 'text-white/65',
        bullets: 'text-white',
        outro: 'text-white/70',
    },
};
// Links inside the closing paragraph, spelled out for Tailwind.
const OUTRO_LINKS = {
    light: '[&_a]:font-semibold [&_a]:text-navy [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4',
    dark: '[&_a]:font-semibold [&_a]:text-white [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4',
};

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { theme, layout, eyebrow, title, intro, items, bulletsTitle, bullets, outro } = attributes;
    const t = THEMES[theme] ?? THEMES.dark;
    const blockProps = useBlockProps({ className: t.section });
    const isGrid = layout === 'grid';

    // Lists are stored as arrays; always write fresh copies.
    const updateItem = (index, changes) =>
        setAttributes({ items: items.map((item, i) => (i === index ? { ...item, ...changes } : item)) });
    const moveItem = (index, delta) => {
        const next = [...items];
        const [item] = next.splice(index, 1);
        next.splice(index + delta, 0, item);
        setAttributes({ items: next });
    };

    const field = (tagName, className, value, onChange, placeholder, formats = []) => (
        <RichText tagName={tagName} className={className} value={value} onChange={onChange} placeholder={placeholder} allowedFormats={formats} />
    );
    const itemTitle = (item, index) =>
        field('h3', `${FIGTREE} text-xl font-bold tracking-tight lg:text-2xl ${t.itemTitle}`, item.title, (v) => updateItem(index, { title: v }), __('Titre', 'esquare'));
    const linkText = (item, index) =>
        field('span', t.link, item.linkText, (v) => updateItem(index, { linkText: v }), __('Texte du lien (optionnel)', 'esquare'));

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="list-view"
                        label={__('En lignes', 'esquare')}
                        isPressed={!isGrid}
                        onClick={() => setAttributes({ layout: 'rows' })}
                    />
                    <ToolbarButton
                        icon="grid-view"
                        label={__('En grille (2 colonnes)', 'esquare')}
                        isPressed={isGrid}
                        onClick={() => setAttributes({ layout: 'grid' })}
                    />
                </ToolbarGroup>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="plus-alt2"
                        label={__('Ajouter un élément', 'esquare')}
                        onClick={() => setAttributes({ items: [...items, { title: '', text: '', linkText: '', linkUrl: '' }] })}
                    />
                </ToolbarGroup>
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
                </PanelBody>
                {items.map((item, index) => (
                    <PanelBody
                        key={index}
                        initialOpen={false}
                        title={sprintf(
                            /* translators: 1: item number, 2: item title. */
                            __('Élément %1$d : %2$s', 'esquare'),
                            index + 1,
                            decodeEntities(item.title) || __('sans titre', 'esquare')
                        )}
                    >
                        <URLInput
                            className="esquare-url-input"
                            label={__('Lien (vide = pas de lien)', 'esquare')}
                            value={item.linkUrl}
                            onChange={(value) => updateItem(index, { linkUrl: value })}
                        />
                        <p style={{ marginTop: '12px' }}>
                            <Button variant="tertiary" disabled={index === 0} onClick={() => moveItem(index, -1)}>
                                {__('↑ Monter', 'esquare')}
                            </Button>
                            <Button variant="tertiary" disabled={index === items.length - 1} onClick={() => moveItem(index, 1)}>
                                {__('↓ Descendre', 'esquare')}
                            </Button>
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ items: items.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    </PanelBody>
                ))}
                <PanelBody title={__('Liste à puces (optionnelle)', 'esquare')} initialOpen={false}>
                    {bullets.map((bullet, index) => (
                        <p key={index}>
                            {decodeEntities(bullet) || __('puce vide', 'esquare')}{' '}
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ bullets: bullets.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer', 'esquare')}
                            </Button>
                        </p>
                    ))}
                    <Button variant="secondary" onClick={() => setAttributes({ bullets: [...bullets, ''] })}>
                        {__('Ajouter une puce', 'esquare')}
                    </Button>
                </PanelBody>
            </InspectorControls>

            <section {...blockProps}>
                {theme === 'dark' && <div aria-hidden="true" className="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>}
                <div className="mx-auto max-w-7xl">
                    <div className="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
                        <div>
                            <p className={t.eyebrow}>
                                <span aria-hidden="true" className={t.dot}></span>
                                {field('span', '', eyebrow, (v) => setAttributes({ eyebrow: v }), __('Surtitre', 'esquare'))}
                            </p>
                            {field('h2', t.title, title, (v) => setAttributes({ title: v }), __('Titre', 'esquare'))}
                        </div>
                        {(isSelected || intro) &&
                            field('p', t.intro, intro, (v) => setAttributes({ intro: v }), __('Introduction (optionnelle)', 'esquare'), ['core/bold', 'core/italic', 'core/link'])}
                    </div>

                    {isGrid ? (
                        <ul role="list" className="mt-14 grid grid-cols-1 gap-x-10 gap-y-9 sm:grid-cols-2">
                            {items.map((item, index) => (
                                <li key={index} className={`border-t pt-6 ${t.rule}`}>
                                    {itemTitle(item, index)}
                                    <p className={`mt-3 max-w-[46ch] ${FIGTREE} text-pretty ${t.itemText}`}>
                                        {field('span', '', item.text, (v) => updateItem(index, { text: v }), __('Texte', 'esquare'), ['core/bold', 'core/italic'])}
                                        {(isSelected || item.linkText) && <> {linkText(item, index)}</>}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <ul role="list" className="mt-14">
                            {items.map((item, index) => (
                                <li
                                    key={index}
                                    className={`grid grid-cols-1 items-baseline gap-x-8 gap-y-2 border-t py-6 lg:grid-cols-[15rem_minmax(0,1fr)_auto] lg:py-7 ${t.rule}${index === items.length - 1 ? ' border-b' : ''}`}
                                >
                                    {itemTitle(item, index)}
                                    {field('p', `${FIGTREE} text-base ${t.itemText}`, item.text, (v) => updateItem(index, { text: v }), __('Texte', 'esquare'), ['core/bold', 'core/italic'])}
                                    <p className={`${FIGTREE} text-base lg:text-right`}>{(isSelected || item.linkText) && linkText(item, index)}</p>
                                </li>
                            ))}
                        </ul>
                    )}

                    {bullets.length > 0 && (
                        <div className={`mt-16 grid grid-cols-1 gap-x-14 gap-y-6 border-t pt-10 lg:grid-cols-[16rem_minmax(0,1fr)] ${t.rule}`}>
                            {field('h3', `font-mono text-[11px] font-semibold uppercase tracking-[0.25em] ${t.bulletsTitle}`, bulletsTitle, (v) => setAttributes({ bulletsTitle: v }), __('Titre de la liste', 'esquare'))}
                            <ul role="list" className={`grid grid-cols-1 gap-x-10 gap-y-4 ${FIGTREE} text-lg font-semibold sm:grid-cols-2 lg:text-xl ${t.bullets}`}>
                                {bullets.map((bullet, index) => (
                                    <li key={index} className="flex items-baseline gap-3">
                                        <span aria-hidden="true" className="size-1.5 shrink-0 rounded-full bg-yellow"></span>
                                        {field('span', '', bullet, (v) => setAttributes({ bullets: bullets.map((b, i) => (i === index ? v : b)) }), __('Puce', 'esquare'))}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {(isSelected || outro) &&
                        field('p', `mt-10 max-w-[62ch] ${FIGTREE} text-base ${t.outro} ${OUTRO_LINKS[theme] ?? OUTRO_LINKS.dark}`, outro, (v) => setAttributes({ outro: v }), __('Paragraphe de conclusion (optionnel)', 'esquare'), ['core/bold', 'core/italic', 'core/link'])}
                </div>
            </section>
        </>
    );
}
