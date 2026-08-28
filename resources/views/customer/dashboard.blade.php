@extends('layouts.customer')

@section('title', 'My Dashboard')

@section('content')
@php
    $CS = \App\Support\CustomerStatus::class;
    $tl = app(\App\Services\CustomerPortal\UnifiedTimeline::class);
    $firstName = ucfirst(trim(explode(' ', $customer->name ?: '')[0]) ?: 'there');
    $activeOrders = $orders->reject(fn ($o) => $o->isTerminal());
@endphp

<div class="pmb-hero">
    <h1>Hello, {{ $firstName }} 👋</h1>
    <p>What are you planning today?</p>
    <div class="pmb-hero__actions pmb-flex">
        <a class="pmb-btn pmb-btn--gold" href="{{ route('catalogue.index') }}">Browse the menu</a>
        <a class="pmb-btn pmb-btn--outline" href="{{ route('requests.index') }}">Track a request</a>
    </div>
</div>

<div class="quick-links pmb-grid pmb-grid--3" style="margin-bottom:1.5rem;">
    <a class="pmb-card" href="{{ route('catalogue.index') }}">
        <span class="ql-ic">🍽️</span>
        <span class="ql-t">Browse Catering</span>
        <span class="ql-s">Menus, cakes, juices &amp; celebration foods</span>
    </a>
    <a class="pmb-card" href="{{ route('catalogue.index') }}">
        <span class="ql-ic">🥤</span>
        <span class="ql-t">Order Juice</span>
        <span class="ql-s">Fresh juices &amp; beverages, ready when you are</span>
    </a>
    <a class="pmb-card" href="{{ route('requests.checkout') }}">
        <span class="ql-ic">📝</span>
        <span class="ql-t">Get a Quote</span>
        <span class="ql-s">Request a custom quotation for your event</span>
    </a>
</div>

<div id="actions" class="pmb-grid pmb-grid--main">
    <div>
        {{-- Action required --}}
        @if($actionNeeded->isNotEmpty())
            <div class="pmb-card" style="border-left:4px solid var(--pmb-gold);">
                <h2 class="pmb-card__title">Action required</h2>
                @foreach($actionNeeded as $a)
                    <div class="pmb-action pmb-action--{{ $a['tone'] }}">
                        <div class="pmb-action__icon">{{ $a['icon'] }}</div>
                        <div class="pmb-action__body">
                            <div class="pmb-action__title">{{ $a['title'] }}</div>
                            <div class="pmb-action__detail">{{ $a['detail'] }}</div>
                            <div class="pmb-action__cta">
                                <a class="pmb-btn pmb-btn--outline pmb-btn--sm" href="{{ $a['url'] }}">{{ $a['cta'] }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @elseif($tracking->isNotEmpty())
            <div class="pmb-card">
                <h2 class="pmb-card__title">In progress</h2>
                @foreach($tracking as $a)
                    <div class="pmb-action pmb-action--blue">
                        <div class="pmb-action__icon">{{ $a['icon'] }}</div>
                        <div class="pmb-action__body">
                            <div class="pmb-action__title">{{ $a['title'] }}</div>
                            <div class="pmb-action__detail">{{ $a['detail'] }}</div>
                            <div class="pmb-action__cta">
                                <a class="pmb-btn pmb-btn--outline pmb-btn--sm" href="{{ $a['url'] }}">{{ $a['cta'] }}</a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="pmb-empty" style="margin-bottom:1.25rem;">
                <div class="pmb-empty__icon">✨</div>
                <div class="pmb-empty__title">Nothing waiting on you</div>
                <p>You're all caught up. Start something new whenever you're ready.</p>
                <a class="pmb-btn pmb-btn--gold pmb-btn--sm" href="{{ route('catalogue.index') }}">Browse the menu</a>
            </div>
        @endif

        {{-- Active orders --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Your active orders</h2>
            @forelse($activeOrders as $order)
                <div style="padding:1rem 0;border-bottom:1px solid var(--border);">
                    <div class="pmb-row" style="margin-bottom:.5rem;">
                        <div>
                            <strong>{{ $order->reference }}</strong>
                            <div class="pmb-line__sub">
                                {{ $order->event_date?->format('M j, Y') }}{{ $order->location ? ' · '.$order->location : '' }}
                            </div>
                        </div>
                        <span class="pmb-badge pmb-badge--{{ $CS::orderBadge($order->status) }}">{{ $CS::orderLabel($order->status) }}</span>
                    </div>
                    @include('customer.partials.journey', ['stages' => $tl->journey($order->request)])
                    <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="{{ route('orders.show', $order) }}" style="margin-top:.5rem;">View order →</a>
                </div>
            @empty
                <p style="color:var(--ink-muted);margin:0;">No active orders right now.</p>
            @endforelse
        </div>
    </div>

    <div>
        {{-- Recent activity (unified timeline) --}}
        <div class="pmb-card">
            <h2 class="pmb-card__title">Recent activity</h2>
            @if(count($activity) > 0)
                <div class="pmb-timeline">
                    @foreach($activity as $ev)
                        <div class="pmb-tl-item">
                            <div class="pmb-tl-dot pmb-tl-dot--green is-line"></div>
                            <div class="pmb-tl__body">
                                <div class="pmb-tl__title">{{ $ev['title'] }}</div>
                                @if($ev['detail']) <div class="pmb-tl__detail">{{ $ev['detail'] }}</div> @endif
                                <div class="pmb-tl__meta">{{ $ev['at']->format('M j · g:i A') }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <p style="color:var(--ink-muted);margin:0;">No activity yet. Your journey will appear here.</p>
            @endif
        </div>
    </div>
</div>
@endsection

