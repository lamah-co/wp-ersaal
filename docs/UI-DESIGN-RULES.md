# Ersaal Admin — UI/UX Design Rules
**Version 1.0 · Source of Truth: Ersaal Landing Page**

> **⚠️ Important for Developers and AI Agents**
> Before creating or modifying any wp-ersaal admin UI,
> read this document first. These rules are **requirements**, not suggestions.

---

## 1. Design Principles

| Principle | Description |
|---|---|
| **Brand Fidelity** | All admin UI must reflect the Ersaal visual identity extracted from the official Landing Page |
| **Admin-Friendly** | Adapt marketing-scale design to a compact, functional admin context. No excessive animations or large hero elements |
| **RTL-First** | Arabic and RTL layouts are primary. LTR must work without separate CSS overrides |
| **Accessibility** | WCAG AA contrast, keyboard navigation, visible focus rings, semantic HTML |
| **Consistency** | Reuse shared tokens and components. No arbitrary one-off values |
| **Namespace Discipline** | All CSS classes must be prefixed with `ersaal-`. Never pollute `.wp-admin` globally |

---

## 2. Source of Truth

| Asset | Location (READ-ONLY) |
|---|---|
| Mantine Theme Config | `ersaal-landingpage/src/config/theme.tsx` |
| Color Palette | `theme.tsx → colors` |
| Font Declarations | `globals.css` |
| Input Style | `Input.module.css` |
| Button Style | `button.module.css` |
| Tab Style | `tabs.module.css` |
| Background Tokens | `backgroundColors.json` |
| Border Tokens | `borderColors.json` |
| Text State Tokens | `textStates.json` |
| **Implemented Tokens** | `wp-ersaal/admin/assets/css/ersaal-tokens.css` ← Use this in wp-ersaal |

> ❌ Never modify files inside `ersaal-landingpage/`.
> ✅ Read Landing Page → extract meaning → implement in `ersaal-tokens.css`.

---

## 3. Brand Colors

All values extracted from `ersaal-landingpage/src/config/theme.tsx`.

### Primary Palette — Blue (Brand Identity)

| Token | Variable | Hex | Usage |
|---|---|---|---|
| Primary | `--ersaal-color-primary` | `#1E2670` | Buttons, titles, links, active borders |
| Primary Hover | `--ersaal-color-primary-hover` | `#40478A` | Hover state |
| Primary Light | `--ersaal-color-primary-light` | `#E2E4F8` | Badges, info backgrounds |

### Accent Palette — Yellow (CTA / Active State)

| Token | Variable | Hex | Usage |
|---|---|---|---|
| Accent | `--ersaal-color-accent` | `#FFC10E` | Active tab bg, primary CTA on Landing Page |
| Accent Hover | `--ersaal-color-accent-hover` | `#CC9A0B` | Accent hover |
| Accent Light | `--ersaal-color-accent-light` | `#FFF9E7` | Warning backgrounds |

> In WordPress Admin context, the **primary blue (`#1E2670`)** is used for the main action button. Yellow is reserved for **active tab states** and accent highlights only. Do not make primary action buttons yellow.

### Status Colors

| Semantic | Token | Hex |
|---|---|---|
| Success | `--ersaal-color-success` | `#12B886` (teal[6]) |
| Success Light | `--ersaal-color-success-light` | `#E6FCF5` (teal[0]) |
| Danger | `--ersaal-color-danger` | `#FA5252` (red[6]) |
| Danger Light | `--ersaal-color-danger-light` | `#FFF5F5` (red[0]) |
| Warning | `--ersaal-color-warning` | `#CC9A0B` (yellow[6]) |
| Warning Light | `--ersaal-color-warning-light` | `#FFF9E7` (yellow[0]) |

### Neutral / Surface

| Token | Variable | Hex | Usage |
|---|---|---|---|
| Page BG | `--ersaal-bg-page` | `#FFFFFF` | Page background |
| Surface | `--ersaal-bg-surface` | `#F8F9FA` | Cards, panels, secondary areas |
| Surface 2 | `--ersaal-bg-surface-2` | `#F1F3F5` | Hover, nested, table row bg |
| Disabled BG | `--ersaal-bg-disabled` | `#E9ECEF` | Disabled inputs |

### Text

