/* FV Accessibility — frontend trigger + drawer + content adjustments.
 *
 * State shape lives in localStorage as `fv_a11y_state`, with cookie fallback
 * (1-year expiry) so preferences survive across visits and respect WP login
 * sessions. The inline <head> bootstrap script applies state classes to
 * <html> before paint; this file is responsible for handling user
 * interactions, persistence, and DOM-level features (image captions).
 *
 * Vanilla JS, no jQuery. Event delegation throughout to keep the listener
 * count low.
 */
(function () {
  'use strict';
  if (window.fvA11yLoaded) return;
  window.fvA11yLoaded = true;

  var STATE_KEY = 'fv_a11y_state';
  var COOKIE_MAX_AGE = 365 * 24 * 60 * 60; // 1 year

  // Map between DOM data-feature ids (snake_case) and state keys (camelCase).
  // Keeping the storage shape camelCase matches typical JS convention while
  // the HTML data-* attribute remains snake_case for readability.
  function toCamel(snake) {
    return snake.replace(/_(.)/g, function (_, c) { return c.toUpperCase(); });
  }

  // Class-name producers per state field. The order here doesn't matter for
  // rendering, but keep it in sync with the inline <head> bootstrap script
  // so what we write is what the head script reads on the next page load.
  function classesFromState(state) {
    var c = [];
    if (state.textSize)          c.push('fv-text-size-' + state.textSize);
    if (state.lineSpacing)       c.push('fv-line-spacing-' + state.lineSpacing);
    if (state.wordSpacing)       c.push('fv-word-spacing-' + state.wordSpacing);
    if (state.letterSpacing)     c.push('fv-letter-spacing-' + state.letterSpacing);
    if (state.lineHeight)        c.push('fv-line-height-' + state.lineHeight);
    if (state.pageZoom)          c.push('fv-page-zoom-' + state.pageZoom);
    if (state.textAlign)         c.push('fv-text-align-' + state.textAlign);
    if (state.readableFont)      c.push('fv-readable-font');
    if (state.dyslexicFont)      c.push('fv-dyslexic-font');
    if (state.largerTargets)     c.push('fv-larger-targets');
    if (state.highlightHeadings) c.push('fv-highlight-headings');
    if (state.highlightLinks)    c.push('fv-highlight-links');
    if (state.highlightFocus)    c.push('fv-highlight-focus');
    if (state.imageDescriptions) c.push('fv-image-descriptions');
    if (state.contentMagnifier)  c.push('fv-content-magnifier');
    if (state.contrastLight)     c.push('fv-contrast-light');
    if (state.contrastDark)      c.push('fv-contrast-dark');
    if (state.monochrome)        c.push('fv-monochrome');
    if (state.invertColors)      c.push('fv-invert-colors');
    if (state.saturation)        c.push('fv-saturation-' + state.saturation);
    if (state.pauseAnimations)   c.push('fv-pause-animations');
    if (state.hideImages)        c.push('fv-hide-images');
    if (state.blockFlashing)     c.push('fv-block-flashing');
    if (state.muteMedia)         c.push('fv-mute-media');
    if (state.customBg || state.customFg || state.customHeading) c.push('fv-custom-colors');
    if (state.cursorBlack)   c.push('fv-cursor-black');
    if (state.cursorWhite)   c.push('fv-cursor-white');
    if (state.keyboardNav)   c.push('fv-keyboard-nav');
    if (state.readingRuler)  c.push('fv-reading-ruler');
    if (state.readingMask)   c.push('fv-reading-mask');
    if (state.readerMode)    c.push('fv-reader-mode');
    return c;
  }

  /**
   * Profile definitions. Each profile is a meta-toggle: clicking activates
   * a bundle of feature state. State coherence is checked by re-comparing
   * each key — a profile is "active" only when *all* of its keys still
   * match its definition (so manually flipping one feature un-activates
   * the profile visually without un-doing what the user did).
   */
  var PROFILES = {
    profile_blind:       { keyboardNav: true, highlightLinks: true, highlightHeadings: true, highlightFocus: true, readableFont: true },
    profile_low_vision:  { textSize: 2, contrastLight: true, readableFont: true, cursorWhite: true, largerTargets: true, highlightLinks: true },
    profile_color_blind: { monochrome: true, highlightLinks: true },
    profile_cognitive:   { readableFont: true, readingRuler: true, pauseAnimations: true, lineSpacing: 2, dyslexicFont: true },
    profile_motor:       { keyboardNav: true, largerTargets: true, highlightFocus: true, cursorBlack: true }
  };

  function profileMatches(profileId, state) {
    var def = PROFILES[profileId]; if (!def) return false;
    for (var k in def) if (def.hasOwnProperty(k)) {
      if ((state[k] || (typeof def[k] === 'boolean' ? false : 0)) !== def[k]) return false;
    }
    return true;
  }

  function applyProfile(profileId, state) {
    var def = PROFILES[profileId]; if (!def) return state;
    var active = profileMatches(profileId, state);
    var keys = Object.keys(def);
    if (active) {
      // Deactivating: clear only the keys the profile sets.
      for (var i = 0; i < keys.length; i++) {
        var k = keys[i];
        state[k] = (typeof def[k] === 'boolean') ? false : (typeof def[k] === 'number' ? 0 : '');
      }
    } else {
      // Activating: copy values over.
      for (var j = 0; j < keys.length; j++) state[keys[j]] = def[keys[j]];
    }
    return state;
  }

  /**
   * Apply / remove custom-color CSS variables on documentElement. Empty
   * values are removed so unset channels fall back to "inherit / transparent"
   * in the rule (which lets the page's own colors show through).
   */
  function applyCustomColors(state) {
    var h = document.documentElement;
    var pairs = [
      ['--fv-custom-bg',      state.customBg],
      ['--fv-custom-fg',      state.customFg],
      ['--fv-custom-heading', state.customHeading],
    ];
    for (var i = 0; i < pairs.length; i++) {
      var k = pairs[i][0], v = pairs[i][1];
      if (v) h.style.setProperty(k, v);
      else   h.style.removeProperty(k);
    }
  }

  function readState() {
    var raw = null;
    try { raw = localStorage.getItem(STATE_KEY); } catch (e) {}
    if (!raw) {
      var m = document.cookie.match(/(?:^|;\s*)fv_a11y_state=([^;]+)/);
      if (m) raw = decodeURIComponent(m[1]);
    }
    if (!raw) return {};
    try { return JSON.parse(raw) || {}; } catch (e) { return {}; }
  }

  function writeState(state) {
    var serialized = JSON.stringify(state);
    try { localStorage.setItem(STATE_KEY, serialized); } catch (e) {}
    document.cookie = 'fv_a11y_state=' + encodeURIComponent(serialized)
      + ';path=/;max-age=' + COOKIE_MAX_AGE + ';SameSite=Lax';
  }

  function applyState(state) {
    var html = document.documentElement;
    // Strip every existing fv-* class first so removing a feature actually
    // removes the rule. Anything not starting with `fv-` is preserved (the
    // theme/plugins may attach their own classes to <html>).
    var keep = [];
    var existing = (html.className || '').split(/\s+/);
    for (var i = 0; i < existing.length; i++) {
      if (existing[i] && !/^fv-(?:text-|line-|word-|letter-|page-|readable-|dyslexic-|larger-|highlight-|image-|content-|contrast-|monochrome|invert-|saturation-|pause-|hide-|block-|mute-|custom-|cursor-|keyboard-|reading-|reader-)/.test(existing[i])) {
        keep.push(existing[i]);
      }
    }
    html.className = keep.concat(classesFromState(state)).join(' ');

    // Image captions are a DOM-level feature, not just CSS; toggle them.
    if (state.imageDescriptions) injectImageCaptions(); else removeImageCaptions();

    // Custom color CSS variables.
    applyCustomColors(state);

    // Mute media affects audio/video properties, not just CSS.
    if (state.muteMedia) muteAllMedia();   else unmuteAllMedia();

    // Pause-animations also pauses currently-playing videos (for the
    // "stop motion" expectation that goes beyond CSS animations).
    if (state.pauseAnimations) pauseAllVideos();

    // Navigation/cursor side effects.
    if (state.readingRuler) attachRulerTracking(); else detachRulerTracking();
    if (state.readingMask)  attachMaskTracking();  else detachMaskTracking();
    if (state.readerMode)   ensureReaderModeContent(); else removeReaderModeContent();
    if (state.keyboardNav)  ensureSkipTarget();    else removeSkipTarget();
    if (state.keyboardNav)  enableLandmarkNav();   else disableLandmarkNav();
  }

  /* ─── Reading ruler ─── */
  var rulerRaf = 0, rulerY = 0;
  function onRulerMove(e) {
    rulerY = e.clientY;
    if (rulerRaf) return;
    rulerRaf = requestAnimationFrame(function () {
      document.documentElement.style.setProperty('--fv-ruler-y', rulerY + 'px');
      rulerRaf = 0;
    });
  }
  function attachRulerTracking() { document.addEventListener('mousemove', onRulerMove, { passive: true }); }
  function detachRulerTracking() { document.removeEventListener('mousemove', onRulerMove); }

  /* ─── Reading mask ─── */
  var maskRaf = 0, maskY = 0;
  function onMaskMove(e) {
    maskY = e.clientY;
    if (maskRaf) return;
    maskRaf = requestAnimationFrame(function () {
      document.documentElement.style.setProperty('--fv-mask-y', maskY + 'px');
      maskRaf = 0;
    });
  }
  function attachMaskTracking() { document.addEventListener('mousemove', onMaskMove, { passive: true }); }
  function detachMaskTracking() { document.removeEventListener('mousemove', onMaskMove); }

  /* ─── Reader mode ───
   * Detect the most likely "main content" element using a priority list,
   * clone it into .fv-a11y-reader-mode-content so the original DOM stays
   * untouched. The CSS hides every other body child while .fv-reader-mode
   * is on. */
  function detectMainContent() {
    var candidates = ['main', '[role="main"]', 'article', '.entry-content', '.post-content', '#content', '#main', '#primary'];
    for (var i = 0; i < candidates.length; i++) {
      var el = document.querySelector(candidates[i]);
      if (el && el.textContent && el.textContent.trim().length > 80) return el;
    }
    return null;
  }
  function ensureReaderModeContent() {
    var existing = document.querySelector('.fv-a11y-reader-mode-content');
    if (existing) return;
    var src = detectMainContent();
    var wrapper = document.createElement('article');
    wrapper.className = 'fv-a11y-reader-mode-content';
    if (src) {
      wrapper.innerHTML = src.innerHTML;
    } else {
      wrapper.textContent = 'לא נמצא תוכן ראשי בעמוד זה.';
    }
    if (document.body) document.body.appendChild(wrapper);
  }
  function removeReaderModeContent() {
    var el = document.querySelector('.fv-a11y-reader-mode-content');
    if (el && el.parentNode) el.parentNode.removeChild(el);
  }

  /* ─── Skip link target ───
   * The static skip link points at #fv-a11y-skip-target. Add that id to a
   * sensible main-content element (the same ones reader-mode looks at), so
   * focusing the link actually jumps somewhere useful. */
  function ensureSkipTarget() {
    if (document.getElementById('fv-a11y-skip-target')) return;
    var el = detectMainContent() || document.querySelector('h1') || document.body;
    if (!el) return;
    el.id = el.id || 'fv-a11y-skip-target';
    if (el.id !== 'fv-a11y-skip-target') {
      // Element already had an id; create a sibling anchor instead.
      var a = document.createElement('a');
      a.id = 'fv-a11y-skip-target';
      a.tabIndex = -1;
      el.parentNode.insertBefore(a, el);
    }
  }
  function removeSkipTarget() {
    var t = document.getElementById('fv-a11y-skip-target');
    if (t && t.tagName === 'A' && t.tabIndex === -1) {
      // Only remove if we created it; ids set on existing elements stay.
      if (t.parentNode) t.parentNode.removeChild(t);
    }
  }

  /* ─── Landmark navigation (Alt + 1..9 → nth heading) ─── */
  function landmarkKeydown(e) {
    if (!e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
    var k = parseInt(e.key, 10);
    if (!(k >= 1 && k <= 9)) return;
    var headings = document.querySelectorAll('main h1, main h2, main h3, h1, h2, h3');
    var target = headings[k - 1];
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ block: 'start', behavior: 'smooth' });
    target.tabIndex = -1;
    target.focus();
    flashHighlight(target);
  }
  function enableLandmarkNav()  { document.addEventListener('keydown', landmarkKeydown); }
  function disableLandmarkNav() { document.removeEventListener('keydown', landmarkKeydown); }

  function flashHighlight(el) {
    el.classList.add('fv-a11y-jump-highlight');
    setTimeout(function () { el.classList.remove('fv-a11y-jump-highlight'); }, 1500);
  }

  /* ─── Structure / outline list population ───
   * Builds a fresh list each open. CSS-selector strings are stored on
   * data-jump so the click handler can re-find each target without
   * holding DOM references (which would prevent GC of removed nodes). */
  function selectorForElement(el) {
    if (el.id) return '#' + CSS.escape(el.id);
    // Generate a path using nth-of-type for stability.
    var parts = [];
    var node = el;
    while (node && node.nodeType === 1 && parts.length < 6) {
      var tag = node.nodeName.toLowerCase();
      if (tag === 'html' || tag === 'body') break;
      var sib = node, idx = 1;
      while ((sib = sib.previousElementSibling)) {
        if (sib.nodeName === node.nodeName) idx++;
      }
      parts.unshift(tag + ':nth-of-type(' + idx + ')');
      node = node.parentElement;
    }
    return parts.length ? parts.join(' > ') : el.nodeName.toLowerCase();
  }

  function populateStructureLists(panel, target) {
    if (target === 'outline') {
      var listOutline = panel.querySelector('.fv-a11y-structure-list[data-list="outline"]');
      fillList(listOutline, queryHeadings());
      return;
    }
    fillList(panel.querySelector('.fv-a11y-structure-list[data-list="headings"]'),  queryHeadings());
    fillList(panel.querySelector('.fv-a11y-structure-list[data-list="landmarks"]'), queryLandmarks());
    fillList(panel.querySelector('.fv-a11y-structure-list[data-list="links"]'),     queryLinks());
  }

  function queryHeadings() {
    var els = document.querySelectorAll('main h1, main h2, main h3, main h4, main h5, main h6, body h1, body h2, body h3, body h4, body h5, body h6');
    var out = [], seen = new WeakSet();
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      if (seen.has(el)) continue;
      if (el.closest && el.closest('.fv-a11y-panel')) continue;
      seen.add(el);
      out.push({ el: el, label: (el.textContent || '').trim().slice(0, 80), level: parseInt(el.nodeName.slice(1), 10) });
    }
    return out;
  }
  function queryLandmarks() {
    var sel = 'main, [role="main"], nav, [role="navigation"], aside, [role="complementary"], header, [role="banner"], footer, [role="contentinfo"], section[aria-label], section[aria-labelledby]';
    var els = document.querySelectorAll(sel);
    var out = [];
    for (var i = 0; i < els.length; i++) {
      if (els[i].closest && els[i].closest('.fv-a11y-panel')) continue;
      var name = els[i].getAttribute('aria-label')
        || (els[i].getAttribute('aria-labelledby') && (document.getElementById(els[i].getAttribute('aria-labelledby')) || {}).textContent)
        || els[i].nodeName.toLowerCase();
      out.push({ el: els[i], label: name.toString().trim().slice(0, 80) });
    }
    return out;
  }
  function queryLinks() {
    var els = document.querySelectorAll('main a[href], body a[href]');
    var out = [], seen = new WeakSet();
    for (var i = 0; i < els.length && out.length < 200; i++) {
      var a = els[i];
      if (seen.has(a)) continue;
      if (a.closest && a.closest('.fv-a11y-panel')) continue;
      seen.add(a);
      var t = (a.textContent || '').trim() || a.getAttribute('aria-label') || a.href;
      out.push({ el: a, label: t.slice(0, 80) });
    }
    return out;
  }

  function fillList(listEl, items) {
    if (!listEl) return;
    listEl.innerHTML = '';
    if (!items.length) {
      var empty = document.createElement('li');
      empty.className = 'fv-a11y-structure-empty';
      empty.textContent = 'אין פריטים בעמוד זה.';
      listEl.appendChild(empty);
      return;
    }
    for (var i = 0; i < items.length; i++) {
      var li = document.createElement('li');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.textContent = items[i].label || '(ללא טקסט)';
      btn.setAttribute('data-jump', selectorForElement(items[i].el));
      if (items[i].level) btn.className = 'fv-a11y-h-' + items[i].level;
      li.appendChild(btn);
      listEl.appendChild(li);
    }
  }

  /**
   * Walk all <audio>/<video> elements and mute them. Sets up a
   * MutationObserver (lazy, single instance) so later-added media also
   * starts muted while the feature is on.
   */
  var muteObserver = null;
  function muteAllMedia() {
    var els = document.querySelectorAll('audio, video');
    for (var i = 0; i < els.length; i++) els[i].muted = true;
    if (!muteObserver && 'MutationObserver' in window) {
      muteObserver = new MutationObserver(function (records) {
        for (var i = 0; i < records.length; i++) {
          var added = records[i].addedNodes;
          for (var j = 0; j < added.length; j++) {
            var n = added[j];
            if (n.nodeType !== 1) continue;
            if (n.matches && n.matches('audio, video')) n.muted = true;
            if (n.querySelectorAll) {
              var nested = n.querySelectorAll('audio, video');
              for (var k = 0; k < nested.length; k++) nested[k].muted = true;
            }
          }
        }
      });
      muteObserver.observe(document.body || document.documentElement, { childList: true, subtree: true });
    }
  }

  function unmuteAllMedia() {
    if (muteObserver) { muteObserver.disconnect(); muteObserver = null; }
    // We deliberately don't auto-unmute existing elements — the user may
    // have manually muted some. Toggling the menu doesn't re-broadcast.
  }

  function pauseAllVideos() {
    var vids = document.querySelectorAll('video');
    for (var i = 0; i < vids.length; i++) {
      try { vids[i].pause(); } catch (e) {}
    }
  }

  function announce(panel, msg) {
    var live = panel && panel.querySelector('.fv-a11y-announce');
    if (!live) return;
    // Clearing first then setting forces SR to read even repeats.
    live.textContent = '';
    requestAnimationFrame(function () { live.textContent = msg; });
  }

  function ctlLabel(btn) {
    var l = btn.querySelector('.fv-a11y-ctl-label');
    return l ? l.textContent.trim() : '';
  }

  function updateButton(btn, state) {
    var feature = btn.getAttribute('data-feature');
    var key  = toCamel(feature);
    var type = btn.getAttribute('data-type');
    var stateEl = btn.querySelector('.fv-a11y-ctl-state');
    var val = state[key];

    if (type === 'step') {
      var on = !!val;
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      if (stateEl) stateEl.textContent = on ? (val + '/' + btn.getAttribute('data-steps')) : '';
    } else if (type === 'toggle') {
      var t = !!val;
      btn.classList.toggle('is-active', t);
      btn.setAttribute('aria-pressed', t ? 'true' : 'false');
      if (stateEl) stateEl.textContent = t ? '✓' : '';
    } else if (type === 'cycle') {
      var c = val && val !== 'none' ? String(val) : '';
      btn.classList.toggle('is-active', !!c);
      btn.setAttribute('aria-pressed', c ? 'true' : 'false');
      if (stateEl) stateEl.textContent = c ? labelForCycleValue(c) : '';
    } else if (type === 'profile') {
      var match = profileMatches(feature, state);
      btn.classList.toggle('is-active', match);
      btn.setAttribute('aria-pressed', match ? 'true' : 'false');
    }
    // 'action' type has no on/off state — it just opens a sub-section.
  }

  function labelForCycleValue(v) {
    // Hebrew short labels for the text-align cycle. Keep adding here as
    // more cycles land in later modules.
    var map = { right: 'ימין', left: 'שמאל', center: 'מרכז', justify: 'מלא' };
    return map[v] || v;
  }

  function refreshAllButtons(panel, state) {
    var buttons = panel.querySelectorAll('.fv-a11y-ctl, .fv-a11y-profile-chip');
    for (var i = 0; i < buttons.length; i++) updateButton(buttons[i], state);

    // Sync the color picker inputs to saved state (only when set; otherwise
    // leave the default value so the input shows a sensible starting color).
    var colorMap = { bg: 'customBg', fg: 'customFg', heading: 'customHeading' };
    var inputs = panel.querySelectorAll('input[type="color"][data-color-target]');
    for (var j = 0; j < inputs.length; j++) {
      var key = colorMap[inputs[j].getAttribute('data-color-target')];
      if (key && state[key]) inputs[j].value = state[key];
    }
  }

  function injectImageCaptions() {
    var imgs = document.querySelectorAll('body img:not(.fv-a11y-icon)');
    var i18n = (window.fvA11yConfig && window.fvA11yConfig.i18n) || {};
    for (var i = 0; i < imgs.length; i++) {
      var img = imgs[i];
      if (img.closest && img.closest('.fv-a11y-panel')) continue;
      if (img.nextElementSibling && img.nextElementSibling.classList && img.nextElementSibling.classList.contains('fv-a11y-img-caption')) continue;
      var alt = (img.getAttribute('alt') || '').trim();
      var span = document.createElement('span');
      span.className = 'fv-a11y-img-caption';
      span.textContent = alt || (i18n.imgNoAlt || '(no description)');
      if (img.parentNode) img.parentNode.insertBefore(span, img.nextSibling);
    }
  }

  function removeImageCaptions() {
    var caps = document.querySelectorAll('.fv-a11y-img-caption');
    for (var i = 0; i < caps.length; i++) {
      if (caps[i].parentNode) caps[i].parentNode.removeChild(caps[i]);
    }
  }

  function init() {
    var trigger = document.getElementById('fv-a11y-trigger');
    var panel   = document.getElementById('fv-a11y-panel');
    if (!trigger || !panel) return;
    var closeBtn = panel.querySelector('.fv-a11y-panel-close');
    var titleEl  = panel.querySelector('#fv-a11y-panel-title');

    function openPanel() {
      panel.hidden = false;
      panel.setAttribute('aria-hidden', 'false');
      panel.classList.add('is-open');
      trigger.setAttribute('aria-expanded', 'true');
      if (titleEl) {
        // Defer so the transition starts before focus moves.
        requestAnimationFrame(function () { titleEl.focus(); });
      }
    }

    function closePanel() {
      panel.classList.remove('is-open');
      panel.setAttribute('aria-hidden', 'true');
      panel.hidden = true;
      trigger.setAttribute('aria-expanded', 'false');
      trigger.focus();
    }

    function togglePanel() {
      if (panel.classList.contains('is-open')) closePanel();
      else openPanel();
    }

    trigger.addEventListener('click', togglePanel);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);

    // Apply state immediately (head bootstrap already added classes; this
    // also ensures buttons reflect saved state and image captions exist).
    var state = readState();
    applyState(state);
    refreshAllButtons(panel, state);

    // Feature-control click handler: cycles step/cycle, flips toggles,
    // applies profiles, opens sub-sections for action buttons.
    panel.addEventListener('click', function (e) {
      var ctl = e.target.closest('.fv-a11y-ctl, .fv-a11y-profile-chip');
      if (ctl && panel.contains(ctl)) {
        handleControlClick(ctl);
        return;
      }
      var tab = e.target.closest('.fv-a11y-structure-tabs button');
      if (tab && panel.contains(tab)) {
        var name = tab.getAttribute('data-tab');
        var btns = panel.querySelectorAll('.fv-a11y-structure-tabs button');
        for (var i = 0; i < btns.length; i++) btns[i].classList.toggle('is-active', btns[i] === tab);
        var lists = panel.querySelectorAll('.fv-a11y-structure-list');
        for (var j = 0; j < lists.length; j++) lists[j].hidden = lists[j].getAttribute('data-list') !== name;
        return;
      }
      var jump = e.target.closest('.fv-a11y-structure-list button[data-jump]');
      if (jump && panel.contains(jump)) {
        var sel = jump.getAttribute('data-jump');
        var target = sel ? document.querySelector(sel) : null;
        if (target) {
          target.scrollIntoView({ block: 'start', behavior: 'smooth' });
          target.tabIndex = -1;
          target.focus();
          flashHighlight(target);
          closePanel();
        }
        return;
      }
      var resetBtn = e.target.closest('[data-action="reset"]');
      if (resetBtn && panel.contains(resetBtn)) {
        handleReset();
        return;
      }
      var resetColors = e.target.closest('[data-action="reset-colors"]');
      if (resetColors && panel.contains(resetColors)) {
        var st = readState();
        st.customBg = ''; st.customFg = ''; st.customHeading = '';
        writeState(st);
        applyState(st);
        return;
      }
    });

    // Custom-colors picker (input event for live preview as user drags).
    panel.addEventListener('input', function (e) {
      var input = e.target.closest('input[type="color"][data-color-target]');
      if (!input || !panel.contains(input)) return;
      var which = input.getAttribute('data-color-target');
      var st = readState();
      if (which === 'bg')      st.customBg = input.value;
      if (which === 'fg')      st.customFg = input.value;
      if (which === 'heading') st.customHeading = input.value;
      writeState(st);
      applyState(st);
    });

    function handleControlClick(btn) {
      var feature = btn.getAttribute('data-feature');
      var key  = toCamel(feature);
      var type = btn.getAttribute('data-type');
      var st   = readState();
      var i18n = (window.fvA11yConfig && window.fvA11yConfig.i18n) || {};
      var label = ctlLabel(btn);
      var msg   = '';

      if (type === 'profile') {
        applyProfile(feature, st);
        writeState(st);
        applyState(st);
        refreshAllButtons(panel, st);
        announce(panel, label);
        return;
      }
      if (type === 'action') {
        var target = btn.getAttribute('data-target');
        if (target === 'structure' || target === 'outline') {
          populateStructureLists(panel, target);
        }
        showSection(target);
        var heading = panel.querySelector('.fv-a11y-section[data-section="' + target + '"] .fv-a11y-section-title');
        if (heading) requestAnimationFrame(function () { heading.focus(); });
        return;
      }

      if (type === 'step') {
        var steps = parseInt(btn.getAttribute('data-steps'), 10) || 1;
        st[key] = ((st[key] || 0) + 1) % (steps + 1);
        msg = st[key]
          ? (i18n.announceStep || '%1$s step %2$d of %3$d').replace('%1$s', label).replace('%2$d', st[key]).replace('%3$d', steps)
          : (i18n.announceOff  || '%s off').replace('%s', label);
      } else if (type === 'toggle') {
        st[key] = !st[key];
        // Cursor mutex: enabling one cursor variant forces the other off.
        if (key === 'cursorBlack' && st.cursorBlack) st.cursorWhite = false;
        if (key === 'cursorWhite' && st.cursorWhite) st.cursorBlack = false;
        msg = st[key]
          ? (i18n.announceOn  || '%s on').replace('%s', label)
          : (i18n.announceOff || '%s off').replace('%s', label);
      } else if (type === 'cycle') {
        var cycle = (btn.getAttribute('data-cycle') || '').split(',').filter(Boolean);
        var current = st[key] || '';
        var idx = cycle.indexOf(current);
        // Cycle: -> v0 -> v1 -> ... -> '' (off) -> v0 -> ...
        if (idx < 0) {
          st[key] = cycle[0];
        } else if (idx === cycle.length - 1) {
          st[key] = '';
        } else {
          st[key] = cycle[idx + 1];
        }
        msg = st[key]
          ? (i18n.announceOn || '%s on').replace('%s', label) + ' — ' + labelForCycleValue(st[key])
          : (i18n.announceOff || '%s off').replace('%s', label);
      }

      writeState(st);
      applyState(st);
      updateButton(btn, st);
      announce(panel, msg);
    }

    function handleReset() {
      var i18n = (window.fvA11yConfig && window.fvA11yConfig.i18n) || {};
      writeState({});
      applyState({});
      refreshAllButtons(panel, {});
      announce(panel, i18n.announceReset || 'Reset.');
    }

    // Drawer section switching: any element with data-target swaps sections.
    function showSection(name) {
      var sections = panel.querySelectorAll('.fv-a11y-section');
      for (var i = 0; i < sections.length; i++) {
        var s = sections[i];
        var match = s.getAttribute('data-section') === name;
        s.hidden = !match;
      }
    }
    panel.addEventListener('click', function (e) {
      var target = e.target.closest('[data-target]');
      if (!target || !panel.contains(target)) return;
      var section = target.getAttribute('data-target');
      if (!section) return;
      showSection(section);
      var heading = panel.querySelector('.fv-a11y-section[data-section="' + section + '"] .fv-a11y-section-title, .fv-a11y-section[data-section="' + section + '"] h3');
      if (heading) {
        heading.setAttribute('tabindex', '-1');
        requestAnimationFrame(function () { heading.focus(); });
      }
    });

    // Feedback form submission via admin-ajax.
    var fbForm = panel.querySelector('.fv-a11y-feedback-form');
    if (fbForm) {
      fbForm.addEventListener('submit', function (e) {
        e.preventDefault();
        submitFeedback(fbForm);
      });
    }

    // Parse the configurable shortcut (default: Ctrl+U).
    var cfg   = window.fvA11yConfig || {};
    var sc    = String(cfg.shortcut || 'ctrl+u').toLowerCase();
    var parts = sc.split('+').map(function (p) { return p.trim(); });
    var key   = parts.pop();
    var needCtrl  = parts.indexOf('ctrl') !== -1 || parts.indexOf('control') !== -1 || parts.indexOf('cmd') !== -1 || parts.indexOf('meta') !== -1;
    var needShift = parts.indexOf('shift') !== -1;
    var needAlt   = parts.indexOf('alt') !== -1;

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && panel.classList.contains('is-open')) {
        e.preventDefault();
        closePanel();
        return;
      }
      var k = (e.key || '').toLowerCase();
      if (k !== key) return;
      if (needCtrl  !== (e.ctrlKey || e.metaKey)) return;
      if (needShift !== e.shiftKey) return;
      if (needAlt   !== e.altKey) return;
      e.preventDefault();
      togglePanel();
    });
  }

  function submitFeedback(form) {
    var cfg = window.fvA11yConfig || {};
    var i18n = cfg.i18n || {};
    var status = form.querySelector('.fv-a11y-fb-status');
    var submit = form.querySelector('.fv-a11y-fb-submit');
    var submitText = form.querySelector('.fv-a11y-fb-submit-text');

    if (!cfg.ajax || !cfg.ajax.url) {
      if (status) status.textContent = i18n.errorGeneric || 'Error';
      return;
    }

    var data = new FormData(form);
    data.append('action', cfg.ajax.action || 'fv_a11y_feedback');

    if (submit) submit.disabled = true;
    if (submitText) submitText.textContent = i18n.sending || 'Sending...';
    if (status) {
      status.className = 'fv-a11y-fb-status';
      status.textContent = '';
    }

    fetch(cfg.ajax.url, {
      method: 'POST',
      body: data,
      credentials: 'same-origin',
    })
      .then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok, body: j }; });
      })
      .then(function (res) {
        var msg = (res.body && res.body.data && res.body.data.message) || '';
        if (res.body && res.body.success) {
          form.reset();
          if (status) {
            status.classList.add('is-success');
            status.textContent = msg;
          }
        } else {
          if (status) {
            status.classList.add('is-error');
            status.textContent = msg || (i18n.errorGeneric || 'Error');
          }
        }
      })
      .catch(function () {
        if (status) {
          status.classList.add('is-error');
          status.textContent = i18n.errorGeneric || 'Error';
        }
      })
      .finally(function () {
        if (submit) submit.disabled = false;
        if (submitText) submitText.textContent = i18n.submit || 'Submit';
      });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
