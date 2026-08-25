<footer class="footer">
    <div class="container">
        <div class="footer-grid">
            <div>
                <div class="footer-logo-img">
                    <div class="navbar-logo-text" style="color: var(--pmb-gold-light);">Pure Meals Basket</div>
                </div>
                <p class="footer-tagline">Fresh meals, delivered with love.</p>
                <p class="footer-line">&copy; {{ date('Y') }} Pure Meals Basket. All rights reserved.</p>
            </div>
            <div>
                <h3>Quick Links</h3>
                <div class="footer-links">
                    <a href="{{ url('/') }}">Home</a>
                    <a href="{{ route('login') }}">Log in</a>
                    <a href="{{ route('register') }}">Register</a>
                </div>
            </div>
            <div>
                <h3>Contact</h3>
                <div class="footer-links">
                    <p>
                        <span class="footer-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/></svg>
                        </span>
                        +254 737 953 292
                    </p>
                    <p>
                        <span class="footer-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        </span>
                        info@puremealsbasket.co.ke
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>
