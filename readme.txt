=== FV Accessibility ===
Contributors: nimrod-cohen
Tags: accessibility, a11y, israel, wcag, hebrew, rtl, is-5568
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

WordPress accessibility plugin compliant with Israeli Standard IS 5568 (WCAG 2.1 AA) and Regulation 35 of the Equal Rights for Persons with Disabilities (Service Accessibility Adjustments) Regulations.

== Description ==

Hebrew-first WordPress accessibility plugin. Performs **real DOM modifications** — not a cosmetic overlay.

Israeli rulings (Tel Aviv District Court, 2022 onward) make clear that overlay-only solutions do not satisfy IS 5568. The underlying site must also be accessible. This plugin is one piece of compliance, not a complete solution: pair it with semantic HTML, alt text, proper headings, and keyboard-operable templates.

**Core requirements covered**

* Floating accessibility button with separate desktop / mobile position controls
* Real DOM modifications (CSS class on <html> + targeted style injection)
* Settings persisted in `localStorage` with cookie fallback (1-year expiry)
* Hebrew-first UI, English fallback, full RTL support
* No external API calls, no third-party scripts, no AI overlays
* Auto-created accessibility statement page with editable coordinator details
* Built-in feedback channel emailing the configured accessibility coordinator (legally required by Regulation 35)

== Roadmap ==

This 0.1.0 release ships the scaffold (button + drawer + position controls + Settings API). Subsequent modules add the 40 features listed in the spec.

== Changelog ==

= 0.1.0 =
* Initial scaffold
* Floating button with full position control (desktop + mobile): side, anchor, offset X/Y, size
* Empty drawer panel skeleton (features land in v0.3+)
* Configurable keyboard shortcut (default: Ctrl+U)
* ARIA + focus management + prefers-reduced-motion
* Settings API (single serialized option, deep-merge defaults)
* Auto-creates `/accessibility/` page on activation
* GitHub-based auto-updater
