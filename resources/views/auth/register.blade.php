@extends('auth.layout')

@section('auth-container-class', 'auth-container--wide')

@section('title', 'Register')

@section('content')
<div class="auth-card auth-card--registration">
    <div class="auth-card-header">
        <div class="auth-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M16 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                <circle cx="8.5" cy="7" r="4"/>
                <line x1="20" y1="8" x2="20" y2="14"/>
                <line x1="23" y1="11" x2="17" y2="11"/>
            </svg>
        </div>
        <h1>Create an account</h1>
        <p>Join Pure Meals Basket</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="auth-form" novalidate>
        @csrf

        @if ($errors->any())
            <div class="auth-alert error">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="reg-section">
            <div class="reg-section-title">Personal Information</div>
            <div class="reg-grid">
                <div class="form-group">
                    <label for="name">Full Name <span style="color: #dc2626;">*</span></label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                           placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email Address <span style="color: #dc2626;">*</span></label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required
                           placeholder="you@example.com">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number <span style="color: #dc2626;">*</span></label>
                    <input id="phone" type="tel" name="phone" value="{{ old('phone') }}" required
                           placeholder="0712345678">
                </div>
            </div>
        </div>

        <div class="reg-section">
            <div class="reg-section-title">Address Information</div>
            <div class="reg-grid">
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="address_line1">Address Line 1 <span style="color: #dc2626;">*</span></label>
                    <input id="address_line1" type="text" name="address_line1" value="{{ old('address_line1') }}" required
                           placeholder="Street address, P.O. Box">
                </div>

                <div class="form-group" style="grid-column: 1 / -1;">
                    <label for="address_line2">Address Line 2</label>
                    <input id="address_line2" type="text" name="address_line2" value="{{ old('address_line2') }}"
                           placeholder="Apartment, suite, etc. (optional)">
                </div>

                <div class="form-group">
                    <label for="city">City <span style="color: #dc2626;">*</span></label>
                    <input id="city" type="text" name="city" value="{{ old('city') }}" required
                           placeholder="Nairobi">
                </div>

                <div class="form-group">
                    <label for="state">State / Province</label>
                    <input id="state" type="text" name="state" value="{{ old('state') }}"
                           placeholder="Nairobi County">
                </div>

                <div class="form-group">
                    <label for="postal_code">Postal Code</label>
                    <input id="postal_code" type="text" name="postal_code" value="{{ old('postal_code') }}"
                           placeholder="00100">
                </div>

                <div class="form-group">
                    <label for="country">Country <span style="color: #dc2626;">*</span></label>
                    <input id="country" type="text" name="country" value="{{ old('country', 'Kenya') }}" required
                           placeholder="Kenya">
                </div>
            </div>
        </div>

        <div class="reg-section">
            <div class="reg-section-title">Security</div>
            <div class="reg-grid">
                <div class="form-group">
                    <label for="password">Password <span style="color: #dc2626;">*</span></label>
                    <div class="password-wrapper">
                        <input id="password" type="password" name="password" required
                               placeholder="Min. 8 characters">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password_confirmation">Confirm Password <span style="color: #dc2626;">*</span></label>
                    <div class="password-wrapper">
                        <input id="password_confirmation" type="password" name="password_confirmation" required
                               placeholder="Repeat your password">
                        <button type="button" class="password-toggle" aria-label="Show password">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-gold btn-block" data-original-text="Register">Register</button>
    </form>

    <div class="auth-social">
        <div class="auth-divider"><span>or</span></div>
        <a href="{{ route('auth.google') }}" class="btn btn-google btn-block">
            <svg width="18" height="18" viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
            </svg>
            Sign up with Google
        </a>
    </div>

    <div class="auth-links">
        Already have an account? <a href="{{ route('login') }}">Sign in</a>
    </div>
</div>
@endsection
