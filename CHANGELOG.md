# Changelog

All notable changes will be documented in this file.

## [0.4.0] - 2026-05-03 — Module 4: color/contrast (21–26) + media/motion (27–30)

### Added
- **Color/contrast features (6)**:
  - `contrast_light` — white background, black text, blue links (`#0000ee`), visited purple, no shadows.
  - `contrast_dark` — black background, white text, yellow links + headings.
  - `monochrome` — `filter: grayscale(100%)` on body.
  - `invert_colors` — body inverted; images / videos / iframes re-inverted to look natural.
  - `saturation` — cycle: high (`saturate(2)`) → low (`saturate(0.4)`) → off.
  - **`color_picker`** — three inline color inputs (background / text / headings). Uses CSS custom properties (`--fv-custom-bg`, `--fv-custom-fg`, `--fv-custom-heading`) so unset channels don't trample the page's own colors. Live-updates on `input` event for drag-preview. "Clear colors" button resets just the customs.
- **Media/motion features (4)**:
  - `pause_animations` — kills all CSS animations + transitions globally; JS pauses currently-playing `<video>` elements.
  - `hide_images` — hides `<img>`, `<picture>`, `<svg>` (excluding our icon), `<video>` via `visibility: hidden`.
  - `block_flashing` — kills repeating animations + clamps duration to 1s, neutralizes legacy `<marquee>`/`<blink>` per WCAG 2.3.1.
  - `mute_media` — JS mutes all `<audio>`/`<video>`; `MutationObserver` mutes dynamically-added media too. Disconnects observer on toggle-off.

### Changed
- Inline `<head>` bootstrap script extended to apply the new state keys before paint, including custom-color CSS variables (`--fv-custom-*`).
- `applyState` regex updated to strip the new `fv-*` class families on state changes.
- New picker control type with its own grid-spanning layout in the drawer.

## [0.3.0] - 2026-05-03 — Module 3: content adjustments (features 6–20)

### Added
- 15 content-adjustment features wired end-to-end:
  - **Stepped (cycle through 1..N then off)**: text size (4 steps to +200%), line spacing (3), word spacing (3), letter spacing (3), line height / paragraph rhythm (3), page zoom (3 to 150%).
  - **Toggles**: readable font (Heebo/Assistant/Open Sans Hebrew), dyslexic-friendly font (OpenDyslexic with Comic Sans MS / Verdana fallback — bundled `.woff2` to drop in `/assets/fonts/`), larger click targets (≥44×44px), highlight headings, highlight links, highlight focus, image descriptions (alt-text caption injected as a `<span>` after each img), content magnifier (CSS hover scale on text blocks).
  - **Cycle**: text alignment (right → left → center → justify → off).
- Each control is a button in the drawer panel with `aria-pressed` + state badge (step/checkmark/short label) and an `aria-live="polite"` announce region for state-change announcements ("גודל טקסט — שלב 2 מתוך 4").
- **Reset action** (feature 40): clears all adjustments, wipes localStorage + cookie, refreshes button states, announces.
- **Persistence**: `fv_a11y_state` JSON in `localStorage` with cookie fallback (1-year, `SameSite=Lax`). Choices survive navigation and visits.
- **Inline `<head>` bootstrap** (≈1KB): reads cookie/localStorage and adds the `fv-*` classes to `<html>` *before paint* — prevents the brief flash of un-adjusted content on every navigation.
- **Defensive isolation**: feature CSS uses `body :not(.fv-a11y-button):not(.fv-a11y-panel)*…` selectors; `fv-a11y.css` re-declares typography on our own UI with `!important`, so even with every feature active the menu/button stay readable.

### Notes
- Profiles (1–5), color/contrast (21–26), media/motion (27–30), navigation/cursor (31–37), and cognitive aids (38–39) still ship per the agreed cadence (modules 4–6).
- OpenDyslexic font files are not yet bundled (binary blobs need to be added manually to `/assets/fonts/OpenDyslexic-Regular.woff2`); the CSS uses `local('OpenDyslexic')` first so users with the font installed system-wide already get it. A future release will bundle the font.

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
