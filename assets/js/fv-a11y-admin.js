/* FV Accessibility — admin Settings → Position page interactions. */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    initDeviceToggle();
    initAnchorPickers();
  });

  /**
   * Two pill-buttons (Desktop / Mobile) toggle which device's form is
   * visible. Both forms remain in the DOM so the form post sends both.
   */
  function initDeviceToggle() {
    var btns = document.querySelectorAll('.fv-a11y-device-btn');
    if (!btns.length) return;
    btns.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var device = btn.getAttribute('data-device');
        btns.forEach(function (b) {
          var active = b === btn;
          b.classList.toggle('is-active', active);
          b.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        document.querySelectorAll('.fv-a11y-device-form').forEach(function (f) {
          f.hidden = f.getAttribute('data-device') !== device;
        });
      });
    });
  }

  /**
   * 3×3 visual anchor picker. Clicking a cell:
   *   - Marks it active (visual)
   *   - Updates the hidden inputs that ride along with the form post.
   * Keyboard support: arrow keys move between cells inside the same picker.
   */
  function initAnchorPickers() {
    document.querySelectorAll('.fv-a11y-pos-picker').forEach(function (picker) {
      var cells = picker.querySelectorAll('.fv-a11y-pos-cell');
      var form = picker.closest('.fv-a11y-device-form');
      if (!form) return;
      var sideInput   = form.querySelector('[data-pos-input="side"]');
      var anchorInput = form.querySelector('[data-pos-input="anchor"]');

      cells.forEach(function (cell) {
        cell.addEventListener('click', function () {
          cells.forEach(function (c) {
            c.classList.remove('is-active');
            c.setAttribute('aria-checked', 'false');
          });
          cell.classList.add('is-active');
          cell.setAttribute('aria-checked', 'true');
          if (sideInput)   sideInput.value   = cell.getAttribute('data-side');
          if (anchorInput) anchorInput.value = cell.getAttribute('data-anchor');
        });
      });

      picker.addEventListener('keydown', function (e) {
        if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) return;
        var current = picker.querySelector('.fv-a11y-pos-cell.is-active') || cells[0];
        var idx = Array.prototype.indexOf.call(cells, current);
        var col = idx % 3, row = Math.floor(idx / 3);
        if (e.key === 'ArrowUp')    row = Math.max(0, row - 1);
        if (e.key === 'ArrowDown')  row = Math.min(2, row + 1);
        // In RTL admin, ArrowLeft increases column index; ArrowRight decreases.
        var rtl = document.documentElement.getAttribute('dir') === 'rtl' || getComputedStyle(picker).direction === 'rtl';
        if (e.key === 'ArrowLeft')  col = rtl ? Math.min(2, col + 1) : Math.max(0, col - 1);
        if (e.key === 'ArrowRight') col = rtl ? Math.max(0, col - 1) : Math.min(2, col + 1);
        var target = cells[row * 3 + col];
        if (target) {
          e.preventDefault();
          target.focus();
          target.click();
        }
      });
    });
  }
})();
