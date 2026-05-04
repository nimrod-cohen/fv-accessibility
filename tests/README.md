# Test plan

There's no automated test framework yet. The plan below covers:

1. **Manual smoke test** — what to click after every release.
2. **Automated WCAG audit** with `axe-core` against a sample fixture page.
3. **Screen-reader spot check** with NVDA in Hebrew.

---

## 1. Manual smoke test

Activate the plugin, then run through the menu in this order. Each step should be silent (no JS errors, no PHP notices in `debug.log`).

### Floating button
- [ ] Button visible at default position (bottom-right desktop, bottom-right mobile).
- [ ] Click → drawer opens. `Esc` → drawer closes, focus returns to the button.
- [ ] `Ctrl+U` → drawer toggles. (May conflict with browser View-Source — change the shortcut in admin if so.)
- [ ] Tab order inside the drawer reaches every control.

### Settings → נגישות
- [ ] **Position**: change desktop side to "left" + offset_y to 100. Frontend updates after save + page refresh.
- [ ] **Position**: change mobile size to 64. Resize viewport to <768px → button reflects mobile values.
- [ ] **Appearance**: change button color → button repaints after save + refresh.
- [ ] **Features**: untick `text_size` and save. Reload front; that button is gone from the drawer.
- [ ] **Statement**: fill coordinator email. Visit `/accessibility/` → details appear.
- [ ] **Advanced**: tick "exclude pages" for Privacy Policy. Visit that page → button absent.
- [ ] **Compliance**: click "הפעל סריקה". Report shows up. Each page row either says "no issues" or lists rules + WCAG level.

### Content adjustments (after save: just front)
- [ ] **Text size**: click 4 times → cycles through 4 step states + off. Dots fill 1‑by‑1.
- [ ] **Line spacing / word spacing / letter spacing / line height**: 3 steps each.
- [ ] **Readable font / dyslexic font**: check `<body>` font-family changes.
- [ ] **Text alignment**: cycle right → left → center → justify → off.
- [ ] **Page zoom**: 3 steps to 150%. (Note: `zoom` is a non-standard CSS prop; works in Chromium/WebKit, accept-but-ignored in old Firefox.)
- [ ] **Larger targets**: buttons/inputs grow to ≥44×44px.
- [ ] **Highlight headings / links / focus**: visible outlines appear; ours stays unaffected.
- [ ] **Image descriptions**: alt text shows as a caption under each `<img>`. Toggle off → captions removed.
- [ ] **Content magnifier**: hover over a paragraph → it scales up.

### Color/contrast
- [ ] **Contrast cycle**: light (white bg) → dark (black bg) → off. Drawer/button stay readable in both.
- [ ] **Monochrome**: page goes grayscale, drawer stays in color.
- [ ] **Invert colors**: page inverts, images/videos re-invert (still recognizable).
- [ ] **Saturation**: high → low → off.
- [ ] **Custom colors**: pick a green for "background" → page bg becomes green, headings/text untouched.
- [ ] Verify drawer/button **stay anchored** during all of the above. (Bug fixed in v0.4.1: filter on body broke fixed positioning.)

### Media
- [ ] **Pause animations**: open a page with a CSS animation; toggle on → animation stops.
- [ ] **Hide images**: toggle on → all `<img>` invisible (visibility:hidden), our icon stays.
- [ ] **Block flashing**: legacy `<marquee>` content stops scrolling.
- [ ] **Mute media**: open a page with `<video>`; toggle on → video muted; play it — still muted; reload while toggle on — muted on next play.

### Navigation
- [ ] **Cursor cycle**: black (large black arrow with white outline) → white (inverted) → off (system default).
- [ ] **Keyboard nav**: focus ring appears on Tab. Tab to first link → if Skip link wasn't present in template, the auto-injected one shows. Press Enter on it → page scrolls to main.
- [ ] **Reading ruler**: horizontal yellow band follows mouse Y.
- [ ] **Reading mask**: only a strip around the cursor is bright.
- [ ] **Reader mode**: on a content page, page strips down to article-only layout. Toggle off → original layout returns.
- [ ] **Page structure**: opens sub-section. Headings tab lists every h1-h6 with indents. Click one → drawer closes, page scrolls + focuses.
- [ ] **Page outline**: same but headings only.

