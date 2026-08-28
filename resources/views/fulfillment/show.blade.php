@extends('layouts.app')

@section('title', 'Fulfillment · '.$fulfillment->order->reference)

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <div class="pmb-row">
        <div>
            <h1 class="pmb-h1">Fulfillment — {{ $fulfillment->order->reference }}</h1>
            <p>{{ $CS::methodLabel($fulfillment->method) }}</p>
        </div>
        <span class="pmb-badge pmb-badge--{{ $CS::fulfillmentBadge($fulfillment->status) }}">{{ $CS::fulfillmentLabel($fulfillment->status) }}</span>
    </div>
</div>

@if($fulfillment->isOutForDelivery() || $fulfillment->isPreparing())
    <div class="pmb-action {{ $fulfillment->isOutForDelivery() ? 'pmb-action--blue' : 'pmb-action--gold' }}">
        <div class="pmb-action__icon">{{ $fulfillment->isOutForDelivery() ? '🚚' : '👨‍🍳' }}</div>
        <div class="pmb-action__body">
            <div class="pmb-action__title">{{ $fulfillment->isOutForDelivery() ? 'Your order is on the way' : 'Your order is being prepared' }}</div>
            @if($fulfillment->dispatched_at)
                <div class="pmb-action__detail">Dispatched at {{ $fulfillment->dispatched_at->format('g:i A') }}</div>
            @endif
        </div>
    </div>
@endif

<div class="pmb-grid pmb-grid--main">
    <div class="pmb-card">
        <h2 class="pmb-card__title">Details</h2>
        <div class="pmb-money">
            <div class="pmb-money__row"><span>Order</span><span><a href="{{ route('orders.show', $fulfillment->order) }}">{{ $fulfillment->order->reference }}</a></span></div>
            <div class="pmb-money__row"><span>Method</span><span>{{ $CS::methodLabel($fulfillment->method) }}</span></div>
            <div class="pmb-money__row"><span>Scheduled</span><span>{{ $fulfillment->scheduled_at?->format('F j · g:i A') ?? '—' }}</span></div>
            @if($fulfillment->delivery_address)
                <div class="pmb-money__row"><span>Delivery address</span><span>{{ $fulfillment->delivery_address }}</span></div>
            @endif
            @if($fulfillment->recipient_name)
                <div class="pmb-money__row"><span>Recipient</span><span>{{ $fulfillment->recipient_name }}</span></div>
            @endif
        </div>
    </div>

    <div class="pmb-card">
        <h2 class="pmb-card__title">Timeline</h2>
        @if($fulfillment->events->isNotEmpty())
            <div class="pmb-timeline">
                @foreach($fulfillment->events as $event)
                    <div class="pmb-tl-item">
                        <div class="pmb-tl-dot is-line"></div>
                        <div class="pmb-tl__body">
                            <div class="pmb-tl__title">{{ $event->event_type }}</div>
                            <div class="pmb-tl__detail">{{ $event->description ?? '—' }}</div>
                            <div class="pmb-tl__meta">{{ $event->created_at->format('M j · g:i A') }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p style="color:var(--ink-muted);margin:0;">No events recorded.</p>
        @endif
    </div>
</div>
@endsection