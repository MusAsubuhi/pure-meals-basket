@extends('auth.layout')

@section('title', 'Login')

@section('content')
<div class="auth-card">
    <div class="auth-card-header">
        <div class="auth-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M15 3h4a2 2 0 012 2v14a2 2 0 01-2 2h-4"/>
                <polyline points="10 17 15 12 10 7"/>
                <line x1="15" y1="12" x2="3" y2="12"/>
            </svg>
        </div>
        <h1>Welcome back</h1>
        <p>Sign in to your account</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="auth-form" novalidate>
        @csrf

        @if ($errors->any())
            <div class="auth-alert error">
                {{ $errors->first('email') ?: 'Please check your credentials and try again.' }}
            </div>
        @endif

        <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                   placeholder="you@example.com">
        </div>

        <div class="form-group">
            <label for="password">Password</label>
            <div class="password-wrapper">
                <input id="password" type="password" name="password" required
                       placeholder="Enter your password">
                <button type="button" class="password-toggle" aria-label="Show password">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="btn btn-gold btn-block" data-original-text="Sign in">Sign in</button>
    </form>

    <div class="auth-links">
        Don't have an account? <a href="{{ route('register') }}">Register</a>
    </div>
</div>
@endsection
