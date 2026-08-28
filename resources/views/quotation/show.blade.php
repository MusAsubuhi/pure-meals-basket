@extends('layouts.customer')

@section('title', $quotation->reference)

@section('content')
@php
    $CS = \App\Support\CustomerStatus::class;
    $req = $quotation->request;
@endphp

<div class="pmb-page-title">
    <div class="pmb-row">
        <div>
            <h1 class="pmb-h1">{{ $quotation->reference }}</h1>
            <p>{{ $req->event_date?->format('F j, Y') ?? 'Date not set' }}{{ $req->location ? ' · '.$req->location : '' }}</p>
        </div>
        <span class="pmb-badge pmb-badge--{{ $CS::quotationBadge($quotation->status) }}">{{ $CS::quotationLabel($quotation->status) }}</span>
    </div>
</div>

<div class="pmb-grid pmb-grid--main">
    <div>
        <div class="pmb-card">
            <h2 class="pmb-card__title">Quotation breakdown</h2>
            @foreach($quotation->items as $item)
                <div class="pmb-line">
                    <div>
                        <div class="pmb-line__name">{{ $item->name }}</div>
                        @if($item->description) <div class="pmb-line__sub">{{ $item->description }}</div> @endif
                        <div class="pmb-line__sub">{{ $item->quantity }} {{ $item->unit ?? 'unit' }}</div>
                    </div>
                    <div class="pmb-line__price pmb-ksh">KSh {{ number_format($item->subtotal, 2) }}</div>
                </div>
            @endforeach

            <div class="pmb-money" style="margin-top:1rem;">
                <div class="pmb-money__row"><span>Subtotal</span><span class="pmb-ksh">KSh {{ number_format($quotation->subtotal, 2) }}</span></div>
                @if((float)$quotation->discount > 0)
                    <div class="pmb-money__row"><span>Discount</span><span class="pmb-ksh">− KSh {{ number_format($quotation->discount, 2) }}</span></div>
                @endif
                <div class="pmb-money__row pmb-money__row--total"><span>Total</span><span class="pmb-ksh">KSh {{ number_format($quotation->total, 2) }}</span></div>
            </div>

            @if($quotation->notes)
                <p style="color:var(--ink-muted);font-size:.9rem;margin-top:1rem;">{{ $quotation->notes }}</p>
            @endif
        </div>

        @if($quotation->canBeAccepted())
            {{-- Confirm your booking --}}
            <div class="pmb-card" style="border:2px solid var(--pmb-green);">
                <h2 class="pmb-card__title">Confirm your booking</h2>
                <div class="pmb-money">
                    <div class="pmb-money__row"><span>Booking amount</span><span class="pmb-ksh">KSh {{ number_format($quotation->total, 2) }}</span></div>
                    <div class="pmb-money__row"><span>Event</span><span>{{ $req->event_date?->format('F d, Y') ?: '—' }}</span></div>
                    <div class="pmb-money__row"><span>Location</span><span>{{ $req->location ?? '—' }}</span></div>
                </div>

                <form method="POST" action="{{ route('quotations.accept', $quotation) }}" style="margin-top:1rem;" onsubmit="return confirm('Accept this quotation and create your order?');">
                    @csrf
                    <button class="pmb-btn pmb-btn--gold pmb-btn--block" type="submit">Accept quotation &amp; continue</button>
                </form>

                <form method="POST" action="{{ route('quotations.decline', $quotation) }}" style="margin-top:.5rem;" onsubmit="return confirm('Decline this quotation?');">
                    @csrf
                    <button class="pmb-btn pmb-btn--danger pmb-btn--block" type="submit">Decline</button>
                </form>
            </div>

            {{-- Request changes --}}
            <div class="pmb-card">
                <h2 class="pmb-card__title">Request changes</h2>
                <p style="color:var(--ink-muted);font-size:.9rem;margin:0 0 .75rem;">Tell us what to adjust and we'll prepare a revised quotation.</p>
                <form method="POST" action="{{ route('quotations.changes', $quotation) }}" class="pmb-form">
                    @csrf
                    <div class="pmb-field">
                        <textarea class="pmb-textarea" name="change_reason" placeholder="e.g. reduce to 100 guests, vegetarian menu..." required></textarea>
                        @error('change_reason') <span class="pmb-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <button class="pmb-btn pmb-btn--ghost" type="submit">Request changes</button>
                    </div>
                </form>
            </div>
        @elseif($quotation->isAccepted() && $quotation->order)
            <div class="pmb-card" style="border:2px solid var(--pmb-green);">
                <h2 class="pmb-card__title">Quotation accepted ✓</h2>
                <p style="margin:0 0 1rem;">Your order has been created. Head to your order to make a payment.</p>
                <a class="pmb-btn pmb-btn--gold" href="{{ route('orders.show', $quotation->order) }}">View order →</a>
            </div>
        @endif
    </div>

    <div>
        <div class="pmb-card">
            <h2 class="pmb-card__title">Validity</h2>
            @if($quotation->valid_until)
                <p style="margin:0;">This quotation is valid until <strong>{{ $quotation->valid_until->format('M j, Y') }}</strong>.</p>
            @else
                <p style="margin:0;color:var(--ink-muted);">No expiry date set.</p>
            @endif
        </div>

        <div class="pmb-card">
            <h2 class="pmb-card__title">Your request</h2>
            <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('requests.show', $req) }}">View request →</a>
        </div>
    </div>
</div>
@endsection
