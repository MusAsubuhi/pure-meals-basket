@extends('layouts.app')

@section('title', $service->name . ' — Pure Meals Basket')

@section('content')

@push('styles')
<style>
  .service-hero {
    position: relative;
    min-height: 35vh;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    background: var(--pmb-brown);
  }

  .service-hero-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(44,26,14,0.55) 0%, rgba(44,26,14,0.85) 100%);
  }

  .service-hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    color: var(--pmb-white);
    padding: 5rem 1.25rem 2.5rem;
    max-width: 800px;
  }

  .service-hero-content h1 {
    color: var(--pmb-white);
    margin-bottom: 0.75rem;
    text-shadow: 0 2px 16px rgba(0,0,0,0.25);
  }

  .service-hero-content p {
    opacity: 0.9;
    font-weight: 300;
    font-size: 1.05rem;
  }

  .service-back {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    color: var(--pmb-gold);
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 1.5rem;
  }

  .service-back:hover {
    color: var(--pmb-gold-light);
  }

  .service-layout {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 2.5rem;
    align-items: start;
  }

  .service-info-section {
    background: var(--pmb-white);
    border-radius: var(--radius-card);
    overflow: hidden;
    box-shadow: var(--shadow-soft);
  }

  .service-info-section img {
    width: 100%;
    height: 320px;
    object-fit: cover;
    display: block;
  }

  .service-info-placeholder {
    width: 100%;
    height: 320px;
    background: var(--pmb-cream);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--pmb-gold);
    font-size: 3rem;
  }

  .service-info-body {
    padding: 1.5rem;
  }

  .service-info-body h2 {
    margin-bottom: 0.75rem;
  }

  .service-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin-bottom: 1.25rem;
  }

  .service-meta-badge {
    display: inline-block;
    font-size: 0.8rem;
    padding: 0.35rem 0.75rem;
    border-radius: 999px;
    background: var(--pmb-cream);
    color: var(--pmb-brown);
    font-weight: 600;
  }

  .service-meta-badge.unavailable {
    background: #FBECE6;
    color: #B3401E;
  }

  .service-description {
    color: rgba(44,26,14,0.75);
    line-height: 1.7;
    margin-bottom: 1.5rem;
  }

  .service-order-panel {
    background: var(--pmb-white);
    border-radius: var(--radius-card);
    box-shadow: var(--shadow-soft);
    padding: 1.5rem;
    position: sticky;
    top: 90px;
  }

  .service-order-panel h3 {
    margin-bottom: 1.25rem;
    font-size: 1.15rem;
  }

  .form-group {
    margin-bottom: 1rem;
  }

  .form-group label {
    display: block;
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--pmb-brown);
    margin-bottom: 0.35rem;
  }

  .form-group input[type="number"],
  .form-group select {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border: 1.5px solid #E4D5BF;
    border-radius: var(--radius-card);
    font-family: var(--font-body);
    font-size: 0.95rem;
    background: var(--pmb-white);
    color: var(--pmb-brown);
    transition: border-color var(--transition-base);
  }

  .form-group input[type="number"]:focus,
  .form-group select:focus {
    outline: none;
    border-color: var(--pmb-gold);
  }

  .form-group select {
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%232C1A0E' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.85rem center;
    padding-right: 2.5rem;
  }

  .option-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: var(--pmb-brown);
    margin-bottom: 0.35rem;
  }

  .option-label .required {
    color: #dc2626;
  }

  .option-select {
    width: 100%;
    padding: 0.65rem 0.85rem;
    border: 1.5px solid #E4D5BF;
    border-radius: var(--radius-card);
    font-family: var(--font-body);
    font-size: 0.95rem;
    background: var(--pmb-white);
    color: var(--pmb-brown);
    margin-bottom: 1rem;
    cursor: pointer;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%232C1A0E' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.85rem center;
    padding-right: 2.5rem;
  }

  .option-select:focus {
    outline: none;
    border-color: var(--pmb-gold);
  }

  .option-price {
    font-size: 0.8rem;
    color: var(--pmb-gold);
    font-weight: 600;
    margin-left: 0.35rem;
  }

  .quote-summary {
    background: var(--pmb-cream);
    border-radius: var(--radius-card);
    padding: 1rem;
    margin: 1.25rem 0;
  }

  .quote-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
  }

  .quote-row.total {
    font-weight: 700;
    font-size: 1.1rem;
    color: var(--pmb-gold);
    border-top: 1px solid #E4D5BF;
    padding-top: 0.75rem;
    margin-top: 0.75rem;
    margin-bottom: 0;
  }

  .quote-error {
    color: #dc2626;
    font-size: 0.85rem;
    margin-top: 0.75rem;
  }

  .quote-custom-note {
    background: #FEF3C7;
    border-radius: var(--radius-card);
    padding: 0.85rem;
    font-size: 0.85rem;
    color: #92400e;
    margin: 1rem 0;
  }

  .btn-add-cart {
    width: 100%;
    padding: 0.85rem;
    background: var(--pmb-gold);
    color: var(--pmb-brown);
    border: none;
    border-radius: var(--radius-pill);
    font-family: var(--font-body);
    font-weight: 700;
    font-size: 0.95rem;
    cursor: pointer;
    transition: background var(--transition-base), transform var(--transition-base);
  }

  .btn-add-cart:hover {
    background: var(--pmb-gold-light);
    transform: scale(1.02);
  }

  .btn-add-cart:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
  }

  .login-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(44, 26, 14, 0.55);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 2000;
  }

  .login-modal-overlay.is-visible {
    display: flex;
  }

  .login-modal {
    background: var(--pmb-white);
    border-radius: var(--radius-card);
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    text-align: center;
    box-shadow: var(--shadow-card);
  }

  .login-modal p {
    margin-bottom: 1.25rem;
    color: var(--pmb-brown);
    font-size: 0.95rem;
  }

  .login-modal .btn-gold {
    display: inline-block;
    background: var(--pmb-gold);
    color: var(--pmb-brown);
    padding: 0.7rem 1.75rem;
    border-radius: var(--radius-pill);
    font-weight: 700;
    text-decoration: none;
  }

  .login-modal .btn-gold:hover {
    background: var(--pmb-gold-light);
    color: var(--pmb-brown);
  }

  @media (max-width: 900px) {
    .service-layout {
      grid-template-columns: 1fr;
    }
    .service-order-panel {
      position: static;
    }
  }
