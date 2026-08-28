@extends('layouts.customer')

@section('title', 'My Payments')

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <h1 class="pmb-h1">Payments</h1>
    <p>Your payment history across all orders.</p>
</div>

@forelse($payments as $orderRef => $group)
    <div class="pmb-card">
        <h2 class="pmb-card__title">{{ $orderRef }}</h2>
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
                @foreach($group as $payment)
                    <tr>
                        <td>{{ $payment->reference }}</td>
                        <td>{{ $payment->method->label() }}</td>
                        <td class="num pmb-ksh">KSh {{ number_format($payment->amount, 2) }}</td>
                        <td>
                            <span class="pmb-badge pmb-badge--{{ $CS::paymentBadge($payment->status) }}">
                                {{ $CS::paymentLabel($payment->status) }}
                            </span>
                        </td>
                        <td>{{ $payment->paid_at?->format('M j, Y') ?? $payment->created_at->format('M j, Y') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if($group->first()->order)
            <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('orders.show', $group->first()->order) }}" style="margin-top:.75rem;">View order →</a>
        @endif
    </div>
@empty
    <div class="pmb-empty">
        <div class="pmb-empty__icon">💳</div>
        <div class="pmb-empty__title">No payments yet</div>
        <p>Your payments will show up here once you make your first order.</p>
        <a class="pmb-btn pmb-btn--gold pmb-btn--sm" href="{{ route('catalogue.index') }}">Browse the menu</a>
    </div>
@endforelse
@endsection
