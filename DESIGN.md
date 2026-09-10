---
name: e-Square
description: Visual system for the e-Square coworking, fablab, and community space in Marche-en-Famenne.
colors:
  atelier-navy: "#0E1F33"
  atelier-navy-soft: "#1A2D4A"
  atelier-navy-deep: "#081523"
  marche-brass: "#F2B33D"
  brass-light: "#FDE3A6"
  newsprint-cream: "#FAF8F3"
  workshop-stone: "#F1ECE3"
  kraft-famenne: "#E0D8C8"
  atelier-white: "#FCFAF5"
  ink-black: "#0B1422"
typography:
  display:
    fontFamily: "Instrument Serif, Georgia, serif"
    fontSize: "clamp(2.75rem, 7vw, 7rem)"
    fontWeight: 400
    lineHeight: 1.02
    letterSpacing: "-0.005em"
  headline:
    fontFamily: "Figtree, system-ui, sans-serif"
    fontSize: "clamp(2rem, 4vw, 3rem)"
    fontWeight: 700
    lineHeight: 1.1
    letterSpacing: "-0.01em"
  title:
    fontFamily: "Figtree, system-ui, sans-serif"
    fontSize: "1.25rem"
    fontWeight: 600
    lineHeight: 1.3
    letterSpacing: "normal"
  body:
    fontFamily: "Figtree, system-ui, sans-serif"
    fontSize: "1.0625rem"
    fontWeight: 400
    lineHeight: 1.55
    letterSpacing: "normal"
  label:
    fontFamily: "Geist Mono, ui-monospace, monospace"
    fontSize: "0.75rem"
    fontWeight: 600
    lineHeight: 1.4
    letterSpacing: "0.12em"
rounded:
  none: "0"
  sm: "0.25rem"
  md: "0.5rem"
  lg: "0.75rem"
  pill: "9999px"
spacing:
  "10": "0.5rem"
  "20": "1rem"
  "30": "1.5rem"
  "40": "2rem"
  "50": "3rem"
  "60": "4rem"
  "70": "6rem"
  "80": "8rem"
components:
  button-primary:
    backgroundColor: "{colors.marche-brass}"
    textColor: "{colors.atelier-navy}"
    rounded: "{rounded.pill}"
    padding: "0.75rem 1.5rem"
  button-primary-hover:
    backgroundColor: "{colors.brass-light}"
    textColor: "{colors.atelier-navy}"
  button-secondary:
    backgroundColor: "{colors.atelier-navy}"
    textColor: "{colors.atelier-white}"
    rounded: "{rounded.pill}"
    padding: "0.5rem 1.25rem"
  button-secondary-hover:
    backgroundColor: "{colors.atelier-navy-soft}"
    textColor: "{colors.atelier-white}"
  button-ghost:
    backgroundColor: "transparent"
    textColor: "{colors.atelier-white}"
    rounded: "{rounded.pill}"
    padding: "0.75rem 1.5rem"
  card-surface:
    backgroundColor: "{colors.newsprint-cream}"
    textColor: "{colors.atelier-navy}"
    rounded: "{rounded.none}"
    padding: "1.5rem"
  card-surface-dark:
    backgroundColor: "{colors.atelier-navy}"
    textColor: "{colors.atelier-white}"
    rounded: "{rounded.none}"
    padding: "1.5rem"
  input-text:
    backgroundColor: "{colors.atelier-white}"
    textColor: "{colors.atelier-navy}"
    rounded: "{rounded.sm}"
    padding: "0.75rem 1rem"
---

# Design System: e-Square

## 1. Overview

**Creative North Star: "The Communal Atelier"**

e-Square's interface is a communal atelier rendered in pixels: cream walls, deep navy beams, brass-coloured signage above every door. It is a place you can walk into. It is not a SaaS coworking template, not a sterile municipal portal, not a creative-agency stage. It is a public-spirited workshop with the lights on and the doors open, in Marche-en-Famenne and nowhere else.

The system runs on **editorial restraint**. Display type does the talking. Whitespace carries the rhythm. Decoration is rare, intentional, and earned. Brass-yellow appears in small doses — a stamp, a label, a single italicised word — never as a wash. Navy holds structure; cream is air; mono labels mark categories the way a kraft-paper tag marks a drawer in a workshop. The voice is **warm, confident, locally-rooted**: declarative French, no jargon, no hedging.

