@extends('auth.layout')

@section('title', 'Customer')

@section('content')
<div class="auth-card">
    <div class="auth-card-header">
        <div class="auth-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
        </div>
        <h1>Customer Area</h1>
        <p>Welcome to your customer dashboard.</p>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="auth-form">
        @csrf
        <button type="submit" class="btn btn-outline-gold btn-block">Log out</button>
    </form>
</div>
@endsection
