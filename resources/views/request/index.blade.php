@extends('layouts.customer')

@section('title', 'My Requests')

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <h1 class="pmb-h1">My requests</h1>
    <p>Track every request you've sent to PMB, from submission through to completion.</p>
</div>

@forelse($requests as $request)
    <div class="pmb-card">
        <div class="pmb-row">
            <div>
                <strong>{{ $request->reference }}</strong>
                <div class="pmb-line__sub">
                    {{ $request->event_date?->format('F j, Y') ?? 'Date not set' }}
                    @if($request->event_time) at {{ $request->event_time->format('g:i A') }} @endif
                </div>
                <div class="pmb-line__sub">Location: {{ $request->location ?? 'Not specified' }}</div>
            </div>
            <div class="pmb-flex" style="flex-direction:column;align-items:flex-end;gap:.4rem;">
                <span class="pmb-badge pmb-badge--{{ $CS::requestBadge($request->status) }}">{{ $CS::requestLabel($request->status) }}</span>
                <a class="pmb-btn pmb-btn--outline pmb-btn--sm" href="{{ route('requests.show', $request) }}">View request</a>
            </div>
        </div>
    </div>
@empty
    <div class="pmb-empty">
        <div class="pmb-empty__icon">📨</div>
        <div class="pmb-empty__title">No requests yet</div>
        <p>Start by browsing the menu and adding items to a request.</p>
        <a class="pmb-btn pmb-btn--gold pmb-btn--sm" href="{{ route('catalogue.index') }}">Browse the menu</a>
    </div>
@endforelse
@endsection
