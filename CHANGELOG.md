# Changelog

All notable changes will be documented in this file.

## [0.2.0] - 2026-05-03 — Module 2: admin completion + statement + feedback + footer

### Added
- **Features registry** (`Features::all()`): all 39 toggleable adjustments + the Reset action, grouped into 6 categories (profiles / content / color / media / navigation / cognitive). Feature behavior lands in modules 3–6; this module wires the on/off toggles so admins can curate which items the menu will offer.
- **Statement shortcode** `[fv_accessibility_statement]` and Hebrew template (`templates/statement-he.php`). Coordinator details edited in Settings reflect immediately on the statement page — no page-content re-save needed.
- **Feedback channel** (`Feedback` class): shortcode `[fv_accessibility_feedback]` plus inline form inside the drawer panel. Submits to `wp_ajax_fv_a11y_feedback`, validates and emails the configured coordinator (falls back to `admin_email` if unset). Required by Regulation 35.
- **Drawer sectioning**: drawer body now switches between "main" and "feedback" sections via `data-target` attributes; focus is moved to the destination section's heading on switch.
- **Footer accessibility link**: small inline link auto-injected at end of `<body>` when `Settings → Advanced → Show footer icon` is on. Links to the statement page.
- **Admin tabs filled out**: Features, Statement, Advanced. Save handler validates and stores all fields.
- **Custom CSS** field (Settings → Advanced) — appended to the inline style block on every page render.
- **Excluded pages**: multi-checkbox list of all pages; the floating button/panel/footer link skip rendering on those.
- **Uninstall script**: deletes `fv_accessibility_settings` option only when admin opts in via Advanced → Cleanup on uninstall. The auto-created `/accessibility/` page is preserved (user content).

### Changed
- Activation now seeds the `/accessibility/` page with the `[fv_accessibility_statement]` shortcode (was a placeholder paragraph).
- `inline_styles()` now also outputs the saved Custom CSS in the same `<style>` block, after the position rules.

## [0.1.0] - 2026-05-03 — Module 1: scaffold + floating button

### Added
- Plugin scaffold (`fv-accessibility.php` bootstrap, `\FVAccessibility\` namespace).
- Settings API: single serialized option `fv_accessibility_settings`, deep-merge against defaults so new keys don't break older installs.
- Floating accessibility button with separate desktop / mobile position controls — side (right/left), vertical anchor (top/middle/bottom), offset X, offset Y, size.
- Drawer panel skeleton: header with localized title, close button, body placeholder, footer link to the statement page.
- Configurable keyboard shortcut (default `ctrl+u`); Esc closes the panel.
- ARIA: `role="button"`, `aria-label`, `aria-expanded`, `aria-controls`, `aria-modal`, `aria-hidden`, focus restored to trigger on close.
- Honors `prefers-reduced-motion` (no transitions, no hover scaling).
- Admin page under Settings → נגישות, with all 6 tabs visible. Position + Appearance tabs functional; Features / Statement / Advanced / Compliance show placeholder until their modules ship.
- Activation hook auto-creates `/accessibility/` page if it doesn't exist.
- GitHub-based auto-updater (uses the `nimrod-cohen/fv-accessibility` releases endpoint).
