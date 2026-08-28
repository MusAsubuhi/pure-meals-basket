@extends('layouts.customer')

@section('title', $order->reference)

@section('content')
@php
    $CS = \App\Support\CustomerStatus::class;
    $tl = app(\App\Services\CustomerPortal\UnifiedTimeline::class);
    $fulfillment = $order->fulfillment;
    $journey = $tl->journey($order->request);
@endphp

<div class="pmb-page-title">
    <div class="pmb-row">
        <div>
            <h1 class="pmb-h1">{{ $order->reference }}</h1>
            <p>
                {{ $order->event_date?->format('F j, Y') ?: 'Date not set' }}
                @if($order->event_time) at {{ $order->event_time->format('g:i A') }} @endif
                @if($order->location) · {{ $order->location }} @endif
            </p>
        </div>
        <span class="pmb-badge pmb-badge--{{ $CS::orderBadge($order->status) }}">{{ $CS::orderLabel($order->status) }}</span>
    </div>
</div>

<div class="pmb-grid pmb-grid--main">
    <div>
        {{-- Journey stepper --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Your order</h2>
            @include('customer.partials.journey', ['stages' => $journey])
        </div>

        {{-- Fulfillment details --}}
        @if($fulfillment)
            <div class="pmb-card">
                <h2 class="pmb-card__title">{{ $CS::methodLabel($fulfillment->method) }}</h2>
                <div class="pmb-money">
                    @if($fulfillment->scheduled_at)
                        <div class="pmb-money__row"><span>Scheduled</span><span>{{ $fulfillment->scheduled_at->format('F j · g:i A') }}</span></div>
                    @endif
                    @if($fulfillment->delivery_address)
                        <div class="pmb-money__row"><span>Delivery to</span><span>{{ $fulfillment->delivery_address }}</span></div>
                    @endif
                    @if($fulfillment->recipient_name)
                        <div class="pmb-money__row"><span>Recipient</span><span>{{ $fulfillment->recipient_name }}</span></div>
                    @endif
                    @if($fulfillment->collection_notes)
                        <div class="pmb-money__row"><span>Collection notes</span><span>{{ $fulfillment->collection_notes }}</span></div>
                    @endif
                    @if($fulfillment->dispatched_at)
                        <div class="pmb-money__row"><span>Dispatched</span><span>{{ $fulfillment->dispatched_at->format('g:i A') }}</span></div>
                    @endif
                    @if($fulfillment->service_started_at)
                        <div class="pmb-money__row"><span>Service started</span><span>{{ $fulfillment->service_started_at->format('g:i A') }}</span></div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Items --}}
        @if($order->items->isNotEmpty())
            <div class="pmb-card">
                <h2 class="pmb-card__title">Items</h2>
                @foreach($order->items as $item)
                    <div class="pmb-line">
                        <div>
                            <div class="pmb-line__name">{{ $item->name }}</div>
                            <div class="pmb-line__sub">{{ $item->quantity }} {{ $item->unit ?? 'unit' }}</div>
                        </div>
                        <div class="pmb-line__price pmb-ksh">KSh {{ number_format($item->subtotal, 2) }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        {{-- Financial summary --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Payment</h2>
            <div class="pmb-money">
                <div class="pmb-money__row"><span>Order total</span><span class="pmb-ksh">KSh {{ number_format($order->total, 2) }}</span></div>
                <div class="pmb-money__row pmb-money__row--good"><span>Paid</span><span class="pmb-ksh">KSh {{ number_format($order->amount_paid, 2) }}</span></div>
                <div class="pmb-money__row pmb-money__row--total"><span>Balance</span><span class="pmb-ksh">KSh {{ number_format($order->balance_due, 2) }}</span></div>
            </div>

            @if($order->isPendingPayment() && $order->balance_due > 0)
                <a class="pmb-btn pmb-btn--gold pmb-btn--block" href="{{ route('payments.index', $order) }}" style="margin-top:1rem;">Make payment</a>
                <p style="font-size:.85rem;color:var(--ink-muted);margin:.75rem 0 0;text-align:center;">Your order will be confirmed once payment is received.</p>
            @endif

            @if($order->canBeCancelled())
                <form method="POST" action="{{ route('orders.cancel', $order) }}" style="margin-top:.75rem;" onsubmit="return confirm('Cancel this order?');">
                    @csrf
                    <button class="pmb-btn pmb-btn--danger pmb-btn--block" type="submit">Cancel order</button>
                </form>
            @endif
        </div>

        <div class="pmb-card">
            <h2 class="pmb-card__title">Request &amp; quotation</h2>
            <div class="pmb-flex">
                <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('requests.show', $order->request) }}">View request</a>
                @if($order->quotation)
                    <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('quotations.show', $order->quotation) }}">View quotation</a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
