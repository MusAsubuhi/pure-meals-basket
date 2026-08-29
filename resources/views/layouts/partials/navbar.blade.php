<nav class="navbar-nav" id="navbar-nav">
        <a href="{{ route('catalogue.index') }}" class="nav-link">Browse Menu</a>
        <a href="#about" class="nav-link">About</a>
        <a href="#services" class="nav-link">Services</a>
        <a href="#coverage" class="nav-link">Coverage</a>
        <a href="#feedback" class="nav-link">Feedback</a>
        @auth
            <a href="{{ route('customer.dashboard') }}" class="btn btn-gold btn-nav">My Account</a>
        @else
            <a href="{{ route('login') }}" class="btn btn-gold btn-nav">Sign In</a>
        @endauth
      </nav>