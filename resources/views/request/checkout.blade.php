@extends('layouts.customer')

@section('title', 'Tell us about your event')

@section('content')
<div class="pmb-page-title">
    <h1 class="pmb-h1">Tell us about your event</h1>
    <p>These details help PMB prepare your request. They stay with this request only.</p>
</div>

<div class="pmb-grid pmb-grid--main">
    <div class="pmb-card">
        <h2 class="pmb-card__title">Event details</h2>
        <form method="POST" action="{{ route('requests.submit') }}" class="pmb-form">
            @csrf
            <input type="hidden" name="request_id" value="{{ $draft->id }}">

            <div class="pmb-field">
                <label class="pmb-label" for="event_date">Event date *</label>
                <input class="pmb-input" id="event_date" type="date" name="event_date" value="{{ old('event_date') }}" required>
                @error('event_date') <span class="pmb-error">{{ $message }}</span> @endforeach
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="event_time">Event time</label>
                <input class="pmb-input" id="event_time" type="time" name="event_time" value="{{ old('event_time') }}">
                @error('event_time') <span class="pmb-error">{{ $message }}</span> @endif
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="location">Delivery area / venue *</label>
                <input class="pmb-input" id="location" name="location" value="{{ old('location') }}" placeholder="e.g. Ujamaa, Mombasa" required>
                <p class="pmb-hint">Choose from the areas PMB delivers to. If your area isn't listed we'll let you know.</p>
                @error('location') <span class="pmb-error">{{ $message }}</span> @endforeach
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="notes">Additional notes</label>
                <textarea class="pmb-textarea" id="notes" name="notes" rows="3" placeholder="Special requests, dietary requirements...">{{ old('notes') }}</textarea>
            </div>

            <div class="pmb-flex">
                <button class="pmb-btn pmb-btn--gold" type="submit">Submit request</button>
                <a class="pmb-btn pmb-btn--ghost" href="{{ route('request.cart') }}">Back to cart</a>
            </div>
        </form>
    </div>

    <div class="pmb-card">
        <h2 class="pmb-card__title">Where we deliver</h2>
        <p style="color:var(--ink-muted);font-size:.9rem;margin:0 0 .75rem;">Select your area from the list below. If your area isn't listed, we'll let you know at submission.</p>
        <div class="pmb-zone-list">
            @forelse($zones as $zone)
                <div class="pmb-zone">
                    <div class="pmb-zone__name">{{ $zone->name }}</div>
                    <div class="pmb-zone__meta">
                        @if((float)$zone->base_charge > 0)
                            <span>From KSh {{ number_format($zone->base_charge, 2) }}</span>
                        @endif
                        @if((float)$zone->door_to_door_surcharge > 0)
                            <span>Door-to-door +KSh {{ number_format($zone->door_to_door_surcharge, 2) }}</span>
                        @endif
                        @if((float)$zone->beyond_stage_surcharge > 0)
                            <span>Beyond stage +KSh {{ number_format($zone->beyond_stage_surcharge, 2) }}</span>
                        @endif
                        @if($zone->free_delivery_above)
                            <span>Free above KSh {{ number_format($zone->free_delivery_above, 2) }}</span>
                        @endif
                    </div>
                </div>
            @empty
                <p style="color:var(--ink-muted);font-size:.9rem;">No delivery areas configured yet.</p>
            @endforeach
        </div>

        <div class="pmb-card" style="margin-top:1rem;">
            <h2 class="pmb-card__title">Request reference</h2>
            <p class="pmb-h1" style="font-size:1.3rem;">{{ $draft->reference }}</p>
            <p style="color:var(--ink-muted);margin:0;">Draft request — not yet submitted.</p>
        </div>
    </div>
</div>
<style>
.pmb-zone-list { display:flex; flex-direction:column; gap:.5rem; }
.pmb-zone { padding:.75rem 1rem; border:1px solid var(--border); border-radius:8px; }
.pmb-zone__name { font-weight:700; }
.pmb-zone__meta { display:flex; flex-wrap:wrap; gap:.5rem; font-size:.85rem; color:var(--ink-muted); }
.pmb-zone__meta span { background:var(--surface-2); padding:.15rem .5rem; border-radius:4px; }
.pmb-hint { font-size:.8rem; color:var(--ink-muted); margin:.25rem 0 0; }
</style>
@endsection