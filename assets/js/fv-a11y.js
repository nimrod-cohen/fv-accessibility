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