What this system rejects: WeWork-style purple gradients and hero-metric blocks; sterile public-sector portals; trendy AI/crypto neon and glassmorphism; gimmicky agency maximalism with scroll-hijack and oversize cursors. If a swap of logo would let it belong to another coworking, the design has failed.

**Key Characteristics:**

- Editorial typography (Instrument Serif display + Figtree body + Geist Mono labels), Figtree never compensates for missing serif weight by going bolder.
- Tinted neutral palette: warm cream and warm off-white, not pure `#fff`; navy tinted blue-violet, not pure `#000`.
- Brass-yellow accent ≤ 10% of any given screen.
- Flat surfaces by default; structure carried by hairline borders and color contrast, not shadows.
- Pill CTAs (full radius) on otherwise hard-edged surfaces — a deliberate hospitality marker.
- Mono uppercase eyebrows with letter-spaced labels mark sections like signage.

## 2. Colors: The Atelier Palette

A warm, tinted-neutral palette anchored by **Atelier Navy** and lifted by a single accent, **Marche Brass**. Whites and creams are tinted toward warmth; navies are tinted toward blue-violet. Pure `#fff` and pure `#000` are absent on purpose.

### Primary

- **Atelier Navy** (`#0E1F33`): structural color. Used for body text on cream, hero backgrounds, primary nav, and the secondary-button surface. Carries identity weight; appears on roughly 35–55% of typical screens.
- **Atelier Navy Soft** (`#1A2D4A`): hover state on `Atelier Navy` surfaces, and a tonal step for layered navy sections.
- **Atelier Navy Deep** (`#081523`): the floor — used for dramatic hero backdrops and the deepest tonal layer when navy stacks on navy. Reserved for moments that need weight.

### Secondary

- **Marche Brass** (`#F2B33D`): the brand accent. Used on primary CTAs, italicised display fragments (`<em>co-créer.</em>`), eyebrow labels, and the skip-link. **Never** as a full-surface wash and **never** as a gradient. Appears on ≤ 10% of any screen.
- **Brass Light** (`#FDE3A6`): hover-on-brass and soft brass tints (annotated info blocks, highlight bands). Subordinate to Marche Brass.

### Neutral

- **Newsprint Cream** (`#FAF8F3`): the default page surface. The "newsprint" the system reads against. Tinted warm so the eye softens.
- **Workshop Stone** (`#F1ECE3`): the second tonal layer. Used to step a section away from Newsprint Cream without leaving the cream family — alternating section backgrounds, callout strips, kraft-paper card surfaces.
- **Kraft Famenne** (`#E0D8C8`): the lowest warm neutral, one step under Workshop Stone. Reserved for the closing surface of a page (the colophon footer), so that surface always steps *down* in tone no matter what precedes it, including the one page that ends on a Workshop Stone band. Not a general section background: if it starts alternating with cream mid-page, it has lost its job.
- **Atelier White** (`oklch(0.992 0.004 78)`, ≈ `#FCFAF5`): the highest neutral. Used sparingly — input surfaces, dialog interiors, anything that needs to read as "lifted off the cream".
- **Ink Black** (`oklch(0.18 0.012 250)`, ≈ `#0B1422`): the deepest possible text, reserved for editorial emphasis on cream. Never pure black.

### Named Rules

**The Ten-Percent Brass Rule.** Marche Brass covers ≤ 10% of any rendered screen. Its rarity is the point. If brass starts looking like a background, the design has failed.

**The Tinted Neutral Rule.** Never `#fff`. Never `#000`. Every neutral leans warm (cream, stone) or warm-cool (navy ink). Pure neutrals read as a placeholder; tinted neutrals read as a brand.

**The Closing Surface Rule.** A page ends on an extreme, never on a mid-tone. The footer is either the deepest surface on the page or the lightest, and never a value that sits between two content bands. This is why Kraft Famenne exists: navy already means *content* on this site (hero, contact blocks), so it can no longer mean *chrome*, and a navy footer under a navy-deep section reads as one more section. The footer inverts instead, and Kraft sits below every content neutral so the inversion holds on every page.

