@extends('layouts.app')

@section('title', 'Catalogue — Pure Meals Basket')

@section('content')

@push('styles')
<style>
.catalogue-hero {
  position: relative;
  min-height: 40vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  background: var(--pmb-brown);
}

.catalogue-hero-bg {
  position: absolute;
  inset: 0;
  object-fit: cover;
  opacity: 0.35;
}

.catalogue-hero-overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(44,26,14,0.55) 0%, rgba(44,26,14,0.85) 100%);
}

.catalogue-hero-content {
  position: relative;
  z-index: 2;
  text-align: center;
  color: var(--pmb-white);
  padding: 6rem 1.25rem 3rem;
  max-width: 720px;
}

.catalogue-hero-content h1 {
  color: var(--pmb-white);
  margin-bottom: 1rem;
  text-shadow: 0 2px 16px rgba(0,0,0,0.25);
}

.catalogue-hero-content p {
  opacity: 0.95;
  font-weight: 300;
  font-size: 1.05rem;
  line-height: 1.7;
  margin: 0 auto;
  max-width: 560px;
}

.catalogue-section {
  padding: 4rem 0;
}

.catalogue-section-title {
  text-align: center;
  margin-bottom: 0.75rem;
}

.catalogue-section-subtitle {
  text-align: center;
  color: rgba(44,26,14,0.7);
  margin-bottom: 3rem;
  font-weight: 300;
}

.catalogue-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
  gap: 1.5rem;
}

.catalogue-card {
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

.catalogue-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-card);
  border-top-color: var(--pmb-gold);
}

.catalogue-card-image {
  width: 100%;
  height: 180px;
  object-fit: cover;
}

.catalogue-card-body {
  padding: 1.25rem;
}

.catalogue-card-body h3 {
  margin-bottom: 0.5rem;
  font-size: 1.15rem;
}

.catalogue-card-body p {
  font-weight: 300;
  font-size: 0.9rem;
  color: rgba(44,26,14,0.7);
  margin: 0;
}

.catalogue-card-meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
}

.catalogue-card-count {
  font-size: 0.8rem;
  color: rgba(44,26,14,0.6);
  font-weight: 600;
}

.catalogue-card-action {
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--pmb-gold);
}

.catalogue-card:hover .catalogue-card-action {
  color: var(--pmb-gold-light);
}

@media (max-width: 640px) {
  .catalogue-hero {
    min-height: 30vh;
  }
  .catalogue-hero-content {
    padding-top: 5rem;
  }
  .catalogue-grid {
    grid-template-columns: 1fr;
  }
}
</style>

<div class="catalogue-hero">
  <img class="catalogue-hero-bg media-photo" src="{{ asset('assets/images/hero-bg.webp') }}" alt="" loading="eager">
  <div class="catalogue-hero-overlay"></div>
  <div class="catalogue-hero-content container">
    <h1>Our Catalogue</h1>
    <p>From freshly prepared meals to celebration cakes and natural juices — explore what Pure Meals Basket has for your next occasion.</p>
  </div>
</div>

<section class="catalogue-section">
  <div class="container">
    <h2 class="catalogue-section-title">What We Offer</h2>
    <p class="catalogue-section-subtitle">Choose a category to see available products, pricing, and how to order.</p>

    <div class="catalogue-grid">
      @forelse($categories as $category)
        <a href="{{ route('catalogue.category', $category) }}" class="catalogue-card">
          @if($category->image_path)
            <img class="catalogue-card-image media-photo" src="{{ asset('storage/' . ltrim($category->image_path, '/')) }}" alt="{{ $category->name }}" loading="lazy">
          @endif
          <div class="catalogue-card-body">
            <h3>{{ $category->name }}</h3>
            <p>{{ $category->description }}</p>
            <div class="catalogue-card-meta">
              <span class="catalogue-card-count">{{ $category->products_count }} item{{ $category->products_count === 1 ? '' : 's' }}</span>
              <span class="catalogue-card-action">Browse &rarr;</span>
            </div>
          </div>
        </a>
      @empty
        <p style="text-align:center;grid-column:1/-1;color:rgba(44,26,14,0.6);">The catalogue is being prepared. Please check back soon.</p>
      @endforelse
    </div>
  </div>
</section>

@php
    $services = \App\Models\Service::active()->orderBy('sort_order')->get();
@endphp

@if($services->isNotEmpty())
<section class="catalogue-section" id="services">
  <div class="container">
    <h2 class="catalogue-section-title">Custom Services</h2>
    <p class="catalogue-section-subtitle">Bespoke services tailored to your occasion.</p>

    <div class="catalogue-grid">
      @foreach($services as $service)
        <a href="{{ route('catalogue.services.show', $service) }}" class="catalogue-card">
          <div class="catalogue-card-body">
            <h3>{{ $service->name }}</h3>
            <p>{{ $service->short_description ?? $service->description }}</p>
            <div class="catalogue-card-meta">
              <span class="catalogue-card-count">{{ $service->pricing_type->label() }}</span>
              <span class="catalogue-card-action">View &rarr;</span>
            </div>
          </div>
        </a>
      @endforeach
    </div>
  </div>
</section>
@endif

@endsection
