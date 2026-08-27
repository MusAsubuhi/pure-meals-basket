@extends('catalogue.layout')

@section('title', $category->name)

@section('content')
<div class="container">
    <a class="back" href="{{ route('catalogue.index') }}">← All categories</a>
    <h1>{{ $category->name }}</h1>

    <div class="grid">
        @forelse($products as $product)
            <a class="card" href="{{ route('catalogue.show', $product) }}">
                <div class="card-body">
                    <strong>{{ $product->name }}</strong><br>
                    <span class="price-tag">
                        @if ($product->pricing_type->value === 'custom')
                            Request a quote
                        @elseif ($product->pricing_type->value === 'fixed')
                            KSh {{ number_format($product->base_price, 0) }}
                        @else
                            KSh {{ number_format($product->base_price, 0) }} / {{ $product->unit }}
                        @endif
                    </span><br>
                    @unless ($product->is_available)
                        <span class="unavailable">Currently unavailable</span><br>
                    @endunless
                    @if ($product->short_description)
                        <small>{{ \Illuminate\Support\Str::limit($product->short_description, 80) }}</small>
                    @endif
                </div>
            </a>
        @empty
            <p>Nothing available in this category right now.</p>
        @endforelse
    </div>
</div>
@endsection
