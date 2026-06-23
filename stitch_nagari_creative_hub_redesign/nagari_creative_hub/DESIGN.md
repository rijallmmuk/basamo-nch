---
name: Nagari Creative Hub
colors:
  surface: '#f7f9fb'
  surface-dim: '#d8dadc'
  surface-bright: '#f7f9fb'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f2f4f6'
  surface-container: '#eceef0'
  surface-container-high: '#e6e8ea'
  surface-container-highest: '#e0e3e5'
  on-surface: '#191c1e'
  on-surface-variant: '#41474e'
  inverse-surface: '#2d3133'
  inverse-on-surface: '#eff1f3'
  outline: '#72787f'
  outline-variant: '#c1c7cf'
  surface-tint: '#326286'
  primary: '#003857'
  on-primary: '#ffffff'
  primary-container: '#1b4f72'
  on-primary-container: '#92c0e9'
  inverse-primary: '#9dcbf4'
  secondary: '#735c00'
  on-secondary: '#ffffff'
  secondary-container: '#fed33e'
  on-secondary-container: '#725b00'
  tertiary: '#003d1c'
  on-tertiary: '#ffffff'
  tertiary-container: '#00572a'
  on-tertiary-container: '#54d280'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#cce5ff'
  primary-fixed-dim: '#9dcbf4'
  on-primary-fixed: '#001e31'
  on-primary-fixed-variant: '#154b6d'
  secondary-fixed: '#ffe085'
  secondary-fixed-dim: '#ecc22c'
  on-secondary-fixed: '#231b00'
  on-secondary-fixed-variant: '#574500'
  tertiary-fixed: '#7efba4'
  tertiary-fixed-dim: '#61de8a'
  on-tertiary-fixed: '#00210c'
  on-tertiary-fixed-variant: '#005228'
  background: '#f7f9fb'
  on-background: '#191c1e'
  surface-variant: '#e0e3e5'
typography:
  display-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '800'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  display-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: '1.2'
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 30px
    fontWeight: '700'
    lineHeight: '1.3'
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: '1.3'
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '600'
    lineHeight: '1.4'
  body-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 18px
    fontWeight: '400'
    lineHeight: '1.6'
  body-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 16px
    fontWeight: '400'
    lineHeight: '1.6'
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: '1.2'
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '500'
    lineHeight: '1.2'
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  unit: 8px
  container-max: 1280px
  gutter: 24px
  margin-desktop: 48px
  margin-mobile: 20px
  section-gap: 80px
---

## Brand & Style
The design system for this product bridges the gap between global professional standards and local cultural identity. The brand personality is **Visionary, Rooted, and Prestigious**. It serves a community of creators, entrepreneurs, and stakeholders where trust is paramount, but innovation is the engine.

The visual style is a blend of **Corporate Modernism** and **Tactile Heritage**. We leverage "Plus Jakarta Sans" for its optimistic and contemporary curves, paired with a layout philosophy that prioritizes breathing room and clarity. To ground the "Tech-Forward" aesthetic in its "Nagari" roots, the UI utilizes subtle geometric patterns inspired by Minangkabau motifs—specifically *Pucuk Rabuang* (growth) and *Bada Mudiak* (harmony)—applied as ultra-low opacity watermark layers or decorative dividers.

## Colors
The palette is anchored by **NCH Deep Blue**, a color of stability and authority. **Minang Gold** is reserved for high-impact accents, calls to action, and highlighting achievements, while **Sustainable Green** denotes success, growth, and positive status changes.

The background uses a crisp off-white (#F8FAFC) to differentiate from pure white surface containers, creating a subtle layered effect that feels premium rather than stark. Text colors utilize a slate-based hierarchy to maintain readability without the harshness of pure black.

## Typography
We use **Plus Jakarta Sans** across all levels to maintain a cohesive, friendly, yet professional tone. The typeface's wide apertures and modern geometric construction make it exceptionally readable on digital screens while feeling more distinctive than a standard neo-grotesque.

- **Headlines:** Use Bold or ExtraBold weights with slightly tightened letter spacing for a "designed" editorial feel.
- **Body:** Use Regular weight with generous line height (1.6) to ensure long-form content is accessible and easy to scan.
- **Labels:** Use Medium or SemiBold weights in smaller sizes, often with slight uppercase tracking to denote metadata or category tags.

## Layout & Spacing
This design system utilizes a **12-column fluid grid** for desktop and a **4-column grid** for mobile. The layout philosophy is centered on "Generous Breathing Room," meaning we favor larger vertical gaps (80px+) between major sections to prevent information overload.

Spacing is based on an **8px base unit**. All padding and margins should be multiples of 8 (e.g., 16, 24, 32, 48, 64). Components like cards and sections should use larger internal padding (32px+) to reinforce the clean, high-end creative hub aesthetic.

## Elevation & Depth
Depth is communicated through **Ambient Shadows** and **Tonal Layering**. We avoid harsh black shadows in favor of tinted shadows that use the Primary Blue or a Neutral Slate as their base.

- **Level 1 (Cards/Inputs):** A very soft, diffused shadow (0px 4px 20px rgba(27, 79, 114, 0.05)) and a subtle 1px border (#E2E8F0).
- **Level 2 (Hover states/Modals):** A more pronounced shadow to indicate interactivity (0px 12px 32px rgba(27, 79, 114, 0.12)).
- **Overlays:** Use a background blur (Backdrop Filter: 12px) on modal backdrops to maintain a sense of space while focusing the user's attention.

## Shapes
The shape language is defined by **High Circularity**. We use large border radii to evoke a sense of modern friendliness and accessibility.

- **Base Radius:** 16px (1rem) for standard components.
- **Large Radius (Cards/Sections):** 24px (1.5rem) or 32px (2rem) for primary containers.
- **Pill Radius:** Used for buttons and chips to create a distinct interactive language.

## Components

### Buttons
- **Primary:** Solid NCH Deep Blue with white text. Pill-shaped. On hover, apply a subtle gradient shift or a slight darkening.
- **Secondary:** Outlined with a 1.5px border of NCH Deep Blue.
- **Accent:** Solid Minang Gold for high-priority CTAs (e.g., "Join Hub").

### Cards
- White background (#FFFFFF) with a 24px border radius.
- Subtle 1px border (#F1F5F9).
- Apply a "Pucuk Rabuang" motif watermark in the bottom right corner at 3% opacity for a touch of heritage.
- Hover state: Lift the card by 4px and increase shadow intensity.

### Input Fields
- Background-filled (#F1F5F9) with no border in default state.
- 12px border radius.
- Focus state: White background with a 2px Minang Gold border to clearly indicate active input.

### Chips & Tags
- Used for categories (e.g., "Design," "Traditional Arts").
- Soft-filled backgrounds using 10% opacity of the Primary or Tertiary colors.
- SemiBold labels for readability.

### Decorative Dividers
- Instead of simple lines, use a repeating "Bada Mudiak" geometric pattern line at 10% opacity of Deep Blue to separate major content sections.