| Token | Variable | Hex | Usage |
|---|---|---|---|
| Primary | `--ersaal-text-primary` | `#212529` | Body text |
| Secondary | `--ersaal-text-secondary` | `#495057` | Secondary labels |
| Muted | `--ersaal-text-muted` | `#868E96` | Help text, timestamps |
| Placeholder | `--ersaal-text-placeholder` | `#ADB5BD` | Input placeholders |
| Title | `--ersaal-text-title` | `#1E2670` | Page titles (brand blue) |
| Error | `--ersaal-text-error` | `#FA5252` | Validation errors |
| Success | `--ersaal-text-success` | `#12B886` | Success messages |
| Warning | `--ersaal-text-warning` | `#CC9A0B` | Warning messages |

---

## 4. Typography

### Font Families

| Context | Font Stack | Notes |
|---|---|---|
| Primary (AR+EN) | `"SST Arabic", system-ui, -apple-system, sans-serif` | Official brand font |
| Arabic Fallback | `"DINNextLTArabic", system-ui, sans-serif` | Available in Light + Medium |
| English Fallback | `system-ui, -apple-system, "Segoe UI", sans-serif` | Until SST bundled |

> **Font Licensing Note:** SST Arabic and DINNextLTArabic are present in `ersaal-landingpage/src/assets/font/`. Do **not** copy font files to `wp-ersaal` until licensing is confirmed. Use `system-ui` as safe fallback. After confirmation, bundle into `admin/assets/fonts/` and declare via `@font-face` in `ersaal-tokens.css`.

### Weights

| Name | Value | Usage |
|---|---|---|
| Light | 300 | Subtle labels (if font bundled) |
| Normal | 400 | Body text, tab labels |
| Medium | 500 | Button text, card titles |
| Semibold | 600 | Form labels |
| Bold | 700 | Page titles, strong emphasis |

### Admin-Scale Font Sizes

*(Reduced from Landing Page marketing sizes for compact admin context)*

| Token | Size | Usage |
|---|---|---|
| `--ersaal-text-xs` | 11px | Timestamps, meta info |
| `--ersaal-text-sm` | 12px | Helper text, small labels |
| `--ersaal-text-base` | 13px | Table cells (WP Admin standard) |
| `--ersaal-text-md` | 14px | Body, form labels |
| `--ersaal-text-lg` | 15px | Section labels, card titles |
| `--ersaal-text-xl` | 18px | Section headers |
| `--ersaal-text-2xl` | 22px | Page titles |

---

## 5. Spacing System

Source: `theme.tsx → spacing (xxs:2 xs:4 sm:8 md:12 lg:16 xl:24 xxl:32)`

| Token | Value | Named Alias | Usage |
|---|---|---|---|
| `--ersaal-space-1` | 2px | `--ersaal-space-xxs` | Micro gaps |
| `--ersaal-space-2` | 4px | `--ersaal-space-xs` | Tight spacing |
| `--ersaal-space-3` | 8px | `--ersaal-space-sm` | Icon-text gap, small padding |
| `--ersaal-space-4` | 12px | `--ersaal-space-md` | Form group margin |
| `--ersaal-space-5` | 16px | `--ersaal-space-lg` | Card inner, default padding |
| `--ersaal-space-7` | 24px | `--ersaal-space-xl` | Card padding, section dividers |
| `--ersaal-space-8` | 32px | `--ersaal-space-xxl` | Section gaps |
| `--ersaal-space-9` | 40px | — | Major section separators |

### Named Component Spacing

| Usage | Value |
|---|---|
| Card Padding | `24px (--ersaal-space-xl)` |
| Form Group Gap | `16px (--ersaal-space-lg)` |
| Section Gap | `32px (--ersaal-space-xxl)` |
| Grid Gap | `16px (--ersaal-space-lg)` |

---

## 6. Border Radius

Source: `theme.tsx → radius`

| Token | Value | Usage |
|---|---|---|
| `--ersaal-radius-xs` | 2px | — |
| `--ersaal-radius-sm` | 4px | Badges, status chips |
| `--ersaal-radius-md` | 8px | Cards, inputs, modals (admin standard) |
| `--ersaal-radius-lg` | 16px | Large containers |
| `--ersaal-radius-pill` | 9999px | Buttons, tabs (matches Landing Page `xxl`) |

