/* FV Accessibility — frontend trigger + drawer skeleton (vanilla JS, no jQuery). */
(function () {
  'use strict';
  if (window.fvA11yLoaded) return;
  window.fvA11yLoaded = true;

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

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
