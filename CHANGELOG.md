# Changelog

All notable changes will be documented in this file.

## [1.0.1] - 2026-05-04 — Position picker UI + bug fixes

### Added
- **Visual position picker** for the floating button: device toggle (monitor / smartphone icons, Elementor-style) at the top, then a **3×3 anchor grid** representing the viewport — click a corner / edge / center cell to anchor the button there. Numeric offset X/Y/size inputs sit beside the picker. Keyboard-accessible (arrow keys, RTL-aware).
- **`center` horizontal anchor** is now a valid value (was previously only right/left). When set, the button is positioned via `left: calc(50% + Xpx); transform: translateX(-50%)`. New monitor + smartphone icons added to the icon registry.

### Fixed
- **Hover-slide bug**: hovering the floating button when anchored to middle (or center) made it jump out of position because the `:hover { transform: scale(1.05) }` rule was overriding the positional `translateY(-50%)` / `translateX(-50%)`. Fixed by emitting the positional transform as a CSS variable (`--fv-a11y-pos-transform`) and composing both transforms in the hover rule (`transform: var(--fv-a11y-pos-transform) scale(1.05)`).
- **Tab-aware save**: saving any single tab in Settings → נגישות was unconditionally rewriting `settings[features]`, `advanced[exclude_pages]`, and `advanced[cleanup_on_uninstall]` from `$_POST` — wiping those values whenever the user wasn't on the Features / Advanced tab. The save handler now reads a hidden `active_tab` field and only updates fields belonging to the submitted tab.
- The reduced-motion hover override no longer wipes the positional transform either (uses the same CSS-variable composition).

## [1.0.0] - 2026-05-04 — v1.0: compliance scanner, READMEs, registry cleanup

### Added
- **Compliance scanner** (`includes/class-compliance.php`, Settings → Compliance tab): crawls home + 5 most recent posts/pages, runs HTML-level WCAG checks against the high-impact rules: 1.1.1 (image-alt), 1.3.1 (h1-missing, heading-order, form-label), 2.4.1 (skip-link), 2.4.4 (link-name), 3.1.1 (html-has-lang). AJAX endpoint `wp_ajax_fv_a11y_scan`. Report renders in-place with severity per rule. Acknowledges that 1.4.3 contrast can't be done server-side and recommends axe DevTools.
- **`README.md`** (English) — feature matrix, install + configure, architecture notes, dev structure, license.
- **`README.he.md`** (Hebrew) — same in Hebrew.
- **`tests/README.md`** — manual smoke checklist, axe-core invocation example, NVDA Hebrew spot check, performance budget.

### Changed
- **Features registry cleanup**: removed `cursor_black`/`cursor_white`/`contrast_light`/`contrast_dark` (combined into single `cursor` and `contrast` cycle entries earlier; admin Features tab was still showing the old four). Removed `dictionary` (38) and `virtual_keyboard` (39) — neither is required by IS 5568 / Reg 35; their presence was lifted from generic vendor menus and they're not part of the plugin's value proposition. Cognitive category removed from `Features::categories()`.
- **Plugin status**: 1.0.0 stable. All legally-required components covered, all 36 menu features wired end-to-end, scanner present, docs in both languages.

### Known limitations
- **OpenDyslexic font binary** still needs to be dropped manually into `assets/fonts/OpenDyslexic-Regular.woff2` (binary file, not text). The CSS uses `local('OpenDyslexic')` first so users with the system font installed get it.
- **Frontend JS not lazy-loaded** — currently loads on every page (~14 KB un-minified). Total wire-cost ≈12-14 KB gzipped, well under the 60 KB target. Lazy-loading the interaction layer is post-1.0.
- **Compliance scanner** is intentionally narrower than axe-core — covers static-HTML rules only, not computed-style. Recommended supplementary: axe DevTools browser extension.

## [0.5.0] - 2026-05-03 — Module 5: navigation, cursor, profiles (31–37 + 1–5)

### Added
- **Cursors (31, 32)**: big black cursor and big white cursor — bundled SVG arrows applied via `cursor: url(...) 3 3, default !important`. Hot spot at the arrow tip. Mutual exclusivity in JS: enabling one disables the other.
- **Keyboard navigation (33)**: visible global focus ring (`outline: 3px solid #f59e0b`), auto-injected `דלג לתוכן` skip link (visible only on focus), Alt+1..9 jumps to the nth heading + focuses + smooth-scrolls.
- **Reading ruler (34)**: horizontal highlight band follows the cursor. Mousemove → `requestAnimationFrame` → `--fv-ruler-y` CSS variable. Single passive listener.
- **Reading mask (35)**: dims everything except a ~160px strip around cursor. Two fixed overlays (top + bottom) sized via `--fv-mask-y` CSS variable.
- **Reader mode (36)**: detects main content (`<main>`, `[role="main"]`, `<article>`, `.entry-content`, `#content`, `#primary`, etc.), clones its HTML into `.fv-a11y-reader-mode-content`, hides every other body child via CSS. Original DOM untouched — toggle off restores everything.
- **Page structure (36)**: drawer sub-section with three tabs — headings (h1–h6 with indent by level), ARIA landmarks, and links (capped at 200). Each item is a button that scrolls the target into view, focuses it, flashes an outline, and closes the panel.
- **Page outline (37)**: lighter sub-section with just the heading list.
- **Profiles (1–5)**: 5 one-click presets (blind, low_vision, color_blind, cognitive, motor) that bundle multiple state keys. Click → activate; click again → deactivate. Profile shows "active" only while every constituent key still matches its definition (so manually toggling a feature un-activates the profile visually).

### Notes
- Profile chips render as full-width pill buttons in the drawer (visually distinct from the 2-column feature buttons).
- New `action` control type opens a sub-section instead of toggling state.
- Inline `<head>` bootstrap extended with all the new state classes so navigation features apply before paint.
- The version constant fix in v0.4.2 left the plugin header at 0.4.1; this release bumps both to 0.5.0.

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