> **Important:** Landing Page uses `radius: xl (24px)` for inputs. In WordPress Admin, `--ersaal-radius-md (8px)` is more appropriate. Pill-shaped buttons (`border-radius: 9999px`) are retained as they match the Landing Page CTA style.

---

## 7. Shadows

Source: `theme.tsx → shadows` + admin adaptation

| Token | Value | Usage |
|---|---|---|
| `--ersaal-shadow-none` | `none` | Flat surfaces |
| `--ersaal-shadow-sm` | `0 1px 2px rgba(0,0,0,0.06)` | Subtle card elevation |
| `--ersaal-shadow-md` | `1px 1px 3px rgba(0,0,0,0.12)` | Hovered cards, modals |
| `--ersaal-shadow-focus` | `0 0 0 2px rgba(30,38,112,0.25)` | Keyboard focus ring (blue) |
| `--ersaal-shadow-focus-accent` | `0 0 0 2px rgba(255,193,14,0.4)` | Accent focus ring (yellow) |

### ❌ Prohibited
- Heavy drop shadows
- Neon glow effects
- Glassmorphism
- Floating cards with intense shadow
- `box-shadow: inset` for decorative purposes

---

## 8. Buttons

### Variants

| Variant | Class | Usage |
|---|---|---|
| Primary | `.ersaal-btn-primary` | Main action: Save, Send SMS, Activate |
| Secondary | `.ersaal-btn-secondary` | Alternative actions, Cancel |
| Ghost | `.ersaal-btn-ghost` | Low-priority actions |
| Danger | `.ersaal-btn-danger` | Delete, Bulk Delete, destructive actions |

### States (all buttons must implement)

| State | Implementation |
|---|---|
| Default | Base style |
| Hover | `background/border color change` |
| Focus | `outline: 2px solid var(--ersaal-color-primary); outline-offset: 2px` |
| Active | Darker shade |
| Disabled | `opacity: 0.5; pointer-events: none` |

### Sizing

| Variant | Height | Padding-inline | Font size |
|---|---|---|---|
| Default | 36px | 24px | 14px |
| Small | 28px | 12px | 12px |

### Rules
- ✅ One Primary button per section/form
- ✅ Danger class ONLY for destructive actions
- ✅ Pill shape (`border-radius: 9999px`) matches Landing Page CTA
- ❌ Do not create custom button colors outside the defined variants
- ❌ Do not use yellow for primary action buttons in admin

---

## 9. Inputs & Form Fields

### Structure (Every field must follow this)

```html
<div class="ersaal-field">
  <label class="ersaal-label" for="field-id">Label *</label>
  <input class="ersaal-input" id="field-id" type="text" />
  <p class="ersaal-field-help">Helper text or error message</p>
</div>
```

### Types

| Type | Class | Notes |
|---|---|---|
| Text | `.ersaal-input` | Standard |
| Phone | `.ersaal-input` + `dir="ltr"` | Numbers always LTR |
| Password | `.ersaal-input` | type="password" |
| Textarea | `.ersaal-textarea` | `resize: vertical`, min-height: 80px |
| Select | `.ersaal-select` | Native select element |

### States

| State | Behavior |
|---|---|
| Normal | Border: `--ersaal-border-input` (`#CED4DA`) |
| Focus | Border: `--ersaal-color-primary`, shadow: `--ersaal-shadow-focus` |
| Error | Border: `--ersaal-border-danger`, + error message below |
| Disabled | BG: `--ersaal-bg-disabled`, `cursor: not-allowed` |
| Readonly | BG: `--ersaal-bg-surface`, no focus border |

### Rules
- ✅ Always use `<label>` — never Placeholder-only fields
- ✅ Helper text below input in a `<p class="ersaal-field-help">`
- ✅ Phone numbers and IDs always use `dir="ltr"`
- ❌ Do not use inline styles on individual inputs

---

## 10. Cards

### Variants

| Variant | Class | Usage |
|---|---|---|
| Standard | `.ersaal-card` | Settings sections, content areas |
| Stats | `.ersaal-card-stat` | Dashboard metric tiles |

