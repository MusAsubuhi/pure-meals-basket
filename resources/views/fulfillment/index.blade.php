@extends('layouts.app')

@section('title', 'My Fulfillments')

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <h1 class="pmb-h1">My fulfillments</h1>
    <p>Track the delivery, collection or on-site service for your orders.</p>
</div>

@forelse($fulfillments as $fulfillment)
    <div class="pmb-card">
        <div class="pmb-row">
            <div>
                <a href="{{ route('orders.show', $fulfillment->order) }}" style="font-weight:700;">{{ $fulfillment->order->reference }}</a>
                <div class="pmb-line__sub">{{ $CS::methodLabel($fulfillment->method) }}</div>
                @if($fulfillment->scheduled_at)
                    <div class="pmb-line__sub">Scheduled: {{ $fulfillment->scheduled_at->format('M j · g:i A') }}</div>
                @endif
            </div>
            <div class="pmb-flex" style="flex-direction:column;align-items:flex-end;gap:.4rem;">
                <span class="pmb-badge pmb-badge--{{ $CS::fulfillmentBadge($fulfillment->status) }}">{{ $CS::fulfillmentLabel($fulfillment->status) }}</span>
                <a class="pmb-btn pmb-btn--outline pmb-btn--sm" href="{{ route('fulfillments.show', $fulfillment) }}">View</a>
            </div>
        </div>
    </div>
@empty
    <div class="pmb-empty">
        <div class="pmb-empty__icon">🚚</div>
        <div class="pmb-empty__title">No fulfillments yet</div>
        <p>Fulfillment details will appear here once your order is underway.</p>
        <a class="pmb-btn pmb-btn--gold pmb-btn--sm" href="{{ route('catalogue.index') }}">Browse the menu</a>
    </div>
@endforelse
@endsection