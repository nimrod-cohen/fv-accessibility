=== FV Accessibility ===
Contributors: nimrod-cohen
Tags: accessibility, a11y, israel, wcag, hebrew, rtl, is-5568
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
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

= 0.4.0 =
* 6 color/contrast features: light high-contrast, dark high-contrast, monochrome, invert colors, saturation cycle (high/low/off), and a 3-input custom-color picker (background / text / headings).
* 4 media/motion features: pause animations (CSS + video), hide images, block flashing (>3Hz, WCAG 2.3.1), mute media (audio + video, including dynamically-added via MutationObserver).
* Inline head bootstrap extended to apply the new state classes + custom-color CSS variables before paint.

= 0.3.0 =
* 15 content-adjustment features (text size, spacing × 3, line height, readable / dyslexic font, text alignment, page zoom, larger targets, highlights × 3, image descriptions, content magnifier).
* Reset action.
* Persistent state via localStorage + 1-year cookie fallback.
* Inline <head> bootstrap eliminates flash-of-unadjusted-content on navigation.
* aria-live announcements on state change; aria-pressed on toggles.
* Defensive CSS isolation keeps the menu/button readable regardless of active adjustments.

= 0.2.0 =
* Features registry (39 adjustments + Reset) — toggle on/off per site from the admin Features tab; behavior lands in modules 3–6.
* Statement shortcode `[fv_accessibility_statement]` with editable Hebrew template; coordinator details update live.
* Feedback channel: shortcode `[fv_accessibility_feedback]` + inline form inside the drawer; AJAX-submits and emails the coordinator (legally required).
* Drawer "main" / "feedback" sections with focus management on switch.
* Footer accessibility-statement link, configurable via Advanced → Show footer icon.
* Admin: Features / Statement / Advanced tabs fully implemented. Excluded-pages multi-select. Custom-CSS field. Cleanup-on-uninstall opt-in.
* Uninstall script honors the cleanup-on-uninstall toggle. Statement page is preserved (user content).

= 0.1.0 =
* Initial scaffold
* Floating button with full position control (desktop + mobile): side, anchor, offset X/Y, size
* Empty drawer panel skeleton (features land in v0.3+)
* Configurable keyboard shortcut (default: Ctrl+U)
* ARIA + focus management + prefers-reduced-motion
* Settings API (single serialized option, deep-merge defaults)
* Auto-creates `/accessibility/` page on activation
* GitHub-based auto-updater
