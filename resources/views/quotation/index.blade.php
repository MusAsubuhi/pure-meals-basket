@extends('layouts.customer')

@section('title', 'My Quotations')

@section('content')
@php $CS = \App\Support\CustomerStatus::class; @endphp

<div class="pmb-page-title">
    <h1 class="pmb-h1">My quotations</h1>
    <p>Quotations are valid within their stated window. Review them before they expire.</p>
</div>

@php
    $awaiting = $quotations->filter(fn ($q) => $q->isSent() && ! $q->hasExpired());
    $accepted = $quotations->filter(fn ($q) => $q->isAccepted());
    $other = $quotations->reject(fn ($q) => $awaiting->contains($q) || $accepted->contains($q));
@endphp

@foreach([
    'Awaiting your response' => $awaiting,
    'Accepted' => $accepted,
    'Others' => $other,
] as $heading => $list)
    @if($list->isNotEmpty())
        <h2 class="pmb-h2" style="margin:1.25rem 0 .5rem;">{{ $heading }}</h2>
        @foreach($list as $quotation)
            <div class="pmb-card">
                <div class="pmb-row">
                    <div>
                        <strong>{{ $quotation->reference }}</strong>
                        <div class="pmb-line__sub">
                            {{ $quotation->request->event_date?->format('M j, Y') ?? 'Date not set' }}
                            @if($quotation->request->location) · {{ $quotation->request->location }} @endif
                        </div>
                    </div>
                    <span class="pmb-ksh" style="font-weight:700;">KSh {{ number_format($quotation->total, 2) }}</span>
                </div>
                <div class="pmb-flex pmb-flex--center" style="margin-top:.75rem;">
                    <span class="pmb-badge pmb-badge--{{ $CS::quotationBadge($quotation->status) }}">{{ $CS::quotationLabel($quotation->status) }}</span>
                    @if($quotation->isSent() && ! $quotation->hasExpired())
                        <span class="pmb-badge pmb-badge--orange">Valid until {{ $quotation->valid_until?->format('M j, Y') }}</span>
                    @endif
                    <a class="pmb-btn pmb-btn--outline pmb-btn--sm" href="{{ route('quotations.show', $quotation) }}">Review</a>
                </div>
            </div>
        @endforeach
    @endif
@endforeach

@if($quotations->isEmpty())
    <div class="pmb-empty">
        <div class="pmb-empty__icon">📋</div>
        <div class="pmb-empty__title">No quotations yet</div>
        <p>Quotations PMB prepares for your requests will appear here.</p>
        <a class="pmb-btn pmb-btn--gold pmb-btn--sm" href="{{ route('catalogue.index') }}">Start a request</a>
    </div>
@endif
@endsection