</style>

<div class="service-hero">
  <div class="service-hero-overlay"></div>
  <div class="service-hero-content container">
    <a href="{{ route('catalogue.index') }}" class="service-back">&larr; All services</a>
    <h1>{{ $service->name }}</h1>
    <p>{{ $service->short_description ?? 'Customise and add to your request.' }}</p>
  </div>
</div>

<section class="catalogue-section">
  <div class="container">
    <div class="service-layout">
      <div>
        <div class="service-info-section">
          @if($service->image_path)
            <img src="{{ asset('storage/' . ltrim($service->image_path, '/')) }}" alt="{{ $service->name }}" loading="lazy">
          @else
            <div class="service-info-placeholder">&#128248;</div>
          @endif
          <div class="service-info-body">
            <div class="service-meta">
              <span class="service-meta-badge">{{ $service->pricing_type->label() }}</span>
              @if($service->unit)
                <span class="service-meta-badge">Unit: {{ $service->unit }}</span>
              @endif
              <span class="service-meta-badge {{ $service->is_available ? '' : 'unavailable' }}">
                {{ $service->is_available ? 'Available' : 'Currently unavailable' }}
              </span>
              @if($service->requires_review)
                <span class="service-meta-badge" style="background:#FEF3C7;color:#92400e;">Requires review</span>
              @endif
            </div>
            @if($service->description)
              <p class="service-description">{!! nl2br(e($service->description)) !!}</p>
            @endif
          </div>
        </div>
      </div>

      <div class="service-order-panel">
        <h3>Configure your order</h3>

        @if($service->pricing_type->usesQuantity())
          <div class="form-group">
            <label for="quantity">Quantity{{ $service->unit ? ' (' . $service->unit . ')' : '' }}</label>
            <input type="number" id="quantity"
                   value="{{ $service->minimum_quantity ?? 1 }}"
                   min="{{ $service->minimum_quantity ?? 0.01 }}"
                   max="{{ $service->maximum_quantity ?? '' }}"
                   step="0.5">
          </div>
        @endif

        <div class="quote-summary">
          <div class="quote-row">
            <span>Estimated total</span>
            <span id="quote-total">Calculating…</span>
          </div>
        </div>

        <div id="quote-custom-note" class="quote-custom-note" style="display: none;">
          <strong>Quotation required</strong><br>
          <small>PMB will review your request and confirm a price.</small>
        </div>

        <div id="quote-error" class="quote-error" style="display: none;"></div>

        <button type="button" id="btn-add-cart" class="btn-add-cart">
          @if($service->pricing_type === \App\Enums\PricingType::CUSTOM)
            Request Quotation
          @else
            Add to Request
          @endif
        </button>
      </div>
    </div>
  </div>
</section>

