@extends('layouts.customer')

@section('title', $payment->reference)

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <div class="pmb-row">
        <div>
            <h1 class="pmb-h1">{{ $payment->reference }}</h1>
            <p>
                <a href="{{ route('orders.show', $order) }}">{{ $order->reference }}</a>
                &middot; <a href="{{ route('payments.index', $order) }}">Payments</a>
            </p>
        </div>
        <span class="pmb-badge pmb-badge--{{ $CS::paymentBadge($payment->status) }}">{{ $CS::paymentLabel($payment->status) }}</span>
    </div>
</div>

@if($payment->isProcessing())
    <div class="pmb-action pmb-action--blue">
        <div class="pmb-action__icon">⏳</div>
        <div class="pmb-action__body">
            <div class="pmb-action__title">Payment processing</div>
            <div class="pmb-action__detail">Check your phone — we've sent an M-Pesa prompt. Waiting for confirmation...</div>
            <div class="pmb-action__cta">
                <a class="pmb-btn pmb-btn--outline pmb-btn--sm" href="{{ route('payments.status', ['order' => $order, 'payment' => $payment]) }}">Check status</a>
            </div>
        </div>
    </div>
@elseif($payment->isSuccess())
    <div class="pmb-action pmb-action--green">
        <div class="pmb-action__icon">✓</div>
        <div class="pmb-action__body">
            <div class="pmb-action__title">Payment received</div>
            <div class="pmb-action__detail">KSh {{ number_format($payment->amount, 2) }} · {{ $order->reference }}</div>
        </div>
    </div>
@elseif($order->isPendingPayment() && $payment->method->value === 'CASH')
    <div class="pmb-action pmb-action--gold">
        <div class="pmb-action__icon">💵</div>
        <div class="pmb-action__body">
            <div class="pmb-action__title">Cash payment awaiting confirmation</div>
            <div class="pmb-action__detail">Your order will be confirmed once PMB confirms receipt.</div>
        </div>
    </div>
@endif

<div class="pmb-grid pmb-grid--main">
    <div class="pmb-card">
        <h2 class="pmb-card__title">Payment details</h2>
        <div class="pmb-money">
            <div class="pmb-money__row"><span>Amount</span><span class="pmb-ksh">KSh {{ number_format($payment->amount, 2) }}</span></div>
            <div class="pmb-money__row"><span>Method</span><span>{{ $payment->method->label() }}</span></div>
            <div class="pmb-money__row"><span>Provider</span><span>{{ $payment->provider->label() }}</span></div>
            <div class="pmb-money__row"><span>Paid at</span><span>{{ $payment->paid_at?->format('M j, Y g:i A') ?? '—' }}</span></div>
            @if($payment->provider_reference)
                <div class="pmb-money__row"><span>Provider ref</span><span>{{ $payment->provider_reference }}</span></div>
            @endif
        </div>
    </div>

    <div class="pmb-card">
        <h2 class="pmb-card__title">Order balance</h2>
        <div class="pmb-money">
            <div class="pmb-money__row"><span>Order total</span><span class="pmb-ksh">KSh {{ number_format($order->total, 2) }}</span></div>
            <div class="pmb-money__row pmb-money__row--good"><span>Paid</span><span class="pmb-ksh">KSh {{ number_format($order->amount_paid, 2) }}</span></div>
            <div class="pmb-money__row pmb-money__row--total"><span>Balance</span><span class="pmb-ksh">KSh {{ number_format($order->balance_due, 2) }}</span></div>
        </div>
    </div>
</div>

<div class="pmb-card">
    <h2 class="pmb-card__title">Events</h2>
    @if($payment->events->isNotEmpty())
        <div class="pmb-timeline">
            @foreach($payment->events as $event)
                <div class="pmb-tl-item">
                    <div class="pmb-tl-dot is-line"></div>
                    <div class="pmb-tl__body">
                        <div class="pmb-tl__title">{{ $CS::paymentLabel($payment->status) === 'Paid' ? 'Payment' : $event->event_type }}</div>
                        <div class="pmb-tl__detail">{{ $event->data['description'] ?? $event->event_type }}</div>
                        <div class="pmb-tl__meta">{{ $event->created_at->format('M j · g:i A') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <p style="color:var(--ink-muted);margin:0;">No events recorded.</p>
    @endif
</div>
@endsection