# Changelog

All notable changes will be documented in this file.

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
