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
                @error('event_date') <span class="pmb-error">{{ $message }}</span> @enderror
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="event_time">Event time</label>
                <input class="pmb-input" id="event_time" type="time" name="event_time" value="{{ old('event_time') }}">
                @error('event_time') <span class="pmb-error">{{ $message }}</span> @enderror
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="location">Location *</label>
                <input class="pmb-input" id="location" name="location" value="{{ old('location') }}" placeholder="Delivery address or venue" required>
                @error('location') <span class="pmb-error">{{ $message }}</span> @enderror
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
        <h2 class="pmb-card__title">Request reference</h2>
        <p class="pmb-h1" style="font-size:1.3rem;">{{ $draft->reference }}</p>
        <p style="color:var(--ink-muted);margin:0;">Draft request — not yet submitted.</p>
    </div>
</div>
@endsection
