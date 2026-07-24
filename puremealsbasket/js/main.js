/* ============================================
   PURE MEALS BASKET — main.js
   1. Mobile navbar toggle
   2. Smooth scroll for anchor links
   3. Navbar shrink on scroll
   4. Feedback form AJAX submission
   5. Star rating interactive highlight
   ============================================ */

document.addEventListener('DOMContentLoaded', function () {

  /* ---------- 1. Mobile navbar hamburger toggle ---------- */
  var navToggle = document.getElementById('navbar-toggle');
  var navMenu = document.getElementById('navbar-nav');

  if (navToggle && navMenu) {
    navToggle.addEventListener('click', function () {
      var isOpen = navMenu.classList.toggle('open');
      navToggle.classList.toggle('active', isOpen);
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    navMenu.querySelectorAll('a').forEach(function (link) {
      link.addEventListener('click', function () {
        navMenu.classList.remove('open');
        navToggle.classList.remove('active');
        navToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  /* ---------- 2. Smooth scroll for all anchor links ---------- */
  document.querySelectorAll('a[href^="#"]').forEach(function (anchor) {
    anchor.addEventListener('click', function (e) {
      var targetId = anchor.getAttribute('href');
      if (targetId.length < 2) return;
      var target = document.querySelector(targetId);
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* ---------- 3. Navbar shrink class on scroll past 80px ---------- */
  var navbar = document.getElementById('navbar');
  function handleNavbarScroll() {
    if (window.scrollY > 80) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }
  if (navbar) {
    window.addEventListener('scroll', handleNavbarScroll, { passive: true });
    handleNavbarScroll();
  }

  /* ---------- 4. Feedback form AJAX submission ---------- */
  var feedbackForm = document.getElementById('feedback-form');
  var formMessage = document.getElementById('form-message');

  if (feedbackForm) {
    feedbackForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var submitBtn = feedbackForm.querySelector('.btn-form-submit');
      submitBtn.disabled = true;

      formMessage.textContent = '';
      formMessage.classList.remove('success', 'error');

      var formData = new FormData(feedbackForm);

      fetch(feedbackForm.getAttribute('action'), {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (response) {
          return response.json().catch(function () {
            throw new Error('Invalid response');
          });
        })
        .then(function (data) {
          if (data && data.success) {
            formMessage.textContent = 'Asante sana! Thank you for your feedback. We truly appreciate you taking the time to share your experience with us.';
            formMessage.classList.add('success');
            feedbackForm.reset();
          } else {
            throw new Error((data && data.message) || 'Submission failed');
          }
        })
        .catch(function () {
          formMessage.textContent = 'Something went wrong. Please try again or reach us directly on WhatsApp.';
          formMessage.classList.add('error');
        })
        .finally(function () {
          submitBtn.disabled = false;
        });
    });
  }

  /* ---------- 5. Star rating interactive highlight ---------- */
  var starRating = document.getElementById('star-rating');
  if (starRating) {
    var labels = Array.prototype.slice.call(starRating.querySelectorAll('label'));

    labels.forEach(function (label) {
      label.addEventListener('mouseenter', function () {
        starRating.classList.add('hovered');
        label.classList.add('hovered');
      });
      label.addEventListener('mouseleave', function () {
        starRating.classList.remove('hovered');
        label.classList.remove('hovered');
      });
    });
  }

});

/* PHASE 2: Add scroll animation for section entry */
/* PHASE 2: WhatsApp chat widget */
/* PHASE 3: Franchise inquiry modal triggered by footer link */
