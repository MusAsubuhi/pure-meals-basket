/* ============================================
   PURE MEALS BASKET — main.js
   1. Mobile navbar toggle
   2. Smooth scroll for anchor links
   3. Navbar shrink on scroll
   4. Feedback form Supabase submission
   5. Star rating interactive highlight
   6. Place an Order form (service selection + Supabase submission)
   ============================================ */

const SUPABASE_URL = 'https://pwzdbuzwblgflpadbfxv.supabase.co';
const SUPABASE_ANON_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InB3emRidXp3YmxnZmxwYWRiZnh2Iiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODUyMDE5ODgsImV4cCI6MjEwMDc3Nzk4OH0.HahdEv-TnS72wSmPCbFPCEDU47j_tqHaQI6gRsrcN14';

let supabaseClient = null;
if (window.supabase && window.supabase.createClient) {
  supabaseClient = window.supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
}

const PMB_WHATSAPP_NUMBER = '+254737953292';

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

  /* ---------- 4. Feedback form Supabase submission ---------- */
  var feedbackForm = document.getElementById('feedback-form');
  var formMessage = document.getElementById('form-message');

  if (feedbackForm) {
    feedbackForm.addEventListener('submit', function (e) {
      e.preventDefault();

      var submitBtn = feedbackForm.querySelector('.btn-form-submit');
      submitBtn.disabled = true;

      formMessage.textContent = '';
      formMessage.classList.remove('success', 'error');

      var ratingInput = feedbackForm.querySelector('input[name="rating"]:checked');

      var payload = {
        name: document.getElementById('name').value.trim(),
        phone: document.getElementById('phone').value.trim(),
        event_type: document.getElementById('event-type').value,
        experience: document.getElementById('experience').value.trim(),
        star_rating: ratingInput ? Number(ratingInput.value) : null
      };

      supabaseClient.from('feedback').insert([payload])
        .then(function (result) {
          if (result.error) {
            throw result.error;
          }
          formMessage.textContent = 'Asante sana! Thank you for your feedback. We truly appreciate you taking the time to share your experience with us.';
          formMessage.classList.add('success');
          feedbackForm.reset();
        })
        .catch(function () {
          formMessage.textContent = 'Something went wrong. Please try again or contact PMB directly on WhatsApp at ' + PMB_WHATSAPP_NUMBER + '.';
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

  /* ---------- 6. Place an Order form ---------- */
  var orderServiceCards = Array.prototype.slice.call(document.querySelectorAll('.order-service-card'));
  var orderForm = document.getElementById('order-form');
  var orderFieldGroups = Array.prototype.slice.call(document.querySelectorAll('.order-fields'));
  var orderFormMessage = document.getElementById('order-form-message');
  var selectedService = null;

  var SERVICE_LABELS = {
    catering: 'Catering',
    juice: 'Juices & Beverages',
    cakes: 'Cakes & Celebration Foods'
  };

  // Date pickers can't accept the past — floor them at today.
  var todayISO = new Date().toISOString().split('T')[0];
  ['catering-event-date', 'juice-date', 'cake-date'].forEach(function (id) {
    var input = document.getElementById(id);
    if (input) input.min = todayISO;
  });

  function fieldValue(id) {
    var el = document.getElementById(id);
    if (!el) return null;
    var value = el.value.trim();
    return value === '' ? null : value;
  }

  function fieldNumber(id) {
    var el = document.getElementById(id);
    if (!el || el.value === '') return null;
    return Number(el.value);
  }

  function checkedRadioValue(name) {
    var checked = document.querySelector('input[name="' + name + '"]:checked');
    return checked ? checked.value : null;
  }

  function checkedCheckboxValues(name, otherTextId) {
    var checked = Array.prototype.slice.call(document.querySelectorAll('input[name="' + name + '"]:checked'));
    var otherInput = document.getElementById(otherTextId);
    var values = checked.map(function (input) {
      if (input.value === 'Other' && otherInput && otherInput.value.trim()) {
        return otherInput.value.trim();
      }
      return input.value;
    });
    return values.length ? values.join(', ') : null;
  }

  function cakeSizeValue() {
    var select = document.getElementById('cake-size');
    if (!select || !select.value) return null;
    if (select.value === 'Custom') {
      var notes = fieldValue('cake-size-custom-notes');
      return notes ? 'Custom: ' + notes : 'Custom';
    }
    return select.value;
  }

  function showServiceFields(service) {
    orderForm.hidden = false;
    orderFieldGroups.forEach(function (group) {
      group.hidden = group.getAttribute('data-service-fields') !== service;
    });
  }

  orderServiceCards.forEach(function (card) {
    card.addEventListener('click', function () {
      selectedService = card.getAttribute('data-service');

      orderServiceCards.forEach(function (c) {
        var isSelected = c === card;
        c.classList.toggle('is-selected', isSelected);
        c.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
      });

      showServiceFields(selectedService);
      orderForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
  });

  // "Other" checkboxes and "Custom" cake size reveal a follow-up text field.
  function bindConditionalReveal(triggerId, groupId, isRevealed) {
    var trigger = document.getElementById(triggerId);
    var group = document.getElementById(groupId);
    if (!trigger || !group) return;
    trigger.addEventListener('change', function () {
      group.hidden = !isRevealed(trigger);
    });
  }

  bindConditionalReveal('juice-flavour-other-check', 'juice-flavour-other-group', function (el) { return el.checked; });
  bindConditionalReveal('cake-flavour-other-check', 'cake-flavour-other-group', function (el) { return el.checked; });
  bindConditionalReveal('cake-size', 'cake-size-custom-group', function (el) { return el.value === 'Custom'; });

  if (orderForm) {
    orderForm.addEventListener('submit', function (e) {
      e.preventDefault();
      if (!selectedService) return;

      var submitBtn = orderForm.querySelector('.btn-form-submit');
      submitBtn.disabled = true;

      orderFormMessage.textContent = '';
      orderFormMessage.classList.remove('success', 'error');

      var payload = {
        name: fieldValue('order-name'),
        phone: fieldValue('order-phone'),
        service_type: SERVICE_LABELS[selectedService],
        referral_source: fieldValue('order-referral')
      };

      if (selectedService === 'catering') {
        payload.event_type = fieldValue('catering-event-type');
        payload.event_date = fieldValue('catering-event-date');
        payload.attendee_count = fieldNumber('catering-attendees');
        payload.venue = fieldValue('catering-venue');
        payload.dietary_notes = fieldValue('catering-notes');
      } else if (selectedService === 'juice') {
        payload.juice_quantity_litres = fieldNumber('juice-quantity');
        payload.juice_flavours = checkedCheckboxValues('juice_flavours', 'juice-flavour-other-text');
        payload.juice_delivery = checkedRadioValue('juice_delivery');
        payload.delivery_date = fieldValue('juice-date');
      } else if (selectedService === 'cakes') {
        payload.cake_occasion = fieldValue('cake-occasion');
        payload.cake_size = cakeSizeValue();
        payload.cake_flavour = checkedCheckboxValues('cake_flavour', 'cake-flavour-other-text');
        payload.cake_decoration_notes = fieldValue('cake-decoration-notes');
        payload.delivery_date = fieldValue('cake-date');
      }

      supabaseClient.from('orders').insert([payload])
        .then(function (result) {
          if (result.error) {
            throw result.error;
          }
          orderFormMessage.textContent = 'Asante sana! Your order request has been received. We will confirm within 24 hours.';
          orderFormMessage.classList.add('success');

          orderForm.reset();
          orderForm.hidden = true;
          orderFieldGroups.forEach(function (group) { group.hidden = true; });
          ['juice-flavour-other-group', 'cake-flavour-other-group', 'cake-size-custom-group'].forEach(function (id) {
            var group = document.getElementById(id);
            if (group) group.hidden = true;
          });
          orderServiceCards.forEach(function (c) {
            c.classList.remove('is-selected');
            c.setAttribute('aria-pressed', 'false');
          });
          selectedService = null;
        })
        .catch(function () {
          orderFormMessage.textContent = 'Something went wrong. Please try again or contact PMB directly on WhatsApp at ' + PMB_WHATSAPP_NUMBER + '.';
          orderFormMessage.classList.add('error');
        })
        .finally(function () {
          submitBtn.disabled = false;
        });
    });
  }

});

/* PHASE 2: Add scroll animation for section entry */
/* PHASE 2: WhatsApp chat widget */
/* PHASE 3: Franchise inquiry modal triggered by footer link */
