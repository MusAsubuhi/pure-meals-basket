@extends('layouts.customer')

@section('title', 'Payments · '.$order->reference)

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <h1 class="pmb-h1">Payments — {{ $order->reference }}</h1>
    <p>
        Order total <strong class="pmb-ksh">KSh {{ number_format($order->total, 2) }}</strong>
        · Balance due <strong class="pmb-ksh">KSh {{ number_format($order->balance_due, 2) }}</strong>
    </p>
</div>

@if($order->canBeConfirmed() && $order->balance_due > 0)
    <div class="pmb-card">
        <h2 class="pmb-card__title">How would you like to pay?</h2>

        <div class="pmb-card pmb-card--no-pad" style="border:1px solid var(--border);margin-bottom:1rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border);">
                <div>
                    <strong>📱 M-Pesa</strong>
                    <div class="pmb-line__sub">Pay securely via an STK prompt on your phone.</div>
                </div>
                <span class="pmb-badge pmb-badge--neutral">Min: KSh {{ number_format($order->payment_required, 2) }}</span>
            </div>
            <div style="padding:1rem 1.25rem;">
                <form method="POST" action="{{ route('payments.mpesa', $order) }}" class="pmb-form">
                    @csrf
                    <div class="pmb-field">
                        <label class="pmb-label" for="phone">M-Pesa phone number</label>
                        <input class="pmb-input" id="phone" name="phone" value="{{ old('phone') }}" placeholder="0712345678" required>
                        @error('phone') <span class="pmb-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="pmb-field">
                        <label class="pmb-label" for="amount">Amount to pay (KES)</label>
                        <input class="pmb-input" id="amount" name="amount" type="number" step="0.01" min="{{ $order->payment_required }}" max="{{ $order->balance_due }}" value="{{ old('amount', $order->payment_required) }}" required>
                        <small style="color:var(--ink-muted);">Minimum KSh {{ number_format($order->payment_required, 2) }} (70% of total).</small>
                        @error('amount') <span class="pmb-error">{{ $message }}</span> @enderror
                    </div>
                    <button class="pmb-btn pmb-btn--gold" type="submit" style="align-self:flex-start;">Send STK prompt</button>
                </form>
            </div>
        </div>

        <div class="pmb-card pmb-card--no-pad" style="border:1px solid var(--border);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:1rem 1.25rem;border-bottom:1px solid var(--border);">
                <div>
                    <strong>💵 Cash</strong>
                    <div class="pmb-line__sub">Pay PMB directly. Your order confirms once PMB confirms receipt.</div>
                </div>
                <span class="pmb-badge pmb-badge--neutral">Min: KSh {{ number_format($order->payment_required, 2) }}</span>
            </div>
            <div style="padding:1rem 1.25rem;">
                <form method="POST" action="{{ route('payments.cash', $order) }}" class="pmb-form">
                    @csrf
                    <div class="pmb-field">
                        <label class="pmb-label" for="amount">Amount to pay (KES)</label>
                        <input class="pmb-input" id="amount" name="amount" type="number" step="0.01" min="{{ $order->payment_required }}" max="{{ $order->balance_due }}" value="{{ old('amount', $order->payment_required) }}" required>
                        <small style="color:var(--ink-muted);">Minimum KSh {{ number_format($order->payment_required, 2) }} (70% of total).</small>
                        @error('amount') <span class="pmb-error">{{ $message }}</span> @enderror
                    </div>
                    <button class="pmb-btn pmb-btn--outline" type="submit">Pay with cash</button>
                </form>
            </div>
        </div>
    </div>
@elseif($order->balance_due <= 0)
    <div class="pmb-action pmb-action--green">
        <div class="pmb-action__icon">✓</div>
        <div class="pmb-action__body">
            <div class="pmb-action__title">Order fully paid</div>
            <div class="pmb-action__detail">Thank you — nothing owing on this order.</div>
        </div>
    </div>
@endif

<div class="pmb-card">
    <h2 class="pmb-card__title">Payment history</h2>
    @if($payments->isNotEmpty())
        <table class="pmb-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Method</th>
                    <th class="num">Amount</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                    <tr>
                        <td><a href="{{ route('payments.show', ['order' => $order, 'payment' => $payment]) }}">{{ $payment->reference }}</a></td>
                        <td>{{ $payment->method->label() }}</td>
                        <td class="num pmb-ksh">KSh {{ number_format($payment->amount, 2) }}</td>
                        <td>
                            <span class="pmb-badge pmb-badge--{{ $CS::paymentBadge($payment->status) }}">{{ $CS::paymentLabel($payment->status) }}</span>
                        </td>
                        <td>{{ $payment->paid_at?->format('M j, Y') ?? $payment->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p style="color:var(--ink-muted);margin:0;">No payments recorded yet.</p>
    @endif
</div>
@endsection
