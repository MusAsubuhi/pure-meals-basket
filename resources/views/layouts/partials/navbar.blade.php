<nav class="navbar" id="navbar">
    <div class="container navbar-inner">
        <a href="{{ url('/') }}" class="navbar-logo">
            <div class="navbar-logo-text">Pure Meals Basket</div>
        </a>

        <button class="navbar-toggle" id="navbar-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="navbar-nav" id="navbar-nav">
            @if (Route::has('login'))
                @auth
                    <a href="{{ url('/customer') }}" class="nav-link">Customer</a>
                    <a href="{{ url('/admin') }}" class="nav-link">Admin</a>
                    <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-gold btn-nav">Log out</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="nav-link">Log in</a>
                    <a href="{{ route('register') }}" class="btn btn-gold btn-nav">Register</a>
                @endauth
            @endif
        </div>
    </div>
</nav>
