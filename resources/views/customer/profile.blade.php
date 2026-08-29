@extends('layouts.customer')

@section('title', 'My Profile')

@section('content')
<div class="pmb-page-title">
    <h1 class="pmb-h1">My profile</h1>
    <p>Your contact details. Event-specific information stays with each request.</p>
</div>

@if (session('profile_incomplete'))
    <div style="background:#fff8e6;border:1px solid var(--gold, #d4a72c);border-radius:8px;padding:0.9rem 1.2rem;margin-bottom:1.25rem;color:#5c4a1e;">
        <strong>{{ session('profile_incomplete') }}</strong>
    </div>
@endif

<div class="pmb-grid pmb-grid--main">
    <div class="pmb-card">
        <h2 class="pmb-card__title">Contact information</h2>
        <form method="POST" action="{{ url('/customer/profile') }}" class="pmb-form">
            @csrf
            <div class="pmb-field">
                <label class="pmb-label" for="name">Full name</label>
                <input class="pmb-input {{ $errors->has('name') ? 'pmb-input__err' : '' }}" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                @error('name') <span class="pmb-error">{{ $message }}</span> @enderror
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="email">Email</label>
                <input class="pmb-input" id="email" value="{{ $user->email }}" disabled>
                <span class="pmb-hint">Email is used to sign in and can't be changed here.</span>
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="phone">Phone number</label>
                <input class="pmb-input" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" placeholder="07XX XXX XXX">
                @error('phone') <span class="pmb-error">{{ $message }}</span> @enderror
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="address_line1">Address</label>
                <input class="pmb-input" id="address_line1" name="address_line1" value="{{ old('address_line1', $customer->address_line1) }}" placeholder="Street / estate">
            </div>

            <div class="pmb-field">
                <label class="pmb-label" for="address_line2">Address line 2</label>
                <input class="pmb-input" id="address_line2" name="address_line2" value="{{ old('address_line2', $customer->address_line2) }}">
            </div>

            <div class="pmb-grid pmb-grid--2">
                <div class="pmb-field">
                    <label class="pmb-label" for="city">City / Town</label>
                    <input class="pmb-input" id="city" name="city" value="{{ old('city', $customer->city) }}">
                </div>
                <div class="pmb-field">
                    <label class="pmb-label" for="postal_code">Postal code</label>
                    <input class="pmb-input" id="postal_code" name="postal_code" value="{{ old('postal_code', $customer->postal_code) }}">
                </div>
            </div>

            <div>
                <button class="pmb-btn pmb-btn--gold" type="submit">Save changes</button>
            </div>
        </form>
    </div>

    <div class="pmb-card">
        <h2 class="pmb-card__title">Account</h2>
        <div class="pmb-money">
            <div class="pmb-money__row"><span>Account</span><span>{{ $customer->account?->account_number ?? '—' }}</span></div>
            <div class="pmb-money__row"><span>Member since</span><span>{{ $user->created_at?->format('M Y') ?? '—' }}</span></div>
        </div>
        <hr style="border:none;border-top:1px solid var(--border);margin:1rem 0;">
        <a class="pmb-btn pmb-btn--ghost pmb-btn--sm" href="https://wa.me/254737953292?text=Hi%20PMB%2C%20I%20need%20help%20with%20my%20account" target="_blank" rel="noopener">Help on WhatsApp →</a>
    </div>
</div>
@endsection
