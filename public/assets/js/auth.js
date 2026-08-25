/* ============================================
   PURE MEALS BASKET — auth.js
   1. Password visibility toggle
   2. Form validation feedback
   3. Smooth form transitions
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {

  /* ---------- 1. Password visibility toggle ---------- */
  document.querySelectorAll('.password-wrapper').forEach(function (wrapper) {
    var input = wrapper.querySelector('input[type="password"], input[type="text"]');
    var toggle = wrapper.querySelector('.password-toggle');

    if (input && toggle) {
      toggle.addEventListener('click', function () {
        var isPassword = input.type === 'password';
        input.type = isPassword ? 'text' : 'password';
        toggle.setAttribute('aria-label', isPassword ? 'Hide password' : 'Show password');
        toggle.classList.toggle('visible', isPassword);
      });
    }
  });

  /* ---------- 2. Form validation feedback ---------- */
  document.querySelectorAll('.auth-form').forEach(function (form) {
    var submitBtn = form.querySelector('button[type="submit"]');

    form.addEventListener('submit', function () {
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Please wait...';
      }
    });

    // Re-enable button on input change
    form.querySelectorAll('input').forEach(function (input) {
      input.addEventListener('input', function () {
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = submitBtn.dataset.originalText || submitBtn.textContent;
        }
      });
    });
  });

  /* ---------- 3. Smooth link transitions ---------- */
  document.querySelectorAll('.auth-links a, .btn').forEach(function (el) {
    el.addEventListener('click', function () {
      el.style.opacity = '0.8';
      setTimeout(function () {
        el.style.opacity = '1';
      }, 150);
    });
  });

});
