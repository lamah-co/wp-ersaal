# Ersaal Admin UI/UX Audit

Audit date: 2026-08-09
Scope: Dashboard, Settings, Send SMS, WooCommerce, Logs, Help, and available OTP UI.

## Cross-screen findings

- The page shell is only partially shared. Headers exist, but widths, section rhythm, actions, and content surfaces are handled with page-level inline styles.
- The templates contain 168 inline style attributes. This makes responsive behavior, RTL behavior, focus states, and future maintenance inconsistent.
- Four referenced CSS tokens are undefined (`--ersaal-bg-body`, `--ersaal-color-info`, `--ersaal-color-info-bg`, and `--ersaal-shadow-lg`).
- Visual hierarchy relies too heavily on bordered cards. Section titles, supporting copy, and primary actions do not consistently indicate the next step.
- Dashicons, text glyphs, and page-specific icon treatments are mixed. A single inline SVG system is needed.
- Empty states and alerts are implemented differently across pages. Some provide no clear recovery action.
- Technical values are not consistently isolated as LTR content.
- The page header is explicitly reversed in RTL even though document direction already handles inline flow, producing an incorrect action/title order.
- Keyboard focus is defined for shared buttons and fields, but custom modal, accordion, copy, and destructive controls do not consistently expose state or restore focus.
- Responsive handling is concentrated at the 782px WordPress breakpoint; the requested 1024px tablet behavior is largely absent.

## Dashboard

- No account or connection summary, so the user cannot confirm project readiness from the overview.
- Metrics are visually equal and lack supporting context; the next action is not connected to current status.
- The recent activity table shows message content as a dominant column but omits source/event context.
- The Send SMS CTA points to a non-existent page slug (`ersaal-manual-send`).
- Layout and column widths are inline and provide no deliberate 4 → 2 → 1 responsive progression.

## Send SMS

- A large connection card precedes the core task and contains wallet/subscription detail that is difficult to scan.
- The form is a second card, creating unnecessary separation between readiness and action.
- Payment type is always exposed instead of being treated as an advanced option.
- Character, encoding, and part estimates appear as an undifferentiated strip.
- Success output is assembled as an unstructured HTML alert with no direct route to Logs.
- Mixed Arabic/English messages, Dashicons, and inline styles weaken consistency and RTL quality.

## Settings

- The 640px page width is too narrow for comfortable label/description/control relationships.
- General settings combine save and connection testing without clear section headers or a persistent save area.
- API test feedback is plain colored text rather than the shared alert/status system.
- The uninstall checkbox is visually weak despite representing a destructive preference.
- System status uses fixed inline widths and presentation-table styling without a compact responsive fallback.
- Tab semantics are incomplete because links use `role="tab"` without an associated tab panel relationship.

## WooCommerce

- All event editors are expanded, producing a very long page and high information density.
- Repeated bordered boxes and nested preview surfaces make every event look equally important.
- Variable tokens are styled as full secondary buttons rather than compact chips.
- Boolean settings use small native checkboxes with weak enabled/disabled state communication.
- Customer, admin, and additional order status notifications are not separated strongly enough.
- Save is a normal button at the end of a long form and is easy to lose.
- Preview statistics use hard-coded colors and are generated with inline styles.

## Logs

- Five summary cards consume too much horizontal space before the operational controls.
- Filters are split across multiple forms, making combined search/filter state difficult to understand and preserve.
- The primary header action is Help even though Export CSV is the page-specific action.
- Eleven data columns plus selection/actions overload the default table view.
- The table lacks a dedicated horizontal scroll container and deliberate 1024/768 behavior.
- Empty state uses arbitrary colors, a large Dashicon, and no action.
- Details modal uses an undefined shadow token, backdrop blur, no dialog semantics, no Escape handling, and no focus restoration.
- Copy/delete controls depend on Dashicons and titles rather than consistent accessible labels.

## Help

- The header has no useful description and the language control has no visible label.
- Most layout lives inline in the PHP template, while a second embedded style block overrides the page stylesheet with `!important`.
- Sidebar/content both use bordered card treatment, causing nested-box heaviness.
- Mobile navigation becomes a fixed-height scrolling box rather than a clear collapsible index.
- Accordion alignment uses physical left/right properties and a text plus/minus glyph.
- Search results update visually but are not announced to assistive technology.

## OTP

- No dedicated OTP admin or OTP test screen exists in the current codebase.
- OTP appears only as subscription metadata. No interface can be redesigned without inventing new product scope or behavior.

## Redesign priorities

1. Establish one shared page shell, section rhythm, width strategy, icon system, alerts, toggles, chips, toolbars, tables, modals, and empty states.
2. Make readiness and next action explicit on Dashboard and Send SMS.
3. Reduce settings and WooCommerce length with meaningful grouping and progressive disclosure.
4. Make Logs operational: compact summary, one coherent filter toolbar, fewer default columns, responsive scroll, and accessible details.
5. Move all confirmed presentational rules out of templates and use logical CSS properties for RTL/LTR parity.