### Rules
- ✅ `border: 1px solid --ersaal-border-color`
- ✅ `border-radius: --ersaal-radius-card (8px)`
- ✅ `padding: --ersaal-card-padding (24px)`
- ✅ Subtle shadow: `--ersaal-shadow-sm`
- ❌ Do not nest cards more than 2 levels deep
- ❌ Do not create Cards with `border-radius > 16px`

---

## 11. Tables

### Structure Rules

```html
<table class="ersaal-table widefat fixed striped">
  <thead>
    <tr>
      <th class="manage-column"># Column</th>
    </tr>
  </thead>
  <tbody>...</tbody>
</table>
```

| Element | Rule |
|---|---|
| Header | BG: `--ersaal-bg-surface`, text: `--ersaal-text-secondary`, `font-weight: 600` |
| Row | Alternating with WP native striping |
| Hover | BG: `--ersaal-bg-surface-2` |
| Selected | Background: `--ersaal-color-primary-light` |
| Actions column | Right-aligned (or inline-end) |
| Empty state | Use `.ersaal-empty-state` component |
| Pagination | Use WordPress native `pagination-links` with minimal overrides |

### Rules
- ✅ Use `overflow-x: auto` wrapper on mobile
- ✅ Truncate long text with ellipsis; show full in Detail view
- ❌ Do not remove WP Admin table accessibility structure
- ❌ Do not inline all column widths arbitrarily

---

## 12. Tabs

Source: `tabs.module.css` — Pill shape, yellow active state, 0.2s transitions.

```html
<div class="ersaal-tabs-list" role="tablist">
  <button class="ersaal-tab" role="tab" aria-selected="false">Tab Label</button>
  <button class="ersaal-tab ersaal-tab-active" role="tab" aria-selected="true">Active Tab</button>
</div>
```

| State | Style |
|---|---|
| Default | BG: `--ersaal-gray-0`, text: `--ersaal-text-secondary` |
| Hover | `opacity: 0.8` |
| Active | BG: `--ersaal-color-accent (#FFC10E)`, text: white |
| Focus | `outline: 2px solid --ersaal-color-primary` |
| Disabled | `opacity: 0.5; pointer-events: none` |

### Rules
- ✅ Yellow active tab matches Landing Page `tabs.module.css` exactly
- ✅ Pill shape (`border-radius: 9999px`) from Landing Page
- ✅ Same tab nav pattern across Settings, WooCommerce, OTP pages
- ❌ Do not use different tab styles per page
- ❌ Do not create underline-only tabs

---

## 13. Status Badges

Badges are used extensively across Logs and WooCommerce order views.

```html
<span class="ersaal-badge ersaal-badge-success">Delivered</span>
<span class="ersaal-badge ersaal-badge-danger">Failed</span>
<span class="ersaal-badge ersaal-badge-info">Processing</span>
<span class="ersaal-badge ersaal-badge-warning">Retry</span>
<span class="ersaal-badge ersaal-badge-muted">Unknown</span>
```

### Status Mapping

| Status | Badge Class |
|---|---|
| `accepted`, `delivered`, `sent` | `.ersaal-badge-success` |
| `failed`, `error`, `rejected` | `.ersaal-badge-danger` |
| `processing`, `pending` | `.ersaal-badge-info` |
| `retry_scheduled`, `queued` | `.ersaal-badge-warning` |
| `unknown`, `expired` | `.ersaal-badge-muted` |

### Rules
- ✅ Soft background + readable text (no saturated solid fills)
- ✅ `border-radius: --ersaal-radius-sm (4px)` — compact
- ✅ Always include a text label (never icon-only status)
- ❌ Do not rely on color alone to communicate status

---

## 14. Alerts / Notices

```html
<div class="ersaal-alert ersaal-alert-success" role="alert">
  Connected successfully.
</div>
```

### Variants

| Class | Usage |
|---|---|
| `.ersaal-alert-success` | Connected, saved, verified |
| `.ersaal-alert-danger` | API error, invalid credential, delete warning |
| `.ersaal-alert-warning` | Rate limit, subscription warning |
| `.ersaal-alert-info` | Informational notice |

### Rules
- ✅ Left border accent `4px solid` in relevant status color
- ✅ Use `role="alert"` for dynamic notices
- ✅ Can coexist with WordPress native `notice` classes where required
- ❌ Do not use alerts for decorative or non-actionable information

