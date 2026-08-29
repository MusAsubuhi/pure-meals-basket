@extends('layouts.app')

@section('title', $product->name . ' — Pure Meals Basket')

@section('content')

@push('styles')
<style>
.product-hero {
  position: relative;
  min-height: 35vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: var(--pmb-brown);
}

.product-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(44,26,14,0.55) 0%, rgba(44,26,14,0.85) 100%);
}

.product-hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: var(--pmb-white);
  padding: 5rem 1.25rem 2.5rem;
  max-width: 800px;
}

.product-hero-content h1 {
  color: var(--pmb-white);
  margin-bottom: 0.75rem;
  text-shadow: 0 2px 16px rgba(0,0,0,0.25);
}

.product-hero-content p {
  opacity: 0.9;
  font-weight: 300;
  font-size: 1.05rem;
}

.product-back {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--pmb-gold);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.product-back:hover {
  color: var(--pmb-gold-light);
}

.product-layout {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 2.5rem;
  align-items: start;
}

.product-image-section {
  background: var(--pmb-white);
  border-radius: var(--radius-card);
  overflow: hidden;
  box-shadow: var(--shadow-soft);
}

.product-image-section img {
  width: 100%;
  height: 320px;
  object-fit: cover;
  display: block;
}

.product-image-placeholder {
  width: 100%;
  height: 320px;
  background: var(--pmb-cream);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--pmb-gold);
  font-size: 3rem;
}

.product-info {
  padding: 1.5rem;
}

.product-info h2 {
  margin-bottom: 0.75rem;
}

.product-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.product-meta-badge {
  display: inline-block;
  font-size: 0.8rem;
  padding: 0.35rem 0.75rem;
  border-radius: 999px;
  background: var(--pmb-cream);
  color: var(--pmb-brown);
  font-weight: 600;
}

.product-meta-badge.unavailable {
  background: #FBECE6;
  color: #B3401E;
}

.product-description {
  color: rgba(44,26,14,0.75);
  line-height: 1.7;
  margin-bottom: 1.5rem;
}

.product-order-panel {
  background: var(--pmb-white);
  border-radius: var(--radius-card);
  box-shadow: var(--shadow-soft);
  padding: 1.5rem;
  position: sticky;
  top: 90px;
}

.product-order-panel h3 {
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

@media (max-width: 900px) {
  .product-layout {
    grid-template-columns: 1fr;
  }
  .product-order-panel {
    position: static;
  }
}
</style>

<div class="product-hero">
  <div class="product-hero-overlay"></div>
  <div class="product-hero-content container">
    <a href="{{ route('catalogue.category', $product->category) }}" class="product-back">&larr; {{ $product->category->name }}</a>
    <h1>{{ $product->name }}</h1>
    <p>{{ $product->short_description ?? 'Customise and add to your request.' }}</p>
  </div>
</div>

<section class="catalogue-section">
  <div class="container">
    <div class="product-layout">
      <div>
        <div class="product-image-section">
          @if($product->image_path)
            <img src="{{ asset('storage/' . ltrim($product->image_path, '/')) }}" alt="{{ $product->name }}" loading="lazy">
          @else
            <div class="product-image-placeholder">&#128248;</div>
          @endif
          <div class="product-info">
            <div class="product-meta">
              <span class="product-meta-badge">{{ $product->pricing_type->label() }}</span>
              @if($product->unit)
                <span class="product-meta-badge">Unit: {{ $product->unit }}</span>
              @endif
              <span class="product-meta-badge {{ $product->is_available ? '' : 'unavailable' }}">
                {{ $product->is_available ? 'Available' : 'Currently unavailable' }}
              </span>
            </div>
            @if($product->description)
              <p class="product-description">{!! nl2br(e($product->description)) !!}</p>
            @endif
          </div>
        </div>
      </div>

      <div class="product-order-panel">
        <h3>Configure your order</h3>

        @if($product->pricing_type->usesQuantity())
          <div class="form-group">
            <label for="quantity">Quantity{{ $product->unit ? ' (' . $product->unit . ')' : '' }}</label>
            <input type="number" id="quantity"
                   value="{{ $product->minimum_quantity ?? 1 }}"
                   min="{{ $product->minimum_quantity ?? 0.01 }}"
                   max="{{ $product->maximum_quantity ?? '' }}"
                   step="0.5">
          </div>
        @endif

        @foreach($product->options as $option)
          <label class="option-label">
            {{ $option->name }}
            @if($option->is_required)<span class="required">*</span>@endif
          </label>
          <select class="option-select" data-option-id="{{ $option->id }}">
            <option value="">— choose —</option>
            @foreach($option->values as $value)
              <option value="{{ $value->id }}">
                {{ $value->name }}
                @if((float)$value->price_modifier > 0)
                  (+KSh {{ number_format($value->price_modifier, 0) }})
                @endif
              </option>
            @endforeach
          </select>
        @endforeach

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
          @if($product->pricing_type === \App\Enums\PricingType::CUSTOM)
            Request Quotation
          @else
            Add to Request
          @endif
        </button>
      </div>
    </div>
  </div>
</section>

<script>
(function () {
  'use strict';

  var productId = {{ $product->id }};
  var usesQuantity = {{ $product->pricing_type->usesQuantity() ? 'true' : 'false' }};
  var quoteUrl = '{{ route('catalogue.quote') }}';
  var addToCartUrl = '{{ route('catalogue.add', $product) }}';
  var cartUrl = '{{ route('request.cart') }}';
  var csrfToken = '{{ csrf_token() }}';

  var quantityInput = document.getElementById('quantity');
  var optionSelects = document.querySelectorAll('.option-select');
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

  function getSelectedOptionIds() {
    var ids = [];
    optionSelects.forEach(function (select) {
      if (select.value) {
        ids.push(parseInt(select.value, 10));
      }
    });
    return ids;
  }

  async function requote() {
    var payload = {
      type: 'product',
      id: productId,
      option_value_ids: getSelectedOptionIds()
    };

    if (usesQuantity) {
      payload.quantity = getQuantity();
    }

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

      var data = await res.json();

      if (!res.ok) {
        currentQuote.error = data.message || 'Could not calculate a price.';
        currentQuote.total = null;
        currentQuote.requiresQuote = false;
      } else {
        currentQuote.error = null;
        currentQuote.total = data.total;
        currentQuote.requiresQuote = data.requires_pmb_quote;
      }
    } catch (e) {
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

  async function addToCart() {
    if (currentQuote.error) return;

    var payload = {
      quantity: getQuantity() || 1,
      option_ids: getSelectedOptionIds()
    };

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

      var data = await res.json();

      if (res.ok && data.success) {
        window.location.href = cartUrl;
      } else {
        currentQuote.error = data.message || 'Could not add to cart.';
        updateUI();
      }
    } catch (e) {
      currentQuote.error = 'Could not reach the server.';
      updateUI();
    }
  }

  if (quantityInput) {
    quantityInput.addEventListener('input', function () {
      requote();
    });
  }

  optionSelects.forEach(function (select) {
    select.addEventListener('change', function () {
      requote();
    });
  });

  addToCartBtn.addEventListener('click', addToCart);

  requote();
})();
</script>

@endsection
