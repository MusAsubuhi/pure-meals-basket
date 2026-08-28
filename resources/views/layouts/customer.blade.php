<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'My PMB Account') &middot; Pure Meals Basket</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/customer.css') }}">
    @stack('styles')
</head>
<body>
@php
    $portalCustomer = auth()->user()->customer;
    $portalResolver = new \App\Services\CustomerPortal\ActionRequiredResolver();
    $portalActions  = $portalResolver->resolve($portalCustomer);
    $portalNeeds    = $portalResolver->countNeedingAction($portalCustomer);
    $portalName     = $portalCustomer->getNameAttribute() ?: auth()->user()->name;
    $portalInitial  = strtoupper(mb_substr($portalName, 0, 1) ?: '?');
@endphp

<header class="pmb-header">
    <div class="pmb-header__inner">
        <a href="{{ route('customer.dashboard') }}" class="pmb-brand">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 002-2V2"/>
                <path d="M7 2v20"/>
                <path d="M21 15V2a5 5 0 00-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
            </svg>
            <span>Pure Meals Basket</span>
        </a>

        <div class="pmb-account" id="pmb-account">
            <button type="button" id="pmb-account-trigger"
                    style="background:none;border:none;color:rgba(255,255,255,.9);cursor:pointer;font-family:var(--font-body);font-weight:700;"
                    aria-expanded="false" aria-haspopup="true">
                {{ $portalName }} ▾
            </button>
            <span class="pmb-account__badge" onclick="document.getElementById('pmb-account').classList.toggle('is-open')" title="{{ $portalName }}">{{ $portalInitial }}</span>

            <div class="pmb-account__menu" role="menu">
                <a href="{{ route('customer.profile') }}" role="menuitem">My profile</a>
                <a href="https://wa.me/254737953292?text=Hi%20PMB%2C%20I%20need%20help%20with%20my%20account" target="_blank" rel="noopener" role="menuitem">Help / WhatsApp</a>
                <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                    @csrf
                    <button type="submit" role="menuitem" style="color:var(--pmb-danger);">Log out</button>
                </form>
            </div>
        </div>
    </div>
</header>

@if($portalNeeds > 0)
    <div class="pmb-notice" role="status">
        <span>⚡</span>
        <span>You have {{ $portalNeeds }} thing{{ $portalNeeds === 1 ? '' : 's' }} to do.
            <a href="{{ route('customer.dashboard') }}#actions">View</a>
        </span>
    </div>
@endif

<nav class="pmb-nav" aria-label="Account navigation">
    <div style="max-width:var(--content-max);margin:0 auto;display:flex;align-items:center;">
        <ul class="pmb-nav__items" id="pmb-nav">
            <li><a class="pmb-nav__link {{ request()->routeIs('customer.dashboard') ? 'is-active' : '' }}" href="{{ route('customer.dashboard') }}">Dashboard</a></li>
            <li><a class="pmb-nav__link {{ request()->routeIs('catalogue.*') ? 'is-active' : '' }}" href="{{ route('catalogue.index') }}">Browse</a></li>
            <li><a class="pmb-nav__link {{ request()->routeIs('requests.*') ? 'is-active' : '' }}" href="{{ route('requests.index') }}">My Requests</a></li>
            <li><a class="pmb-nav__link {{ request()->routeIs('quotations.*') ? 'is-active' : '' }}" href="{{ route('quotations.index') }}">My Quotations</a></li>
            <li><a class="pmb-nav__link {{ request()->routeIs('orders.*') ? 'is-active' : '' }}" href="{{ route('orders.index') }}">My Orders</a></li>
            <li>
                <a class="pmb-nav__link {{ request()->routeIs(['payments.*','customer.payments']) ? 'is-active' : '' }}" href="{{ route('customer.payments') }}">
                    Payments
                    @if($portalNeeds > 0)
                        <span class="pmb-nav__count">{{ $portalNeeds }}</span>
                    @endif
                </a>
            </li>
        </ul>
        <button class="pmb-nav__toggle" id="pmb-nav-toggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="pmb-nav">☰</button>
    </div>
</nav>

<main class="pmb-main">
    @if(session('success'))
        <div class="pmb-flash pmb-flash--success" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="pmb-flash pmb-flash--error" role="alert">{{ session('error') }}</div>
    @endif
    @if(session('message'))
        <div class="pmb-flash pmb-flash--info" role="status">{{ session('message') }}</div>
    @endif
    @if($errors->any())
        <div class="pmb-flash pmb-flash--error" role="alert">
            @foreach($errors->all() as $e) {{ $e }} @endforeach
        </div>
    @endif

    @yield('content')
</main>

<script>
(function () {
    var toggle = document.getElementById('pmb-nav-toggle');
    var nav = document.getElementById('pmb-nav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }
    var account = document.getElementById('pmb-account');
    var trigger = document.getElementById('pmb-account-trigger');
    if (trigger && account) {
        trigger.addEventListener('click', function () { account.classList.toggle('is-open'); });
    }
    document.addEventListener('click', function (e) {
        if (account && !account.contains(e.target)) account.classList.remove('is-open');
    });
})();
</script>
@stack('scripts')
</body>
</html>

