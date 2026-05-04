# FV Accessibility

WordPress accessibility plugin compliant with **Israeli Standard IS 5568** (WCAG 2.1 AA) and **Regulation 35** of the Equal Rights for Persons with Disabilities (Service Accessibility Adjustments) Regulations, 5773‑2013.

> **Honest disclaimer.** Israeli rulings (Tel Aviv District Court, 2022 onward) make clear that an accessibility menu — overlay or otherwise — does **not** by itself satisfy IS 5568. The underlying site must also be accessible: semantic HTML, alt text, keyboard-operable templates, sufficient color contrast, etc. This plugin performs *real DOM modifications* (CSS class on `<html>` + targeted style + JS), it is one piece of a compliance program, not the program itself.

## Features

A 36-feature menu rendered behind a configurable floating button, plus 5 one-click profiles, plus the legally-required statement page and feedback channel.

| Category | Features |
|---|---|
| **Profiles** | Vision-impaired, low vision, color-blind, cognitive, motor |
| **Content** | Text size (4 steps to +200%), line/word/letter spacing (3 steps), line height, readable font, dyslexic font, text alignment cycle, page zoom (3 steps to 150%), larger click targets, highlight headings, highlight links, highlight focus, image descriptions (alt as caption), content magnifier on hover |
| **Color & contrast** | Contrast cycle (light / dark), monochrome, invert colors, saturation cycle (high / low), custom color picker (background / text / headings) |
| **Media & motion** | Pause animations, hide images, block flashing (WCAG 2.3.1), mute media |
| **Navigation & cursor** | Big cursor cycle (black / white), keyboard navigation (focus ring + skip link + Alt+1..9 jumps to nth heading), reading ruler, reading mask, reader mode, page structure, page outline |
| **Reset** | Clear all adjustments |

Plus: accessibility statement page (auto-created on activation, populated via shortcode), in-menu feedback form (AJAX-emails the configured coordinator), built-in compliance scanner, GitHub-based auto-updater.

## Installation

1. Drop `fv-accessibility/` into `wp-content/plugins/`.
2. Activate it from the WordPress Plugins screen. The plugin auto-creates a `/accessibility/` page.
3. Open **Settings → נגישות** and fill in the **Statement** tab with your accessibility coordinator's name, email, phone, and role. These details are required by Regulation 35 and are surfaced on the public statement page and as the recipient of feedback emails.

## Configuration

All under **Settings → נגישות**, six tabs:

- **Position** — separate desktop / mobile button placement (side, anchor, offset X/Y, size), keyboard shortcut.
- **Appearance** — button background + icon colors.
- **Features** — toggle each menu item on/off per site so you can curate which adjustments appear.
- **Statement** — coordinator details, business name, exemption text. Edits reflect immediately on the statement page (no page-content re-save needed; the page contains a `[fv_accessibility_statement]` shortcode).
- **Advanced** — excluded pages (won't render the button), custom CSS, cleanup-on-uninstall opt-in.
- **Compliance** — built-in scanner that crawls the homepage and 5 most-recent posts/pages and checks for: missing `alt`, missing `<html lang>`, missing `h1`, heading-level skips, form fields without labels, missing skip link, generic/empty links. Surface-level audit; for full WCAG coverage including computed-style contrast checks, install the **axe DevTools** browser extension.

## Architecture notes

- **No external API calls. Ever.** No tracking, no AI overlays, no third-party scripts, no CDN dependencies.
- **Settings**: single serialized option `fv_accessibility_settings`, deep-merged against defaults so older installs auto-pick up new keys.
- **Persistence**: visitor preferences stored in `localStorage`, cookie fallback (`fv_a11y_state`, 1-year, `SameSite=Lax`). Choices survive navigation and visits.
- **No flash of unadjusted content**: a ~1KB inline `<head>` script reads cookie/localStorage and applies `fv-*` classes to `<html>` *before* paint.
- **Defensive isolation**: feature CSS uses `body :not(.fv-a11y-button) :not(.fv-a11y-panel) *` selectors so adjustments never bleed into the menu/button itself.
- **Containing-block pitfall**: any CSS `filter` on `<body>` would turn body into the containing block for `position: fixed` descendants — yanking the menu off the viewport. Filter rules (monochrome, invert, saturation) target `body > *:not(.fv-a11y-button):not(.fv-a11y-panel)` instead.
- **Drawer is non-modal**: `aria-modal="false"` so screen readers can still navigate the page underneath. Esc closes; focus returns to the trigger.
- **Reduced motion**: every transition and the hover scale-up are zeroed under `@media (prefers-reduced-motion: reduce)`.

## Development

```
wp-content/plugins/fv-accessibility/
├── fv-accessibility.php        Bootstrap (single source of truth for the version)
├── uninstall.php               Honors the cleanup-on-uninstall opt-in
├── includes/
│   ├── class-settings.php      Single serialized option + deep-merge defaults
│   ├── class-features.php      36-feature registry
│   ├── class-icons.php         Lucide SVG icon registry (inline)
│   ├── class-fv-accessibility.php
│   ├── class-admin.php         Settings → נגישות page
│   ├── class-statement.php     Shortcode for the statement page
│   ├── class-feedback.php      AJAX feedback form → wp_mail
│   ├── class-compliance.php    Built-in WCAG scanner
│   └── github-updater.php      WP-native auto-update from GitHub releases
├── assets/
│   ├── css/fv-a11y.css                  Button + drawer chrome
│   ├── css/fv-a11y-features.css         html.fv-* feature class rules
│   ├── css/fv-a11y-admin.css            Settings page styles
│   ├── js/fv-a11y.js                    Frontend runtime (state + DOM ops)
│   ├── img/cursor-black.svg             Big-cursor variant
│   ├── img/cursor-white.svg             Big-cursor variant
│   └── fonts/                           OpenDyslexic .woff2 (drop manually)
├── templates/statement-he.php           Hebrew statement template
└── tests/README.md                      Test plan
```

PHP 7.4+, WordPress 6.0+. No build step, no dependencies, no jQuery.

## License

GPL-2.0-or-later. Lucide icons are under MIT (lucide.dev). OpenDyslexic font (when dropped in) is licensed under SIL OFL.

## Roadmap (post-1.0)

- OpenDyslexic font binary bundled in `assets/fonts/`.
- Lazy-loading the menu JS until first interaction (currently loads on page load; ~14KB un-minified, well under the 60KB-gzipped budget).
- Collapsible category headers in the drawer for sites that enable many features.
