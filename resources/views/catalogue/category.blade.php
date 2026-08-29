@extends('layouts.app')

@section('title', $category->name . ' — Pure Meals Basket')

@section('content')

@push('styles')
<style>
.catalogue-category-hero {
  position: relative;
  min-height: 35vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: var(--pmb-brown);
}

.catalogue-category-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(44,26,14,0.55) 0%, rgba(44,26,14,0.85) 100%);
}

.catalogue-category-hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: var(--pmb-white);
  padding: 5rem 1.25rem 2.5rem;
  max-width: 720px;
}

.catalogue-category-hero-content h1 {
  color: var(--pmb-white);
  margin-bottom: 0.75rem;
  text-shadow: 0 2px 16px rgba(0,0,0,0.25);
}

.catalogue-category-hero-content p {
  opacity: 0.9;
  font-weight: 300;
}

.catalogue-category-back {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--pmb-gold);
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
  margin-bottom: 1.5rem;
}

.catalogue-category-back:hover {
  color: var(--pmb-gold-light);
}

.catalogue-products-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.5rem;
}

.catalogue-product-card {
  background: var(--pmb-white);
  border-radius: var(--radius-card);
  overflow: hidden;
  box-shadow: var(--shadow-soft);
  display: block;
  text-decoration: none;
  color: inherit;
  transition: transform var(--transition-base), box-shadow var(--transition-base);
  border-top: 4px solid transparent;
}

.catalogue-product-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-card);
  border-top-color: var(--pmb-gold);
}

.catalogue-product-card-body {
  padding: 1.25rem;
}

.catalogue-product-card-body h3 {
  margin-bottom: 0.4rem;
  font-size: 1.1rem;
}

.catalogue-product-price {
  color: var(--pmb-gold);
  font-weight: 700;
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.catalogue-product-desc {
  font-weight: 300;
  font-size: 0.9rem;
  color: rgba(44,26,14,0.7);
  margin-bottom: 0.75rem;
}

.catalogue-product-badge {
  display: inline-block;
  font-size: 0.72rem;
  padding: 0.2rem 0.6rem;
  border-radius: 999px;
  background: #F1E9D2;
  color: var(--gold, #8A6D1D);
  font-weight: 600;
}

.catalogue-product-badge.unavailable {
  background: #FBECE6;
  color: #B3401E;
}

.catalogue-empty {
  text-align: center;
  padding: 3rem;
  color: rgba(44,26,14,0.6);
}

@media (max-width: 640px) {
  .catalogue-products-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="catalogue-category-hero">
  <div class="catalogue-category-hero-overlay"></div>
  <div class="catalogue-category-hero-content container">
    <a href="{{ route('catalogue.index') }}" class="catalogue-category-back">&larr; All categories</a>
    <h1>{{ $category->name }}</h1>
    <p>{{ $category->description }}</p>
  </div>
</div>

<section class="catalogue-section">
  <div class="container">
    <div class="catalogue-products-grid">
      @forelse($products as $product)
        <a href="{{ route('catalogue.show', $product) }}" class="catalogue-product-card">
          <div class="catalogue-product-card-body">
            <h3>{{ $product->name }}</h3>
            <div class="catalogue-product-price">
              @if ($product->pricing_type->value === 'custom')
                Request a quote
              @elseif ($product->pricing_type->value === 'fixed')
                KSh {{ number_format($product->base_price, 0) }}
              @else
                KSh {{ number_format($product->base_price, 0) }} / {{ $product->unit }}
              @endif
            </div>
            @if ($product->short_description)
              <p class="catalogue-product-desc">{{ \Illuminate\Support\Str::limit($product->short_description, 100) }}</p>
            @endif
            <span class="catalogue-product-badge {{ $product->is_available ? '' : 'unavailable' }}">
              {{ $product->is_available ? 'Available' : 'Currently unavailable' }}
            </span>
          </div>
        </a>
      @empty
        <p class="catalogue-empty">Nothing available in this category right now.</p>
      @endforelse
    </div>
  </div>
</section>

@endsection