**The No-Gradient Rule.** No accent gradients. The `theme.json` may declare `navy-deep` and `navy-fade` for legacy block patterns, but new compositions avoid gradient surfaces and gradient text entirely.

## 3. Typography

**Display Font:** Instrument Serif (with Georgia, serif).
**Body Font:** Figtree (with system-ui, sans-serif).
**Label/Mono Font:** Geist Mono (with ui-monospace, monospace).

**Character:** Instrument Serif is a contemporary high-contrast serif with the restraint of a literary review; it carries the headlines as if they were chapter openings. Figtree is the workaday voice — warm, geometric, comfortable at body sizes, deliberately not "tech-startup neutral". Geist Mono is the signage and the labels, marking categories the way a clip-on label marks a hardware drawer.

### Hierarchy

- **Display** (Instrument Serif, 400, `clamp(2.75rem, 7vw, 7rem)`, line-height 1.02, slight negative tracking): the hero headline and section openers that need to read like editorial moments. Used at most once per section.
- **Headline** (Figtree, 700, `clamp(2rem, 4vw, 3rem)`, line-height 1.1): primary headings inside sections. Bolder than Display in weight but smaller in size and visual presence; Display still leads.
- **Title** (Figtree, 600, `1.25rem`, line-height 1.3): card titles, dt terms, sub-section openers.
- **Body** (Figtree, 400, `1.0625rem`, line-height 1.55): all running text. Capped at **65–75ch** per line using `max-w-[52ch]` / `max-w-[48ch]` conventions in the existing markup. Body color is `Atelier Navy` at 70–85% opacity on cream surfaces; never the full navy except for emphasis.
- **Label** (Geist Mono, 600, `0.75rem`, letter-spacing `0.12em`, uppercase): mono eyebrows above headlines, breadcrumbs, section markers, time/place tags. Always uppercase; always tracked wide.

### Named Rules

**The Italic-Brass Rule.** When the display headline carries a single italicised fragment (`<em>co-créer.</em>`), that fragment may be coloured `Marche Brass`. This is the only place display type takes a colour beyond Atelier Navy or Atelier White.

**The Mono-Signage Rule.** Mono labels (Geist Mono uppercase) appear above headlines like wayfinding signage. They earn their tracking by being short — typically 2–6 words. Never set body copy in Mono.

**The No-Gradient-Text Rule.** Forbidden. Display headlines are a single solid colour. Emphasis comes from weight, size, or italic, never `background-clip: text`.

## 4. Elevation

This system is **flat by default**. Surfaces sit at their declared colours and do not lift. Depth is carried by three mechanisms in order of preference:

1. **Colour contrast** between adjacent sections (cream → navy → cream).
2. **Tonal layering** within the cream family (Atelier White → Newsprint Cream → Workshop Stone → Kraft Famenne) when a section needs a softer step than full-navy contrast.
3. **Hairline borders** at `border-black/5`, `border-yellow/30`, or `border-white/10` carrying structure where colour shifts alone are too quiet.

Drop shadows on cards or surfaces are **forbidden**. Glassmorphism (`backdrop-filter`) is forbidden as a decorative reflex; rare exceptions exist for overlay nav on top of a hero image, where a `backdrop-blur` plus `bg-white/10 ring-white/30` reads as a window pane rather than a glass card.

### Named Rules

**The No-Shadow Rule.** No `box-shadow` on cards, dialogs, dropdowns, or hero blocks. If a surface feels flat, give it a hairline border or shift its background tone — not a shadow.

**The Hairline Rule.** When a border is needed, it is `1px`. Coloured side-stripes of `2px` or more (the cliché coloured left-border accent) are forbidden absolutely.

## 5. Components

Every component is rendered with the **Communal Atelier** in mind: a hand-stamped object in a well-lit workshop, not a slick SaaS module.

### Buttons