---

## 15. Modals

*(Not yet fully implemented — rules for v1.1)*

- ✅ Overlay: `rgba(0,0,0,0.5)`
- ✅ Container: `border-radius: --ersaal-radius-lg (16px)`, `--ersaal-shadow-md`
- ✅ Close button: top-inline-end corner, accessible
- ✅ Focus trap inside modal
- ✅ `Escape` key closes modal
- ❌ No full-screen modals for simple confirmations

---

## 16. Empty States

```html
<div class="ersaal-empty-state">
  <p class="ersaal-empty-state-title">No SMS logs yet.</p>
  <p class="ersaal-empty-state-text">
    Messages sent through Ersaal will appear here.
  </p>
  <!-- Optional CTA -->
  <a href="..." class="ersaal-btn ersaal-btn-secondary ersaal-btn-sm">Send SMS</a>
</div>
```

### Rules
- ✅ Short, helpful title
- ✅ Brief explanation
- ✅ One optional CTA (not mandatory)
- ❌ No large illustrations inside admin panels
- ❌ Do not show empty state while data is loading

---

## 17. Page Layouts

### Standard Structure

```
┌─────────────────────────────────────────────────┐
│ .ersaal-page-header                             │
│   [Page Title]                   [Action Btns]  │
│   [Optional description]                        │
├─────────────────────────────────────────────────┤
│ [Filters / Tabs if needed]                      │
├─────────────────────────────────────────────────┤
│ [Main Content: Table / Form / Cards]            │
└─────────────────────────────────────────────────┘
```

### Page Width Guidelines

| Page | Max Width |
|---|---|
| Settings | `--ersaal-content-max-width-narrow (640px)` |
| General pages | `--ersaal-content-max-width-normal (960px)` |
| Logs, Dashboard | `--ersaal-content-max-width-wide (1200px)` |

---

## 18. Icons

### Strategy: Dashicons + Selective Inline SVG

| Context | Approach |
|---|---|
| WordPress Admin controls | Dashicons (native) |
| Ersaal-specific feature icons | Inline SVG — simple, single-color |
| Status indicators | Text badge + optional Dashicon |

### Rules
- ✅ Functional icons only — no decorative icon overload
- ✅ SVGs must be 1–2 color, using `currentColor`
- ✅ Icon + text alignment: `display: inline-flex; align-items: center; gap: 4px`
- ❌ Do not use emoji as UI icons
- ❌ Do not mix Dashicons + Font Awesome + custom SVG on the same page

---

## 19. RTL / LTR

### Mandatory CSS Rules

```css
/* ✅ Use — Logical Properties */
margin-inline-start: 16px;
padding-inline-end: 8px;
inset-inline-start: 0;
text-align: start;
border-inline-start: 4px solid;

/* ❌ Avoid in shared components */
margin-left: 16px;
padding-right: 8px;
left: 0;
text-align: left;
border-left: 4px solid;
```

### Exceptions (Acceptable LTR-forced elements)

| Element | Reason |
|---|---|
| Phone numbers | Always LTR: `dir="ltr"` on input |
| Message IDs, Order IDs | LTR: `unicode-bidi: embed; direction: ltr` |
| Code blocks | LTR |
| Timestamps | LTR preferred for readability |

### RTL Testing Checklist
- [ ] Page renders correctly under `<html dir="rtl">`
- [ ] Buttons stack correctly (inline-end alignment)
- [ ] Table columns don't flip incorrectly
- [ ] Alerts border appears on inline-start
- [ ] Tabs align properly

---

## 20. Responsive Rules

### Breakpoints (from Landing Page `theme.tsx`)

| Name | Value | Context |
|---|---|---|
| xs | 416px | Small mobile |
| sm | 768px | Tablet |
| md | 1024px | Laptop |
| lg | 1184px | Desktop |
| xl | 1440px | Wide desktop |

### Admin Priorities (in order)

1. Desktop (primary admin use)
2. Laptop
3. Tablet
4. Mobile (basic compatibility, not primary)

### Rules
- ✅ Cards stack to single column on tablet
- ✅ Tables scroll horizontally on narrow screens
- ✅ Filters wrap gracefully
- ✅ Buttons remain accessible touch targets (min 36px height)
- ❌ No fixed-width elements that overflow on < 768px

