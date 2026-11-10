import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { BlockControls, InspectorControls, RichText, useBlockProps } from '@wordpress/block-editor';
import {
    Button,
    PanelBody,
    TextControl,
    ToggleControl,
    ToolbarButton,
    ToolbarGroup,
} from '@wordpress/components';

// Class lists mirror render.php so the editor shows the real design.
const FIGTREE = '[font-family:var(--font-figtree)]';
const TOC = 'relative isolate overflow-hidden bg-navy-deep px-6 py-12 text-white lg:px-8 lg:py-14';
const TOC_TITLE = 'font-mono text-[11px] font-semibold uppercase tracking-[0.25em] text-yellow';
const CHIP = `inline-flex min-h-[44px] items-center rounded-full bg-white/5 px-4 ${FIGTREE} text-base font-semibold text-white ring-1 ring-white/15 transition-colors hover:bg-white/12`;
const SECTION = 'bg-cream px-6 py-20 lg:px-8 lg:py-24';
const EYEBROW = `inline-flex items-center gap-2 ${FIGTREE} text-xs font-bold text-navy/70 uppercase tracking-widest`;
const TITLE = `mt-3 max-w-[16ch] ${FIGTREE} text-3xl font-bold tracking-tight text-balance text-navy md:text-4xl lg:text-5xl`;
const INTRO = `max-w-[54ch] ${FIGTREE} text-pretty text-base text-navy/75 lg:text-lg`;
const ARTICLE = 'grid grid-cols-1 gap-x-14 gap-y-6 border-t border-navy/12 py-10 lg:grid-cols-[1fr_1.55fr] lg:py-12';
const NAME = `${FIGTREE} text-2xl font-bold tracking-tight text-navy md:text-3xl`;
const SUBTITLE = 'mt-1.5 font-mono text-[11px] font-semibold uppercase tracking-[0.18em] text-navy/70';
const CONTACT_LABEL = 'block font-mono text-[10px] font-semibold uppercase tracking-[0.18em] text-navy/70';
const LINK = 'font-semibold text-navy underline decoration-yellow decoration-2 underline-offset-4 transition-colors hover:decoration-navy/40';
const BODY = `${FIGTREE} text-pretty text-base text-navy/75 lg:text-lg [&_a]:font-semibold [&_a]:text-navy [&_a]:underline [&_a]:decoration-yellow [&_a]:decoration-2 [&_a]:underline-offset-4`;

const EMPTY_SERVICE = { anchor: '', name: '', subtitle: '', tocLabel: '', contacts: [], paragraphs: [''], bullets: [] };
const EMPTY_CONTACT = { label: '', links: [{ text: '', url: '' }] };

const plain = (html) => decodeEntities((html ?? '').replace(/<[^>]*>/g, '')).trim();

