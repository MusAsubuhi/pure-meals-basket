@extends('layouts.app')

@section('title', 'Your Cart')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-8">
            <h1>Your Cart</h1>

            @forelse($items as $key => $item)
                <div class="card" style="margin-bottom: 1rem;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5>{{ $item['product']->name }}</h5>
                                <p class="text-muted mb-1">
                                    Quantity: {{ $item['quantity'] }} {{ $item['product']->unit ?? 'unit' }}
                                </p>
                                @if($item['quote']->breakdown)
                                    <small class="text-muted">
                                        @foreach($item['quote']->breakdown as $line)
                                            <div>{{ $line }}</div>
                                        @endforeach
                                    </small>
                                @endif
                            </div>
                            <div class="text-end">
                                <h5>KSh {{ number_format($item['quote']->total ?? 0, 2) }}</h5>
                                <button class="btn btn-sm btn-outline-danger"
                                        onclick="removeFromCart('{{ $key }}')">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info">
                    Your cart is empty. <a href="{{ route('catalogue.index') }}">Browse catalogue</a>
                </div>
            @endforelse
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h5>Order Summary</h5>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>KSh {{ number_format($total, 2) }}</span>
                    </div>

                    @if($requiresQuote)
                        <div class="alert alert-warning mt-3">
                            <small>
                                <i class="bi bi-exclamation-triangle"></i>
                                Some items require PMB quotation. Final price will be provided after review.
                            </small>
                        </div>
                    @endif

                    @if(count($items) > 0)
                        <a href="{{ route('request.checkout') }}" class="btn btn-primary w-100 mt-3">
                            Proceed to Checkout
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function removeFromCart(key) {
    if (confirm('Remove this item from cart?')) {
        fetch(`/request/cart/${key}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }
}
</script>
@endsection
