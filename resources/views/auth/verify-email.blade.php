@extends('auth.layout')

@section('title', 'Verify Email')

@section('content')
<div class="auth-card">
    <div class="auth-card-header">
        <div class="auth-logo">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                <polyline points="22,6 12,13 2,6"/>
            </svg>
        </div>
        <h1>Verify your email</h1>
        <p>A verification link has been sent to your email address. Please check your inbox and click the link to verify your account.</p>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="auth-form">
        @csrf
        <button type="submit" class="btn btn-outline-gold btn-block">Log out</button>
    </form>
</div>
@endsection
