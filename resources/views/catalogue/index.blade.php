@extends('catalogue.layout')

@section('title', 'Catalogue')

@section('content')
<div class="container">
    <h1>Our Catalogue</h1>
    <p class="subtitle">Everything PMB offers — cakes, juices, celebration foods and catering.</p>

    @forelse($categories as $category)
        <div class="card" style="margin-bottom:1rem;">
            <div class="card-body" style="display:flex;justify-content:space-between;align-items:center;">
                <div>
                    <strong>{{ $category->name }}</strong><br>
                    <span class="subtitle" style="margin:0;">{{ $category->description }}</span>
                </div>
                <div style="text-align:right;">
                    <span class="badge">{{ $category->products_count }} items</span><br>
                    <a class="btn-gold" style="margin-top:.5rem;"
                       href="{{ route('catalogue.category', $category) }}">Browse</a>
                </div>
            </div>
        </div>
    @empty
        <p>The catalogue is being prepared. Please check back soon.</p>
    @endforelse
</div>
@endsection
