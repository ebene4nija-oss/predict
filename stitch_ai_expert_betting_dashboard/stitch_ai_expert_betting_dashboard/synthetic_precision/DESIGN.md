---
name: Synthetic Precision
colors:
  surface: '#0f1417'
  surface-dim: '#0f1417'
  surface-bright: '#353a3d'
  surface-container-lowest: '#0a0f12'
  surface-container-low: '#171c1f'
  surface-container: '#1b2023'
  surface-container-high: '#262b2e'
  surface-container-highest: '#313539'
  on-surface: '#dfe3e7'
  on-surface-variant: '#bdc8d1'
  inverse-surface: '#dfe3e7'
  inverse-on-surface: '#2c3134'
  outline: '#87929a'
  outline-variant: '#3e484f'
  surface-tint: '#7bd0ff'
  primary: '#8ed5ff'
  on-primary: '#00354a'
  primary-container: '#38bdf8'
  on-primary-container: '#004965'
  inverse-primary: '#00668a'
  secondary: '#ffb955'
  on-secondary: '#452b00'
  secondary-container: '#dc9100'
  on-secondary-container: '#4f3100'
  tertiary: '#52e87c'
  on-tertiary: '#003915'
  tertiary-container: '#2ccb63'
  on-tertiary-container: '#004f20'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#c4e7ff'
  primary-fixed-dim: '#7bd0ff'
  on-primary-fixed: '#001e2c'
  on-primary-fixed-variant: '#004c69'
  secondary-fixed: '#ffddb4'
  secondary-fixed-dim: '#ffb955'
  on-secondary-fixed: '#291800'
  on-secondary-fixed-variant: '#633f00'
  tertiary-fixed: '#6bff8f'
  tertiary-fixed-dim: '#4ae176'
  on-tertiary-fixed: '#002109'
  on-tertiary-fixed-variant: '#005321'
  background: '#0f1417'
  on-background: '#dfe3e7'
  surface-variant: '#313539'
typography:
  display-lg:
    fontFamily: Inter
    fontSize: 48px
    fontWeight: '700'
    lineHeight: 56px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Inter
    fontSize: 24px
    fontWeight: '600'
    lineHeight: 32px
  headline-sm:
    fontFamily: Inter
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  body-lg:
    fontFamily: Inter
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  body-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 20px
  data-lg:
    fontFamily: JetBrains Mono
    fontSize: 18px
    fontWeight: '600'
    lineHeight: 24px
  data-sm:
    fontFamily: JetBrains Mono
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 18px
  label-caps:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.05em
rounded:
  sm: 0.125rem
  DEFAULT: 0.25rem
  md: 0.375rem
  lg: 0.5rem
  xl: 0.75rem
  full: 9999px
spacing:
  unit: 4px
  container-padding: 32px
  section-gap: 48px
  card-gap: 16px
  gutter: 24px
---

## Brand & Style
The design system is built for a sophisticated sports betting AI platform that prioritizes clarity, analytical depth, and professional confidence. The aesthetic moves away from traditional, cluttered betting interfaces toward a clean, data-driven "Quant-Tech" style. 

The visual direction combines **Minimalism** with subtle **Glassmorphism**. It utilizes a deep navy palette to reduce eye strain during long sessions of data analysis, while using vibrant electric blue and amber accents to differentiate between machine-generated insights and human expert analysis. The interface should feel "spacious" even when displaying hundreds of data points, achieved through precise alignment, generous margins, and a strict information hierarchy.

## Colors
The palette is rooted in a near-black navy (`#0B0F17`) to establish a premium, high-tech environment. 

- **Primary (Electric Blue):** Reserved for AI-driven insights, circuit motifs, and core interactive elements.
- **Secondary (Amber):** Identifies human expertise, analyst-verified picks, and high-value alerts.
- **Functional Gradients:** A semantic "Confidence Gradient" scales from `#22C55E` (High Confidence) to `#EF4444` (Low Confidence).
- **Text Hierarchy:** Use `#F1F5F9` for primary readability and `#94A3B8` for metadata and secondary labels to maintain a clean visual density.

## Typography
The system employs a dual-font strategy:
1. **Inter** is the primary typeface for all UI labels, headings, and instructional text, chosen for its exceptional legibility and neutral, professional tone.
2. **JetBrains Mono** is utilized exclusively for numerical odds, percentages, and data points. This ensures that columns of numbers align perfectly, facilitating rapid comparison between different markets and picks.

Use `label-caps` for table headers and category descriptors to create clear structural separation without needing heavy borders.

## Layout & Spacing
The layout follows a **Fixed Grid** system on desktop (12 columns, 1200px max-width) to maintain the feeling of a professional dashboard. On mobile, it transitions to a fluid single-column layout with 16px side margins.

Key principles:
- **Vertical Rhythm:** Use a 4px baseline. Section headers should have at least 48px of top margin to provide visual "breathing room."
- **Data Density:** While the overall layout is spacious, internal card data uses tighter 8px and 12px gaps to keep related metrics connected.
- **Negative Space:** Do not fear empty space in the center of the dashboard; it directs focus to the key active widgets.

## Elevation & Depth
In this design system, depth is communicated through **Tonal Layers** and **Subtle Outlines** rather than heavy shadows.

- **Level 0 (Background):** `#0B0F17` — The foundation.
- **Level 1 (Surface):** `#151A24` — Used for primary cards and content containers.
- **Level 2 (Overlay):** `#1F2937` — Used for hover states, dropdown menus, and tooltips.

**Borders:** Use 1px borders with `#1E293B` (low-contrast) for card boundaries. AI-highlighted components may feature a subtle glow or 1px stroke of the primary blue to indicate active computation or importance.

## Shapes
The shape language is **Soft** and disciplined. 
- Standard components (Cards, Inputs) use a 4px (`rounded`) radius.
- Large containers or "Locked Pick" cards use an 8px (`rounded-lg`) radius.
- Interactive status elements (Pills, Badges) use full circular rounding (Pill-shaped) to distinguish them from structural data containers.

## Components
- **AI & Expert Badges:** Small 24px height pills. AI badges use an electric blue border and a 12px circuit icon. Expert badges include a 20px circular avatar mask and the analyst's surname.
- **Confidence Bar:** A 4px tall track with a subtle `#1F2937` background. The fill is a linear gradient from Green to Red; the fill width represents the probability percentage.
- **Locked Pick Card:** The background surface remains `#151A24` but the internal typography is blurred (8px blur). Centered on the card is a high-contrast Button (`#F1F5F9` background, `#0B0F17` text) labeled "Unlock Analysis."
- **Segmented Control:** A "track" styled in `#0B0F17` with a sliding `#1F2937` active state indicator. Text within the control should be `label-caps`.
- **Data Tables:** No vertical lines. Use horizontal 1px dividers in `#1E293B`. Odds must be rendered in `data-lg` (JetBrains Mono) for maximum precision.
- **Input Fields:** Dark fills (`#0B0F17`) with a 1px border that glows Electric Blue on focus.