### Profiles
For each of the 5 profiles:
- [ ] First click activates it; chip turns blue.
- [ ] Manually toggle one of its underlying features (e.g., text size for "low vision") → chip un-actives (no longer fully matching).
- [ ] Click again with manual changes still applied → all profile keys re-set.

### Reset
- [ ] After many features active → "איפוס" → page returns to default. State storage cleared.

### Persistence
- [ ] Activate text size = 2, navigate to another page → text still bigger (no flash).
- [ ] Open in incognito → text default (state per-browser).

---

## 2. Automated WCAG audit (axe-core)

`axe-core` is the de-facto WCAG audit engine. We don't bundle it; install it in your dev env and run against pages with the menu active.

### Install

```bash
npm install --save-dev @axe-core/cli
```

### Run

Against the local dev site:

```bash
npx axe http://value.dev.local --tags wcag2a,wcag2aa --save axe-report.json
```

Against a single page:

```bash
npx axe http://value.dev.local/sample-page/ --tags wcag2aa
```

### Expected output

With the menu present and **inactive** (visitor hasn't toggled anything), the only menu-related findings should be:

- `aria-modal-non-modal` violations: **none** — we explicitly set `aria-modal="false"`.
- `aria-allowed-attr`: **none** — `aria-pressed` on button, `aria-controls` on trigger, `aria-live` on announce region are all valid.
- `region`: the floating button is in `wp_footer`; outside any landmark. Some axe configurations report this as a non-best-practice — this is acceptable for a floating UI affordance.

With the menu **active** (e.g., contrast=light, large targets on), axe should report **fewer** violations than baseline (because we're enforcing focus rings, target sizes, etc.).

The compliance scanner inside the plugin admin is a *subset* of axe-core's checks; it covers the high-impact static-HTML rules but not computed-style ones (contrast 1.4.3). For a complete audit, axe-core remains the source of truth.

---

## 3. Screen-reader spot check (NVDA, Hebrew)

NVDA is free; Hebrew speech via the eSpeak NG engine. Test against `value.dev.local`:

- [ ] Tab to floating button → NVDA announces "תפריט נגישות, כפתור".
- [ ] Activate → "תפריט נגישות" (the panel title becomes focused; tabindex=-1 + RAF focus()).
- [ ] Tab through controls → each button is announced with its label and `aria-pressed` state.
- [ ] Click "גודל טקסט" → live region announces "גודל טקסט — שלב 1 מתוך 4" (and 2/4, 3/4… on subsequent clicks).
- [ ] Click "ניגודיות" → "ניגודיות — bright" / "dark" / "off".
- [ ] Click "איפוס" → "כל ההתאמות אופסו".
- [ ] Esc → focus returns to the trigger; NVDA announces "תפריט נגישות, כפתור" again.

For VoiceOver (macOS) the same commands apply (`Ctrl+Option+arrows`); behavior should be identical.

---

## Performance budget

Frontend assets:

| File | Unminified | Notes |
|---|---|---|
| `fv-a11y-features.css` | ~6 KB | Static |
| `fv-a11y.css` | ~7 KB | Static |
| `fv-a11y.js` | ~14 KB | Static |
| Inline head bootstrap | ~1 KB | Per-page |
| Per-page inline `<style>` | ~0.5 KB | Position config |

Total over the wire (gzipped, served by nginx): ~12-14 KB. Well under the 60 KB target.

The menu JS is **not** lazy-loaded — it's needed at page load for the bootstrap class application. Lazy-loading the *interaction* layer (drawer click handlers, structure scanning) is on the post‑1.0 roadmap.
