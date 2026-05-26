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
- **Atelier White** (`oklch(0.992 0.004 78)`, ≈ `#FCFAF5`): the highest neutral. Used sparingly — input surfaces, dialog interiors, anything that needs to read as "lifted off the cream".
- **Ink Black** (`oklch(0.18 0.012 250)`, ≈ `#0B1422`): the deepest possible text, reserved for editorial emphasis on cream. Never pure black.

### Named Rules

**The Ten-Percent Brass Rule.** Marche Brass covers ≤ 10% of any rendered screen. Its rarity is the point. If brass starts looking like a background, the design has failed.

**The Tinted Neutral Rule.** Never `#fff`. Never `#000`. Every neutral leans warm (cream, stone) or warm-cool (navy ink). Pure neutrals read as a placeholder; tinted neutrals read as a brand.

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
2. **Tonal layering** within the cream family (Newsprint Cream → Workshop Stone → Atelier White) when a section needs a softer step than full-navy contrast.
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

- **Structure:** Logo left, primary links centre or absent, single CTA right (the brass or navy pill).
- **Typography:** Figtree 500/600 at `0.875rem` for links; logo wordmark is Instrument Serif at `1.5rem`.
- **States:** Default link in `text-navy/70`; hover/active in full `text-navy`. Active page may carry a `1px` brass underline at `2px` offset; no boxed buttons inside nav.
- **Mobile:** Drawer or stacked sheet from the top. No hamburger gimmicks; the icon is a plain three-line glyph in `Atelier Navy`.

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
