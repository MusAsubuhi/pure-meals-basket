@extends('layouts.customer')

@section('title', 'My Orders')

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <h1 class="pmb-h1">My orders</h1>
    <p>Track your confirmed orders and their progress.</p>
</div>

@php
    $active = $orders->reject(fn ($o) => $o->isTerminal());
    $completed = $orders->filter(fn ($o) => $o->isTerminal());
@endphp

@if($active->isNotEmpty())
    <h2 class="pmb-h2" style="margin-bottom:.5rem;">Active</h2>
    @foreach($active as $order)
        <div class="pmb-card">
            <div class="pmb-row">
                <div>
                    <strong>{{ $order->reference }}</strong>
                    <div class="pmb-line__sub">
                        {{ $order->event_date?->format('M j, Y') ?: 'Date not set' }}{{ $order->location ? ' · '.$order->location : '' }}
                    </div>
                </div>
                <div class="pmb-ksh" style="font-weight:700;">KSh {{ number_format($order->total, 2) }}</div>
            </div>
            <div class="pmb-flex pmb-flex--center" style="margin-top:.75rem;">
                <span class="pmb-badge pmb-badge--{{ $CS::orderBadge($order->status) }}">{{ $CS::orderLabel($order->status) }}</span>
                @if($order->isPendingPayment() && $order->balance_due > 0)
                    <span class="pmb-badge pmb-badge--orange">KSh {{ number_format($order->balance_due, 2) }} due</span>
                @endif
                <a class="pmb-btn pmb-btn--outline pmb-btn--sm" href="{{ route('orders.show', $order) }}">Track order</a>
            </div>
        </div>
    @endforeach
@endif

@if($completed->isNotEmpty())
    <h2 class="pmb-h2" style="margin:1.25rem 0 .5rem;">Completed</h2>
    @foreach($completed as $order)
        <div class="pmb-card">
            <div class="pmb-row">
                <div>
                    <strong>{{ $order->reference }}</strong>
                    <div class="pmb-line__sub">{{ $order->event_date?->format('M j, Y') ?: '' }}</div>
                </div>
                <span class="pmb-badge pmb-badge--{{ $CS::orderBadge($order->status) }}">{{ $CS::orderLabel($order->status) }}</span>
            </div>
            <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('orders.show', $order) }}" style="margin-top:.75rem;">View</a>
        </div>
    @endforeach
@endif

@if($orders->isEmpty())
    <div class="pmb-empty">
        <div class="pmb-empty__icon">📦</div>
        <div class="pmb-empty__title">No orders yet</div>
        <p>Once you accept a quotation, your order will be created here.</p>
        <a class="pmb-btn pmb-btn--gold pmb-btn--sm" href="{{ route('catalogue.index') }}">Browse the menu</a>
    </div>
@endif
@endsection
