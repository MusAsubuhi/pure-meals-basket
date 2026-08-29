<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>@yield('title', config('app.name', 'Pure Meals Basket'))</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Lato:wght@400;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
        @stack('styles')
    </head>
    <body>
        <header class="navbar" id="navbar">
    <div class="navbar-inner container">
      <a href="#top" class="navbar-logo">
        <img src="{{ asset('assets/images/logo.png') }}" alt="Pure Meals Basket logo" width="48" height="48" loading="lazy">
        <span class="navbar-logo-text">Pure Meals Basket</span>
      </a>

       @include('layouts.partials.navbar')

      <button class="navbar-toggle" id="navbar-toggle" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="navbar-nav">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </header>
       

        <main>
            @yield('content')
        </main>

        @include('layouts.partials.footer')
        <!-- Persistent WhatsApp floating button, visible at all screen sizes -->
        <a href="https://wa.me/254737953292?text=Hi%20PMB%2C%20I%27d%20like%20to%20book%20a%20consultation" class="whatsapp-float" target="_blank" rel="noopener" aria-label="Book via WhatsApp">
            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false"><path fill="currentColor" d="M16.04 3C9.4 3 4 8.36 4 15c0 2.26.62 4.38 1.7 6.2L4 29l7.98-1.66A11.9 11.9 0 0 0 16.04 27C22.68 27 28.08 21.64 28.08 15S22.68 3 16.04 3zm0 21.6c-1.98 0-3.83-.55-5.4-1.5l-.39-.23-4.73.98 1-4.6-.25-.4A9.53 9.53 0 0 1 5.6 15c0-5.24 4.28-9.5 9.44-9.5S24.5 9.76 24.5 15s-4.28 9.6-8.46 9.6zm5.28-7.16c-.29-.15-1.7-.84-1.96-.93-.26-.1-.46-.15-.65.14-.2.29-.75.93-.92 1.12-.17.2-.34.22-.63.07-.29-.14-1.2-.44-2.3-1.42-.85-.76-1.42-1.7-1.59-1.98-.17-.29-.02-.45.13-.6.13-.13.29-.34.44-.51.15-.17.2-.29.29-.48.1-.2.05-.36-.02-.5-.07-.15-.65-1.57-.9-2.15-.24-.57-.48-.5-.65-.5h-.56c-.2 0-.5.07-.77.36-.26.29-1 .98-1 2.4 0 1.4 1.03 2.76 1.17 2.95.15.2 2.02 3.09 4.9 4.33.68.3 1.22.47 1.63.6.68.22 1.3.19 1.8.11.55-.08 1.7-.7 1.94-1.36.24-.67.24-1.24.17-1.36-.07-.12-.26-.2-.55-.34z"/></svg>
        </a>

        <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2" defer></script>
        <script src="{{ asset('assets/js/main.js') }}"></script>
        @stack('scripts')
    </body>
</html>