<div id="login-modal" class="login-modal-overlay" style="display: none;">
  <div class="login-modal">
    <p>You need to be logged in to add items to your request.</p>
    <p style="font-size: 0.85rem; color: rgba(44,26,14,0.7);">Redirecting you to login…</p>
    <a href="{{ route('login') }}" class="btn-gold">Log in now</a>
  </div>
</div>

<script>
(function () {
  'use strict';

  var serviceId = {{ $service->id }};
  var usesQuantity = {{ $service->pricing_type->usesQuantity() ? 'true' : 'false' }};
  var quoteUrl = '{{ route('catalogue.quote') }}';
  var addToCartUrl = '{{ route('catalogue.addService', $service) }}';
  var cartUrl = '{{ route('request.cart') }}';
  var csrfToken = '{{ csrf_token() }}';

  var quantityInput = document.getElementById('quantity');
  var quoteTotal = document.getElementById('quote-total');
  var quoteError = document.getElementById('quote-error');
  var quoteCustomNote = document.getElementById('quote-custom-note');
  var addToCartBtn = document.getElementById('btn-add-cart');

  var currentQuote = {
    total: null,
    requiresQuote: false,
    error: null
  };

  function getQuantity() {
    if (!usesQuantity) return 1;
    var value = quantityInput ? parseFloat(quantityInput.value) : null;
    if (isNaN(value) || value <= 0) return null;
    return value;
  }

  async function requote() {
    var payload = {
      type: 'service',
      id: serviceId,
      quantity: getQuantity() || 1
    };

    console.log('PMB quote request:', payload);

    try {
      var res = await fetch(quoteUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(payload)
      });

      console.log('PMB quote response status:', res.status);

      var data = await res.json();
      console.log('PMB quote response data:', data);

      if (!res.ok) {
        currentQuote.error = data.message || 'Could not calculate a price. (status: ' + res.status + ')';
        currentQuote.total = null;
        currentQuote.requiresQuote = false;
      } else {
        currentQuote.error = null;
        currentQuote.total = data.total;
        currentQuote.requiresQuote = data.requires_pmb_quote;
      }
    } catch (e) {
      console.error('PMB quote error:', e);
      currentQuote.error = 'Could not reach the pricing service.';
      currentQuote.total = null;
      currentQuote.requiresQuote = false;
    }

    updateUI();
  }

  function updateUI() {
    if (currentQuote.error) {
      quoteTotal.textContent = '—';
      quoteError.textContent = currentQuote.error;
      quoteError.style.display = 'block';
      quoteCustomNote.style.display = 'none';
      addToCartBtn.disabled = true;
    } else if (currentQuote.requiresQuote) {
      quoteTotal.textContent = 'Quotation';
      quoteError.style.display = 'none';
      quoteCustomNote.style.display = 'block';
      addToCartBtn.disabled = false;
    } else if (currentQuote.total !== null) {
      quoteTotal.textContent = 'KSh ' + Number(currentQuote.total).toLocaleString(undefined, { minimumFractionDigits: 2 });
      quoteError.style.display = 'none';
      quoteCustomNote.style.display = 'none';
      addToCartBtn.disabled = false;
    } else {
      quoteTotal.textContent = 'Calculating…';
      quoteError.style.display = 'none';
      quoteCustomNote.style.display = 'none';
      addToCartBtn.disabled = true;
    }
  }

  function showLoginModal() {
    var modal = document.getElementById('login-modal');
    if (modal) {
      modal.style.display = 'flex';
      setTimeout(function () {
        window.location.href = '{{ route('login') }}';
      }, 1500);
    }
  }

  async function addToCart() {
    if (currentQuote.error) return;

    var payload = {
      quantity: getQuantity() || 1
    };

    console.log('PMB addToCart request:', payload);

    try {
      var res = await fetch(addToCartUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(payload)
      });

      console.log('PMB addToCart response status:', res.status, 'url:', res.url);

      var isLoginPage = res.url && res.url.indexOf('/login') !== -1;
      if (res.status === 401 || res.status === 302 || isLoginPage) {
        showLoginModal();
        return;
      }

      var data = await res.json();
      console.log('PMB addToCart response data:', data);

      if (res.ok && data.success) {
        window.location.href = cartUrl;
      } else {
        currentQuote.error = (data.message || 'Could not add to cart.') + ' (status: ' + res.status + ')';
        updateUI();
      }
    } catch (e) {
      console.error('PMB addToCart error:', e);
      currentQuote.error = 'Could not reach the server.';
      updateUI();
    }
  }

  if (quantityInput) {
    quantityInput.addEventListener('input', function () {
      requote();
    });
  }

  addToCartBtn.addEventListener('click', addToCart);

  requote();
})();
</script>

@endsection
