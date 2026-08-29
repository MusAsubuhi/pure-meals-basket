@extends('layouts.customer')

@section('title', 'Your Request')

@section('content')
@php
    $csr = csrf_token();
@endphp

<div class="pmb-page-title">
    <h1 class="pmb-h1">Your request</h1>
    <p>Review the items you'd like to order before telling us about your event.</p>
</div>

<div class="pmb-grid pmb-grid--main">
    <div>
        @forelse($items as $key => $item)
            <div class="pmb-card">
                <div class="pmb-row">
                    <div>
                        <div class="pmb-line__name">{{ $item['product']->name }}</div>
                        <div class="pmb-line__sub">Quantity: {{ $item['quantity'] }} {{ $item['product']->unit ?? 'unit' }}</div>
                        @if($item['quote']->breakdown)
                            @foreach($item['quote']->breakdown as $line)
                                <div class="pmb-line__sub">{{ $line }}</div>
                            @endforeach
                        @endif
                    </div>
                    <div style="text-align:right;">
                        <div class="pmb-line__price pmb-ksh">KSh {{ number_format($item['quote']->total ?? 0, 2) }}</div>
                        <button class="pmb-btn pmb-btn--danger pmb-btn--sm" style="margin-top:.5rem;" onclick="removeFromCart('{{ $key }}')">Remove</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="pmb-empty">
                <div class="pmb-empty__icon">🛒</div>
                <div class="pmb-empty__title">Your request is empty</div>
                <p>Add a few things from the menu to get started.</p>
                <a class="pmb-btn pmb-btn--gold pmb-btn--sm" href="{{ route('catalogue.index') }}">Browse the menu</a>
            </div>
        @endforelse
    </div>

    <div>
        <div class="pmb-card">
            <h2 class="pmb-card__title">Estimated total</h2>
            <div class="pmb-money">
                <div class="pmb-money__row pmb-money__row--total"><span>Total</span><span class="pmb-ksh">KSh {{ number_format($total, 2) }}</span></div>
            </div>
            @if($requiresQuote)
                <div class="pmb-badge pmb-badge--purple" style="margin-top:.75rem;">Some items need a PMB quotation</div>
                <p style="font-size:.85rem;color:var(--ink-muted);margin:.5rem 0 0;">The final price will be confirmed once PMB reviews your request.</p>
            @endif
            @if(count($items) > 0)
                <a class="pmb-btn pmb-btn--gold pmb-btn--block" href="{{ route('requests.checkout') }}" style="margin-top:1rem;">Continue</a>
            @endif
        </div>
    </div>
</div>

<script>
function removeFromCart(key) {
    if (confirm('Remove this item from your request?')) {
        fetch('/request/cart/' + key, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ $csr }}',
                'Accept': 'application/json',
            },
        })
        .then(response => response.json())
        .then(data => { if (data.success) { window.location.reload(); } });
    }
}
</script>
@endsection