export default function Edit({ attributes, setAttributes, isSelected }) {
    const { showToc, tocTitle, eyebrow, title, intro, services } = attributes;
    const blockProps = useBlockProps();

    // Services are stored as arrays of plain objects; always write fresh copies.
    const updateService = (index, changes) =>
        setAttributes({ services: services.map((s, i) => (i === index ? { ...s, ...changes } : s)) });
    const moveService = (index, delta) => {
        const next = [...services];
        const [service] = next.splice(index, 1);
        next.splice(index + delta, 0, service);
        setAttributes({ services: next });
    };
    const setListItem = (index, key, itemIndex, value) =>
        updateService(index, { [key]: services[index][key].map((v, i) => (i === itemIndex ? value : v)) });
    const updateContact = (index, contactIndex, changes) =>
        updateService(index, {
            contacts: services[index].contacts.map((c, i) => (i === contactIndex ? { ...c, ...changes } : c)),
        });
    const updateLink = (index, contactIndex, linkIndex, changes) =>
        updateContact(index, contactIndex, {
            links: services[index].contacts[contactIndex].links.map((l, i) => (i === linkIndex ? { ...l, ...changes } : l)),
        });

    const field = (tagName, className, value, onChange, placeholder, formats = []) => (
        <RichText tagName={tagName} className={className} value={value} onChange={onChange} placeholder={placeholder} allowedFormats={formats} />
    );

    return (
        <>
            <BlockControls>
                <ToolbarGroup>
                    <ToolbarButton
                        icon="plus-alt2"
                        label={__('Ajouter un service', 'esquare')}
                        onClick={() => setAttributes({ services: [...services, EMPTY_SERVICE] })}
                    />
                </ToolbarGroup>
            </BlockControls>

            <InspectorControls>
                <PanelBody title={__('Sommaire', 'esquare')}>
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={__('Afficher le sommaire', 'esquare')}
                        help={__('Une bande de liens vers chaque service, au-dessus de l’annuaire.', 'esquare')}
                        checked={showToc}
                        onChange={(value) => setAttributes({ showToc: value })}
                    />
                </PanelBody>
                {services.map((service, index) => (
                    <PanelBody
                        key={index}
                        initialOpen={false}
                        title={sprintf(
                            /* translators: 1: service number, 2: service name. */
                            __('Service %1$d : %2$s', 'esquare'),
                            index + 1,
                            plain(service.name) || __('sans nom', 'esquare')
                        )}
                    >
                        <TextControl
                            __nextHasNoMarginBottom
                            __next40pxDefaultSize
                            label={__('Libellé dans le sommaire', 'esquare')}
                            help={__('Vide = le nom du service.', 'esquare')}
                            value={service.tocLabel ?? ''}
                            onChange={(value) => updateService(index, { tocLabel: value })}
                        />
                        <div style={{ marginTop: '12px' }}>
                            <TextControl
                                __nextHasNoMarginBottom
                                __next40pxDefaultSize
                                label={__('Ancre (lien #…)', 'esquare')}
                                help={__('Vide = déduite du nom.', 'esquare')}
                                value={service.anchor ?? ''}
                                onChange={(value) => updateService(index, { anchor: value.replace(/[^A-Za-z0-9_-]/g, '-').toLowerCase() })}
                            />
                        </div>

                        <h3 style={{ margin: '20px 0 8px', fontSize: '11px', textTransform: 'uppercase' }}>
                            {__('Coordonnées', 'esquare')}
                        </h3>
                        {service.contacts.map((contact, contactIndex) => (
                            <div key={contactIndex} style={{ borderTop: '1px solid #ddd', padding: '12px 0' }}>
                                <TextControl
                                    __nextHasNoMarginBottom
                                    __next40pxDefaultSize
                                    label={__('Intitulé (Téléphone, E-mail, Site web…)', 'esquare')}
                                    value={contact.label}
                                    onChange={(value) => updateContact(index, contactIndex, { label: value })}
                                />
                                {contact.links.map((link, linkIndex) => (
                                    <div key={linkIndex} style={{ marginTop: '8px', paddingLeft: '12px', borderLeft: '2px solid #ddd' }}>
                                        <TextControl
                                            __nextHasNoMarginBottom
                                            __next40pxDefaultSize
                                            label={__('Texte affiché', 'esquare')}
                                            value={link.text}
                                            onChange={(value) => updateLink(index, contactIndex, linkIndex, { text: value })}
                                        />
                                        <div style={{ marginTop: '8px' }}>
                                            <TextControl
                                                __nextHasNoMarginBottom
                                                __next40pxDefaultSize
                                                label={__('Lien', 'esquare')}
                                                help={__('Vide = déduit du texte pour un téléphone ou un e-mail.', 'esquare')}
                                                value={link.url}
                                                onChange={(value) => updateLink(index, contactIndex, linkIndex, { url: value.trim() })}
                                            />
                                        </div>
                                        {contact.links.length > 1 && (
                                            <Button
                                                variant="link"
                                                isDestructive
                                                onClick={() =>
                                                    updateContact(index, contactIndex, { links: contact.links.filter((_, i) => i !== linkIndex) })
                                                }
                                            >
                                                {__('Retirer ce lien', 'esquare')}
                                            </Button>
                                        )}
                                    </div>
                                ))}
                                <p style={{ marginTop: '8px' }}>
                                    <Button
                                        variant="tertiary"
                                        onClick={() => updateContact(index, contactIndex, { links: [...contact.links, { text: '', url: '' }] })}
                                    >
                                        {__('+ Lien sur la même ligne', 'esquare')}
                                    </Button>
                                    <Button
                                        variant="link"
                                        isDestructive
                                        onClick={() => updateService(index, { contacts: service.contacts.filter((_, i) => i !== contactIndex) })}
                                    >
                                        {__('Retirer la ligne', 'esquare')}
                                    </Button>
                                </p>
                            </div>
                        ))}
                        <Button variant="secondary" onClick={() => updateService(index, { contacts: [...service.contacts, EMPTY_CONTACT] })}>
                            {__('Ajouter une coordonnée', 'esquare')}
                        </Button>

                        <h3 style={{ margin: '20px 0 8px', fontSize: '11px', textTransform: 'uppercase' }}>
                            {__('Description', 'esquare')}
                        </h3>
                        {service.paragraphs.map((paragraph, i) => (
                            <p key={`p${i}`}>
                                {plain(paragraph).slice(0, 40) || __('paragraphe vide', 'esquare')}…{' '}
                                <Button
                                    variant="link"
                                    isDestructive
                                    onClick={() => updateService(index, { paragraphs: service.paragraphs.filter((_, j) => j !== i) })}
                                >
                                    {__('Retirer', 'esquare')}
                                </Button>
                            </p>
                        ))}
                        {service.bullets.map((bullet, i) => (
                            <p key={`b${i}`}>
                                • {plain(bullet) || __('puce vide', 'esquare')}{' '}
                                <Button
                                    variant="link"
                                    isDestructive
                                    onClick={() => updateService(index, { bullets: service.bullets.filter((_, j) => j !== i) })}
                                >
                                    {__('Retirer', 'esquare')}
                                </Button>
                            </p>
                        ))}
                        <p>
                            <Button variant="secondary" onClick={() => updateService(index, { paragraphs: [...service.paragraphs, ''] })}>
                                {__('+ Paragraphe', 'esquare')}
                            </Button>{' '}
                            <Button variant="secondary" onClick={() => updateService(index, { bullets: [...service.bullets, ''] })}>
                                {__('+ Puce', 'esquare')}
                            </Button>
                        </p>

                        <p style={{ marginTop: '20px' }}>
                            <Button variant="tertiary" disabled={index === 0} onClick={() => moveService(index, -1)}>
                                {__('↑ Monter', 'esquare')}
                            </Button>
                            <Button variant="tertiary" disabled={index === services.length - 1} onClick={() => moveService(index, 1)}>
                                {__('↓ Descendre', 'esquare')}
                            </Button>
                            <Button
                                variant="link"
                                isDestructive
                                onClick={() => setAttributes({ services: services.filter((_, i) => i !== index) })}
                            >
                                {__('Retirer le service', 'esquare')}
                            </Button>
                        </p>
                    </PanelBody>
                ))}
            </InspectorControls>

            <div {...blockProps}>
                {showToc && (
                    <nav className={TOC}>
                        <div aria-hidden="true" className="poster-grid absolute inset-0 -z-10 opacity-[0.10]"></div>
                        <div className="mx-auto max-w-7xl">
                            {field('h2', TOC_TITLE, tocTitle, (v) => setAttributes({ tocTitle: v }), __('Titre du sommaire', 'esquare'))}
                            <ul role="list" className="mt-5 flex flex-wrap gap-2.5">
                                {services.map((service, index) => (
                                    <li key={index}>
                                        <span className={CHIP}>{service.tocLabel || plain(service.name) || __('Service', 'esquare')}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </nav>
                )}

                <section className={SECTION}>
                    <div className="mx-auto max-w-7xl">
                        <div className="grid items-end gap-x-12 gap-y-3 lg:grid-cols-[2fr_3fr]">
                            <div>
                                <p className={EYEBROW}>
                                    <span aria-hidden="true" className="inline-block size-2 rounded-full bg-yellow opacity-[0.675]"></span>
                                    {field('span', '', eyebrow, (v) => setAttributes({ eyebrow: v }), __('Surtitre', 'esquare'))}
                                </p>
                                {field('h2', TITLE, title, (v) => setAttributes({ title: v }), __('Titre', 'esquare'))}
                            </div>
                            {(isSelected || intro) &&
                                field('p', INTRO, intro, (v) => setAttributes({ intro: v }), __('Introduction (optionnelle)', 'esquare'), ['core/bold', 'core/italic', 'core/link'])}
                        </div>

                        <div className="mt-16">
                            {services.map((service, index) => (
                                <article key={index} className={`${ARTICLE}${index === services.length - 1 ? ' border-b' : ''}`}>
                                    <div>
                                        {field('h3', NAME, service.name, (v) => updateService(index, { name: v }), __('Nom du service', 'esquare'))}
                                        {(isSelected || service.subtitle) &&
                                            field('p', SUBTITLE, service.subtitle, (v) => updateService(index, { subtitle: v }), __('Sous-titre (sigle, responsable…)', 'esquare'))}
                                        {service.contacts.length > 0 && (
                                            <ul role="list" className={`mt-6 space-y-3 ${FIGTREE} text-base`}>
                                                {service.contacts.map((contact, contactIndex) => (
                                                    <li key={contactIndex}>
                                                        <span className={CONTACT_LABEL}>{contact.label}</span>
                                                        <span className="flex flex-wrap gap-x-4 gap-y-1">
                                                            {contact.links.map((link, linkIndex) => (
                                                                <span key={linkIndex} className={LINK}>
                                                                    {link.text || __('(lien sans texte)', 'esquare')}
                                                                </span>
                                                            ))}
                                                        </span>
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                    <div className={BODY}>
                                        {service.paragraphs.map((paragraph, i) => (
                                            <RichText
                                                key={`p${i}`}
                                                tagName="p"
                                                className={i > 0 ? 'mt-4' : ''}
                                                value={paragraph}
                                                onChange={(v) => setListItem(index, 'paragraphs', i, v)}
                                                placeholder={__('Description', 'esquare')}
                                                allowedFormats={['core/bold', 'core/italic', 'core/link']}
                                            />
                                        ))}
                                        {service.bullets.length > 0 && (
                                            <ul role="list" className={`${service.paragraphs.length > 0 ? 'mt-4 ' : ''}space-y-2`}>
                                                {service.bullets.map((bullet, i) => (
                                                    <li key={i} className="flex gap-2.5">
                                                        <span aria-hidden="true" className="mt-2.5 size-1.5 shrink-0 rounded-full bg-yellow"></span>
                                                        <RichText
                                                            tagName="span"
                                                            value={bullet}
                                                            onChange={(v) => setListItem(index, 'bullets', i, v)}
                                                            placeholder={__('Puce', 'esquare')}
                                                            allowedFormats={['core/bold', 'core/italic']}
                                                        />
                                                    </li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                </article>
                            ))}
                        </div>
                    </div>
                </section>
            </div>
        </>
    );
}