- **Shape:** Pill — full `rounded-full` radius (`9999px`). Pill on otherwise hard-edged surfaces is the signature hospitality marker.
- **Primary (Brass CTA):** `bg-yellow` (`Marche Brass`) with `text-navy`, `px-6 py-3`, Figtree 600. Used for the dominant action per surface ("Découvrir nos services", "Louer une salle", "Réserver"). One per section maximum.
- **Secondary (Navy CTA):** `bg-navy` (`Atelier Navy`) with `text-white`, `px-5 py-2`, Figtree 500/600. For the secondary path; appears in the header chrome and in stacked-CTA blocks alongside the brass primary.
- **Ghost:** transparent surface with `ring-white/30` and `text-white`, used on navy hero backdrops as a third option. Never used on cream surfaces.
- **Hover:** Brass → `bg-yellow-soft`; Navy → `bg-navy-soft`; Ghost → `bg-white/20`. No translate, no shadow lift. Colour shift only.
- **Focus:** `outline: 2px solid var(--color-navy-deep); outline-offset: 2px`. Always visible; never removed for "design reasons".
- **Arrow affordance:** CTAs end with an aria-hidden `→` glyph. The arrow signals "go", the colour does not.

### Cards / Containers

- **Corner Style:** Hard corners (`rounded-none`) by default. Cards are flat panels, not pills. Hairline border or background tone carries the boundary.
- **Background:** Newsprint Cream by default; Workshop Stone for a softer step; Atelier Navy for the inverse, dark-on-light surfaces.
- **Shadow Strategy:** None. Refer to the No-Shadow Rule.
- **Border:** Optional `1px` hairline in `border-black/5` on cream, `border-white/10` on navy.
- **Internal Padding:** `1.5rem` (spacing.30) at small; `2rem`–`3rem` (spacing.40–50) at large compositions. Never below `1rem` for a real card.

### Inputs / Fields

- **Style:** Atelier White surface, `1px` hairline `border-black/10`, `rounded-sm` (`0.25rem`) — quietly soft corners, not pills.
- **Focus:** `border-yellow` (`Marche Brass`) plus a `ring-2 ring-yellow/30` glow. The brass is the focus signal; it must be visible against cream and against navy.
- **Error:** Border shifts to a tinted clay-red (declared on first use; not in the current palette — when added, must be tinted, not pure `#dc2626`). Error text in body weight, mono **label** for the field name.

### Navigation

The site header is a **dark bar**, not a cream one: the page opens on Atelier Navy Deep and drops into cream below it. This is the inverse of every other surface in the system, and it is deliberate — the bar reads as the painted lintel over the atelier door.

- **Surface:** `Atelier Navy Deep` (`#081523`), full-bleed edge to edge, `76px` tall, closed by a bottom hairline at 12% `Newsprint Cream`.
- **Structure:** Wordmark left, primary links in a single right-aligned row, a `1px` white/15 divider, then the brass Contact pill last. No boxed buttons inside the link row; the pill is the one exception and it terminates the row.
- **Typography:** Bar and panel links both Figtree 500 at `1rem`. The bar runs one step above the `0.875rem` this system uses elsewhere for chrome, traded knowingly against the width budget below. The wordmark is **Figtree 800 at `1.875rem`, `tracking-tight`** — a logotype, not display type, which is why it does not take Instrument Serif.
- **States:** Rest is `text-white/90` (14.6:1). Hover fills to `--color-white` over a `white/8` wash. Active page takes `--color-white` plus a `1.5px` brass underline inset to match the link's own padding. Never pure `#fff` — the Tinted Neutral Rule holds here too. **The bar runs a bright resting state on purpose**, which leaves only `1.23:1` between rest and active: the brass underline, not the text colour, is what marks the current page, and the `white/8` wash, not the text colour, is what marks hover. Strengthen the underline before reaching for the text colour.
- **Focus:** `2px solid Marche Brass` at `2px` offset. **This is the dark-surface exception to the system default**, which is `Atelier Navy Deep` — the bar's own background, and therefore a 1:1 invisible ring if applied literally. The offset matters: it leaves a navy gap between ring and control, which is what keeps one brass ring legible even around the brass Contact pill.
- **Mobile:** Stacked panel below the bar. No hamburger gimmicks; a plain three-line glyph that swaps to a close glyph when open. `aria-expanded` stays in sync, Escape closes and returns focus to the trigger, and the panel force-closes when the bar takes over so `aria-expanded` never strands on a hidden control.