---

## 21. Accessibility

### Requirements

| Rule | Detail |
|---|---|
| Color contrast | WCAG AA: 4.5:1 for body text, 3:1 for large text |
| Focus ring | Always visible: `outline: 2px solid var(--ersaal-color-primary); outline-offset: 2px` |
| Labels | Every input has a `<label>`. Never placeholder-only |
| Buttons | `<button>` elements, never `<div onclick>` |
| Status | Never use color alone to indicate status — always include text |
| Modals | Focus trap + `aria-modal="true"` + Escape key |
| Tabs | `role="tablist"`, `role="tab"`, `aria-selected` |
| Accordions | `aria-expanded` on trigger |

### ❌ Prohibited
- `outline: none` without replacement focus indicator
- `<div>` as interactive elements without ARIA
- Mouse-only interactions

---

## 22. Motion & Animation

### Allowed (Admin-safe)

| Effect | Duration | Usage |
|---|---|---|
| Hover state change | 150–200ms | Buttons, rows, tabs |
| Focus ring appear | 150ms | Input focus |
| Accordion open/close | 200ms | FAQ, settings sections |
| Modal appear | 200ms | Overlay + container |
| Loading spinner | Continuous (subtle) | Async operations |

Source: `tabs.module.css` uses `transition: 0.2s ease` — follow this standard.

### ❌ Prohibited
- Floating / bouncing animations
- Parallax effects
- Large entrance animations on page load
- Continuous pulsing or blinking (except loading)
- `animation-duration > 350ms` for UI interactions

---

## 23. CSS Architecture

```
admin/assets/css/
├── ersaal-tokens.css       ← Design tokens (colors, spacing, radius, shadows)
│                              ALWAYS load this first
├── ersaal-components.css   ← Future: Shared component overrides
├── ersaal-layout.css       ← Future: Page layout helpers
├── help.css                ← Help page specific
├── manual-send.css         ← Manual Send page specific
└── [page-name].css         ← Page-specific additions only
```

### Rules
- ✅ Load `ersaal-tokens.css` on every Ersaal admin page
- ✅ Page-specific CSS only adds to tokens; never redefines base values
- ✅ All selectors scoped under `.ersaal-admin` or `#ersaal-[page-id]`
- ❌ Do not add `body { }` or `.wp-admin { }` rules
- ❌ Do not duplicate token values inline — reference variables

---

## 24. Do / Don't Quick Reference

| ✅ Do | ❌ Don't |
|---|---|
| Use `--ersaal-color-primary` for main actions | Use `#1E2670` hardcoded in page CSS |
| Use `.ersaal-btn-danger` for all delete actions | Create a red button with a custom style |
| Use `margin-inline-start` for spacing | Use `margin-left` in shared components |
| Use `.ersaal-badge-*` for all status chips | Use `<span style="color: green">` |
| Keep animations ≤ 250ms | Add marketing-style entrance animations |
| Use `<label>` for every input | Use placeholder as the only label |
| Use `dir="ltr"` on phone/ID inputs | Show Arabic phone numbers RTL |
| Test under `<html dir="rtl">` | Assume LTR layout only |
| Use Dashicons for admin UI icons | Mix multiple icon systems |
| Read this document before new UI work | Skip to code immediately |

---

## 25. UI Migration Plan

The following order is recommended for applying the design system to existing pages.
Do **not** attempt all pages in a single task.

| Priority | Page | Why First |
|---|---|---|
| 1 | **Dashboard** | First impression; stats cards benefit most from token system |
| 2 | **Settings** | Most-used page by admins; narrow layout, cleanest to update |
| 3 | **Send SMS** | High-interaction form; input/button tokens apply directly |
| 4 | **WooCommerce** | Complex settings with tabs; tab component standardization |
| 5 | **Logs** | Table + badge heavy; badge system must be finalized first |
| 6 | **Help** | Already has own CSS; align tokens and typography |
| 7 | **OTP screens** | Future feature; implement with tokens from day one |

> Each migration must be a separate Git commit with dedicated testing before moving to the next page.

---

*Maintained by the wp-ersaal development team.*
*Landing Page Reference: `ersaal-landingpage/` (READ-ONLY — never modify)*
