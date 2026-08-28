@extends('layouts.customer')

@section('title', $request->reference)

@section('content')
@php
    $CS = \App\Support\CustomerStatus::class;
    $tl = app(\App\Services\CustomerPortal\UnifiedTimeline::class);
    $journey = $tl->journey($request);
    $event = $tl->timeline($request);
@endphp

<div class="pmb-page-title">
    <div class="pmb-row">
        <div>
            <h1 class="pmb-h1">{{ $request->reference }}</h1>
            <p>
                {{ $request->event_date?->format('F j, Y') ?? 'Date not set' }}
                @if($request->event_time) at {{ $request->event_time->format('g:i A') }} @endif
                @if($request->location) · {{ $request->location }} @endif
            </p>
        </div>
        <span class="pmb-badge pmb-badge--{{ $CS::requestBadge($request->status) }}">{{ $CS::requestLabel($request->status) }}</span>
    </div>
</div>

@if(in_array($request->status->value, ['SUBMITTED','UNDER_REVIEW','NEEDS_INFORMATION','QUOTATION_REQUIRED'], true))
    <div class="pmb-notice" style="border-radius:8px;margin-bottom:1.25rem;">
        <span>🎉</span>
        <span>Request received — our team is reviewing it. We'll get back to you shortly.</span>
    </div>
@endif

<div class="pmb-grid pmb-grid--main">
    <div>
        {{-- Status stepper --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Status</h2>
            @include('customer.partials.journey', ['stages' => $journey])
        </div>

        {{-- Items --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Your items</h2>
            @forelse($request->items as $item)
                <div class="pmb-line">
                    <div>
                        <div class="pmb-line__name">{{ $item->name }}</div>
                        <div class="pmb-line__sub">
                            {{ $item->quantity }} {{ $item->unit ?? 'unit' }}
                            @if($item->pricing_type) · {{ $item->pricing_type->label() }} @endif
                        </div>
                    </div>
                    <div class="pmb-line__price">
                        @if($item->subtotal)
                            <span class="pmb-ksh">KSh {{ number_format($item->subtotal, 2) }}</span>
                        @elseif($item->isQuotationRequired())
                            <span class="pmb-badge pmb-badge--purple">Quote required</span>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:var(--ink-muted);margin:0;">No items in this request yet.</p>
            @endforelse
        </div>

        {{-- Event details --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Event details</h2>
            <div class="pmb-money">
                <div class="pmb-money__row"><span>Date</span><span>{{ $request->event_date?->format('M j, Y') ?? '—' }}</span></div>
                <div class="pmb-money__row"><span>Time</span><span>{{ $request->event_time?->format('g:i A') ?? '—' }}</span></div>
                <div class="pmb-money__row"><span>Location</span><span>{{ $request->location ?? '—' }}</span></div>
                @if($request->notes)
                    <div class="pmb-money__row"><span>Notes</span><span>{{ $request->notes }}</span></div>
                @endif
            </div>
        </div>

        {{-- Clarifications --}}
        @if($request->clarifications->isNotEmpty())
            <div class="pmb-card" id="clarifications">
                <h2 class="pmb-card__title">Messages from PMB</h2>
                @foreach($request->clarifications as $clarification)
                    <div class="pmb-action {{ $clarification->hasBeenAnswered() ? 'pmb-action--green' : 'pmb-action--orange' }}">
                        <div class="pmb-action__icon">{{ $clarification->hasBeenAnswered() ? '✓' : '✉️' }}</div>
                        <div class="pmb-action__body">
                            <div class="pmb-action__title">{{ $clarification->question }}</div>
                            @if($clarification->hasBeenAnswered())
                                <div class="pmb-action__detail"><strong>Your response:</strong> {{ $clarification->response }}</div>
                            @else
                                <form method="POST" action="{{ route('requests.respond', $clarification) }}">
                                    @csrf
                                    <div class="pmb-field" style="margin-top:.5rem;">
                                        <textarea class="pmb-textarea" name="response" rows="2" placeholder="Type your response..." required></textarea>
                                    </div>
                                    <button class="pmb-btn pmb-btn--gold pmb-btn--sm" type="submit" style="margin-top:.5rem;">Send response</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div>
        {{-- Timeline --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Activity</h2>
            @if(count($event) > 0)
                <div class="pmb-timeline">
                    @foreach($event as $ev)
                        <div class="pmb-tl-item">
                            <div class="pmb-tl-dot is-line"></div>
                            <div class="pmb-tl__body">
                                <div class="pmb-tl__title">{{ $ev['title'] }}</div>
                                @if($ev['detail']) <div class="pmb-tl__detail">{{ $ev['detail'] }}</div> @endif
                                <div class="pmb-tl__meta">{{ $ev['at']->format('M j · g:i A') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color:var(--ink-muted);margin:0;">No activity yet.</p>
            @endif
        </div>

        {{-- Linked quotation / order --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Linked</h2>
            <div class="pmb-flex">
                @if($request->quotations->isNotEmpty())
                    <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('quotations.show', $request->quotations->first()) }}">View quotation</a>
                @endif
                @if($request->orders->isNotEmpty())
                    <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('orders.show', $request->orders->first()) }}">View order</a>
                @endif
                @if($request->quotations->isEmpty() && $request->orders->isEmpty())
                    <p style="color:var(--ink-muted);margin:0;">Nothing linked yet.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