**The First-Paint Floor Rule.** Tailwind ships from the in-browser compiler, so utility classes and the `@theme` custom properties are both **absent from the first paint** while `style.css` is already applying. Any component whose surface colour comes from a utility must declare that colour — **background and foreground together** — in `style.css`, as literal hex, never as `var(--color-*)`. Declaring only the background moves the failure rather than fixing it: the bar goes navy while the link colours are still missing, and every link turns navy-on-navy. This bit the header (a white current-page link on cream, 1.04:1) and it generalises to every dark surface in the theme.

**The Bar Budget Rule.** The link row is `whitespace-nowrap` and does not wrap, so it has a hard width budget: roughly `1216px` at the `xl` breakpoint. At `1rem` the current ten links plus the pill spend about `1164px` of it — **52px spare, about 4%**. Note that `xl:` is a rem media query and rem in a media query always resolves against the initial `16px`, ignoring the user's root font-size, so a raised default font scales the content but *not* the breakpoint that would hand off to the burger: an `18px` root overflows the bar by `74px`. Dropping the row to `0.875rem` buys the margin back (`142px` spare, and `18px` fits), and is the first lever if the row grows. Before adding an eleventh link, measure — the honest fix is fewer top-level destinations.

### Eyebrow / Section Marker

A signature component. Mono uppercase Geist Mono, `0.75rem`, letter-spacing `0.12em`, often in `Marche Brass`. Sits above a Display or Headline as wayfinding ("Marche-en-Famenne · Espace collaboratif"). Treat it like a clip-on workshop label.

### Marquee Strip (optional, used in current explorations)

A keyframe-animated horizontal text band (`@keyframes esq-marquee`) that runs partner names or current events. Acceptable when gated on `prefers-reduced-motion: no-preference`; the no-motion fallback is the same content rendered as a static comma-separated list.

## 6. Do's and Don'ts

### Do:

- **Do** keep Marche Brass to ≤ 10% of any rendered screen (Ten-Percent Brass Rule).
- **Do** use Instrument Serif for display headlines and reserve Figtree for everything below.
- **Do** mark sections with Geist Mono uppercase eyebrows tracked at `0.12em`.
- **Do** tint every neutral: warm cream and warm navy, never `#fff` and never `#000`.
- **Do** cap body type at 65–75ch (`max-w-[48ch]`–`max-w-[65ch]`).
- **Do** carry structure with hairline `1px` borders and tonal layering, not shadows.
- **Do** use pill (full-radius) buttons against otherwise hard-edged surfaces — the signature hospitality marker.
- **Do** ground every screen in Marche-en-Famenne specifics (place names, real partners, real numbers). Specificity over genericity is the brand line from PRODUCT.md.
- **Do** make all interactive elements keyboard-navigable with a visible `2px` navy-deep focus outline, per WCAG 2.2 AA.
- **Do** gate any decorative motion (marquee, parallax, scroll choreography) on `prefers-reduced-motion: no-preference`.

### Don't:

- **Don't** use generic SaaS / startup coworking aesthetics: no WeWork-style purple gradients, no "Join the future of work" hero-metric blocks, no identical icon-card grids.
- **Don't** revert to a sterile corporate or municipal portal look: no bland public-sector layout, no stock photos of diverse hands on a table, no condescending welcome banners.
- **Don't** use gradient text. `background-clip: text` on a gradient is forbidden (No-Gradient-Text Rule).
- **Don't** use coloured side-stripe borders (`border-left: 4px solid var(--color-yellow)` etc.). Hairline only, or full border (Hairline Rule).
- **Don't** add drop shadows to cards, dialogs, or any default surface (No-Shadow Rule). Glassmorphism is forbidden as a decorative reflex.
- **Don't** flood Marche Brass beyond 10%. If brass starts looking like a background, the design has failed.
- **Don't** use pure `#fff` or pure `#000` (Tinted Neutral Rule).
- **Don't** chase trendy AI / crypto neon: no neon-on-black, no animated gradient meshes as default backgrounds.
- **Don't** reach for creative-agency clichés: oversize cursors, scroll-hijack, gimmicky reveal animations.
- **Don't** use Mono for body copy. Mono is signage; body is Figtree.
- **Don't** encode meaning in colour alone. Brass accent must always pair with text, weight, or a shape affordance — colourblind safety per PRODUCT.md.
- **Don't** write em dashes (`—` used as separator-em) in UI copy. Use commas, colons, semicolons, periods, or parentheses.
