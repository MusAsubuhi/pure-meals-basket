@extends('layouts.app')

@section('content')
  <!-- ============================================
       SECTION 1: HERO
       ============================================ -->
  <section class="hero" id="top">
    <img class="hero-bg media-photo" src="{{ asset('assets/images/hero-bg.webp') }}" alt="Warm spread of Mombasa catering">
    <div class="hero-overlay"></div>
    <div class="hero-content container">
      <h1 class="hero-heading">Simply Delicious and Refreshing</h1>
      <p class="hero-subheading">Professional catering, fresh juices, and celebration foods for institutions and events across Mombasa.</p>
      <p class="hero-body">From church fellowships to corporate lunches, school meals to wedding celebrations — Pure Meals Basket brings quality, warmth, and punctuality to every table.</p>

      <div class="hero-cta-group">
        <a href="https://wa.me/254737953292?text=Hi%20PMB%2C%20I%27d%20like%20to%20book%20a%20free%20consultation" class="btn btn-gold btn-hero" target="_blank" rel="noopener">Book a Free Consultation</a>
        <a href="#order" class="btn btn-hero btn-outline-hero">Place an Order</a>
      </div>

      <ul class="hero-trust">
        <li><span aria-hidden="true">✓</span> Simple &amp; Straightforward</li>
        <li><span aria-hidden="true">✓</span> Uncompromising Quality</li>
        <li><span aria-hidden="true">✓</span> Always Punctual</li>
      </ul>
    </div>
  </section>

  <!-- ============================================
       SECTION 2: ABOUT PMB
       ============================================ -->
  <section class="about" id="about">
    <div class="container about-grid">
      <img class="about-image media-photo" src="{{ asset('assets/images/why-pmb.webp') }}" alt="Pure Meals Basket team at work" loading="lazy">
      <div class="about-content">
        <h2>Rooted in Mombasa. Passionate About Food.</h2>
        <p>Pure Meals Basket was born from a shared passion for food that brings people together. With more than 5 years of combined catering experience in Mombasa, our team knows what it takes to deliver a meal experience that your guests will remember.</p>
        <p>We are not just caterers. We are the people who show up on time, serve with a smile, and make sure every plate tells a story of care, quality, and the rich flavours of the Kenyan coast — an engaging experience from the first bite to the last.</p>
        <p class="accent-line">Simply Delicious and Refreshing — that is our promise to you.</p>
      </div>
    </div>
    <!-- PHASE 2: Add founder story section with team photos -->
  </section>

  <!-- ============================================
       SECTION 3: SERVICES
       ============================================ -->
  <section class="services" id="services">
    <div class="container">
      <h2 class="section-heading">What We Offer</h2>
      <p class="section-subheading">Three pillars, one promise — every experience Simply Delicious and Refreshing.</p>

      <div class="services-grid">
        <article class="service-card">
          <img class="service-image media-photo" src="{{ asset('assets/images/service-catering.webp') }}" alt="Catering spread for a Mombasa event" loading="lazy">
          <span class="service-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 19h18"/><path d="M4 19a8 8 0 0 1 16 0"/><path d="M12 11V7"/><circle cx="12" cy="5" r="1.4" fill="currentColor" stroke="none"/></svg>
          </span>
          <h3>Catering</h3>
          <p>From school meals to corporate lunches and church fellowships, we deliver fresh, well-prepared food to institutions and gatherings of all sizes across Mombasa.</p>
        </article>

        <article class="service-card">
          <img class="service-image media-photo" src="{{ asset('assets/images/service-juice.webp') }}" alt="Fresh tropical juices" loading="lazy">
          <span class="service-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 4h10l-1.2 15a1 1 0 0 1-1 .9H9.2a1 1 0 0 1-1-.9L7 4Z"/><path d="M6.5 8.5h11"/><path d="M15.5 2v5"/></svg>
          </span>
          <h3>Juices &amp; Beverages</h3>
          <p>Refreshing natural juices and beverages made from tropical fruits, perfect for events, meetings, and celebrations that deserve something special on the table.</p>
        </article>

        <article class="service-card">
          <img class="service-image media-photo" src="{{ asset('assets/images/service-cakes.webp') }}" alt="Celebration cake" loading="lazy">
          <span class="service-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="14" width="16" height="7" rx="1"/><path d="M4 17.5h16"/><path d="M9 14v-3M15 14v-3"/><line x1="12" y1="11" x2="12" y2="6"/><circle cx="12" cy="4.3" r="1.3" fill="currentColor" stroke="none"/></svg>
          </span>
          <h3>Cakes &amp; Celebration Foods</h3>
          <p>Beautiful cakes and celebration catering for weddings, birthdays, graduations, and every milestone worth marking with something delicious.</p>
        </article>
      </div>
    </div>
    <!-- PHASE 2: Link each card to a dedicated service detail page -->
  </section>

  <!-- ============================================
       SECTION 4: WHO WE SERVE
       ============================================ -->
  <section class="who-we-serve" id="who-we-serve">
    <div class="container">
      <h2 class="section-heading">Who We Serve</h2>
      <p class="section-subheading">Whether you are planning a church fellowship lunch or a 500-person graduation reception, PMB has the experience and the heart to make it memorable.</p>

      <div class="clients-grid">
        <article class="client-card">
          <img class="client-image media-photo" src="{{ asset('assets/images/client-church.webp') }}" alt="Church fellowship gathering" loading="lazy">
          <h3>Churches &amp; Faith Communities</h3>
          <p>Sunday fellowships, prayer breakfasts, youth events, and church celebrations catered with warmth and reliability.</p>
        </article>

        <article class="client-card">
          <img class="client-image media-photo" src="{{ asset('assets/images/client-school.webp') }}" alt="School institutional catering" loading="lazy">
          <h3>Schools &amp; Institutions</h3>
          <p>Nutritious, well-prepared meals for school events, prize givings, staff lunches, and institutional gatherings.</p>
        </article>

        <article class="client-card">
          <img class="client-image media-photo" src="{{ asset('assets/images/client-corporate.webp') }}" alt="Corporate office catering" loading="lazy">
          <h3>Corporates &amp; Offices</h3>
          <p>Professional tea breaks, working lunches, AGMs, and corporate events served with the punctuality your schedule demands.</p>
        </article>

        <article class="client-card">
          <img class="client-image media-photo" src="{{ asset('assets/images/client-wedding.webp') }}" alt="Wedding celebration table" loading="lazy">
          <h3>Weddings &amp; Celebrations</h3>
          <p>Weddings, birthdays, graduations, and baby showers — celebrations that deserve food as special as the occasion.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- ============================================
       SECTION 5: WHY PMB
       ============================================ -->
  <section class="why-pmb" id="why-pmb">
    <div class="container">
      <h2 class="section-heading">Why Pure Meals Basket</h2>

      <div class="why-grid">
        <div class="why-card">
          <span class="why-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 12.5l2.5 2.5L16 9.5"/></svg>
          </span>
          <h3>Simply Straightforward</h3>
          <p>No complicated processes. You tell us about your event, we handle the rest. Getting great food for your occasion should never be stressful.</p>
        </div>

        <div class="why-card">
          <span class="why-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3.5l2.6 5.4 5.9.7-4.3 4.1 1.1 5.9L12 16.9l-5.3 2.7 1.1-5.9-4.3-4.1 5.9-.7L12 3.5Z"/></svg>
          </span>
          <h3>Quality You Can Taste</h3>
          <p>We use fresh ingredients and prepare everything with care. Every dish we serve is a reflection of our pride in what we do and our respect for your guests.</p>
        </div>

        <div class="why-card">
          <span class="why-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8.5"/><path d="M12 7.5v5l3.5 2"/></svg>
          </span>
          <h3>Punctual. Every Time.</h3>
          <p>We understand that your event runs on a schedule. PMB shows up on time, sets up properly, and serves when you need it — not when it is convenient for us.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ============================================
       SECTION 6: COVERAGE AREA
       ============================================ -->
  <section class="coverage" id="coverage">
    <div class="container coverage-grid">
      <div class="coverage-content">
        <h2 class="section-heading section-heading-left">Serving All of Mombasa</h2>
        <p>Pure Meals Basket serves clients across the greater Mombasa region. Whether you are on the island, along the South Coast, or up on the North Coast, we come to you.</p>

        <ul class="coverage-list">
          <li><span class="coverage-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 18c2-1 4-1.5 9-1.5s7 .5 9 1.5"/><path d="M12 16V9"/><path d="M12 9c0-2-1.5-3.3-3-2.8M12 9c0-2 1.7-3.3 3.4-2.8"/></svg></span> <span><strong>Mombasa Island</strong> — our home base</span></li>
          <li><span class="coverage-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 10c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0 3 1.5 4.5 0"/><path d="M3 15c1.5-1.5 3-1.5 4.5 0s3 1.5 4.5 0 3-1.5 4.5 0 3 1.5 4.5 0"/></svg></span> <span><strong>South Coast</strong> — serving clients to Ukunda</span></li>
          <li><span class="coverage-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 16h18"/><circle cx="12" cy="12" r="4"/><path d="M12 5v1.6M6.5 8l1.1 1.1M17.5 8l-1.1 1.1"/></svg></span> <span><strong>North Coast</strong> — serving clients through Nyali</span></li>
        </ul>

        <p class="coverage-note">Not sure if we cover your area? Send us a WhatsApp message and we will confirm within the hour.</p>
        <a href="https://wa.me/254737953292?text=Hi%20PMB%2C%20I%27d%20like%20to%20book%20a%20free%20consultation" class="btn btn-outline-gold" target="_blank" rel="noopener">Message Us on WhatsApp</a>
      </div>
      <img class="coverage-image media-photo" src="images/map-mombasa.webp" alt="Map of Mombasa coverage area" loading="lazy">
    </div>
    <!-- PHASE 3: Update to show franchise territory map by county -->
  </section>

  <!-- ============================================
       SECTION 7: TESTIMONIALS
       ============================================ -->
  <section class="testimonials" id="testimonials">
    <div class="container">
      <h2 class="section-heading">What Our Clients Say</h2>

      <div class="testimonials-grid">
        <article class="testimonial-card">
          <span class="testimonial-quote" aria-hidden="true">&ldquo;</span>
          <p class="testimonial-text">Testimonial coming soon. We are just getting started and we would love for you to be one of our first stories.</p>
          <p class="testimonial-name">— Client Name, Event Type</p>
          <p class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</p>
        </article>

        <article class="testimonial-card">
          <span class="testimonial-quote" aria-hidden="true">&ldquo;</span>
          <p class="testimonial-text">Testimonial coming soon. We are just getting started and we would love for you to be one of our first stories.</p>
          <p class="testimonial-name">— Client Name, Event Type</p>
          <p class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</p>
        </article>

        <article class="testimonial-card">
          <span class="testimonial-quote" aria-hidden="true">&ldquo;</span>
          <p class="testimonial-text">Testimonial coming soon. We are just getting started and we would love for you to be one of our first stories.</p>
          <p class="testimonial-name">— Client Name, Event Type</p>
          <p class="testimonial-stars" aria-label="5 out of 5 stars">★★★★★</p>
        </article>
      </div>
    </div>
    <!-- PHASE 2: Replace placeholders with real client testimonials -->
    <!-- PHASE 2: Add Google Reviews integration -->
  </section>

  <!-- ============================================
       SECTION 8: PLACE AN ORDER
       ============================================ -->
  <section class="order" id="order">
    <div class="container">
      <h2 class="section-heading">Place an Order</h2>
      <p class="section-subheading">Already know what you want? Fill in the details below and we will confirm your order within 24 hours.</p>

      <div class="order-service-select" id="order-service-select">
        <button type="button" class="order-service-card" data-service="catering" aria-pressed="false">
          <span class="order-service-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 19h18"/><path d="M4 19a8 8 0 0 1 16 0"/><path d="M12 11V7"/><circle cx="12" cy="5" r="1.4" fill="currentColor" stroke="none"/></svg>
          </span>
          <span class="order-service-name">Catering</span>
        </button>

        <button type="button" class="order-service-card" data-service="juice" aria-pressed="false">
          <span class="order-service-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M7 4h10l-1.2 15a1 1 0 0 1-1 .9H9.2a1 1 0 0 1-1-.9L7 4Z"/><path d="M6.5 8.5h11"/><path d="M15.5 2v5"/></svg>
          </span>
          <span class="order-service-name">Juices &amp; Beverages</span>
        </button>

        <button type="button" class="order-service-card" data-service="cakes" aria-pressed="false">
          <span class="order-service-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="14" width="16" height="7" rx="1"/><path d="M4 17.5h16"/><path d="M9 14v-3M15 14v-3"/><line x1="12" y1="11" x2="12" y2="6"/><circle cx="12" cy="4.3" r="1.3" fill="currentColor" stroke="none"/></svg>
          </span>
          <span class="order-service-name">Cakes &amp; Celebration Foods</span>
        </button>
      </div>

      <form class="order-form" id="order-form" novalidate hidden>
        <div class="form-group">
          <label for="order-name">Your Name</label>
          <input type="text" id="order-name" name="name" required autocomplete="name">
        </div>

        <div class="form-group">
          <label for="order-phone">Your Phone Number</label>
          <input type="tel" id="order-phone" name="phone" required autocomplete="tel">
        </div>

        <!-- CATERING FIELDS -->
        <div class="order-fields" data-service-fields="catering" hidden>
          <div class="form-group">
            <label for="catering-event-type">Event Type</label>
            <select id="catering-event-type" name="catering_event_type">
              <option value="" disabled selected>Select an event type</option>
              <option value="Wedding">Wedding</option>
              <option value="Birthday">Birthday</option>
              <option value="Graduation">Graduation</option>
              <option value="Church Event">Church Event</option>
              <option value="Corporate">Corporate</option>
              <option value="School Event">School Event</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label for="catering-event-date">Event Date</label>
            <input type="date" id="catering-event-date" name="catering_event_date">
          </div>

          <div class="form-group">
            <label for="catering-attendees">Number of Attendees</label>
            <input type="number" id="catering-attendees" name="catering_attendee_count" min="30" placeholder="Minimum 30">
          </div>

          <div class="form-group">
            <label for="catering-venue">Venue or Location</label>
            <input type="text" id="catering-venue" name="catering_venue">
          </div>

          <div class="form-group">
            <label for="catering-notes">Dietary Requirements or Special Notes</label>
            <textarea id="catering-notes" name="catering_dietary_notes" rows="3"></textarea>
          </div>
        </div>

        <!-- JUICE & BEVERAGES FIELDS -->
        <div class="order-fields" data-service-fields="juice" hidden>
          <div class="form-group">
            <label for="juice-quantity">Quantity (Litres)</label>
            <input type="number" id="juice-quantity" name="juice_quantity_litres" min="5" placeholder="Minimum 5">
          </div>

          <fieldset class="field-fieldset">
            <legend>Flavour Preferences</legend>
            <div class="checkbox-grid" id="juice-flavour-group">
              <label class="checkbox-option"><input type="checkbox" name="juice_flavours" value="Passion Fruit"> Passion Fruit</label>
              <label class="checkbox-option"><input type="checkbox" name="juice_flavours" value="Mango"> Mango</label>
              <label class="checkbox-option"><input type="checkbox" name="juice_flavours" value="Tamarind"> Tamarind</label>
              <label class="checkbox-option"><input type="checkbox" name="juice_flavours" value="Watermelon"> Watermelon</label>
              <label class="checkbox-option"><input type="checkbox" name="juice_flavours" value="Mixed Tropical"> Mixed Tropical</label>
              <label class="checkbox-option"><input type="checkbox" id="juice-flavour-other-check" name="juice_flavours" value="Other"> Other</label>
            </div>
          </fieldset>

          <div class="form-group conditional-field" id="juice-flavour-other-group" hidden>
            <label for="juice-flavour-other-text">Tell Us the Flavour</label>
            <input type="text" id="juice-flavour-other-text" name="juice_flavour_other">
          </div>

          <div class="form-group">
            <label for="juice-date">Date Needed</label>
            <input type="date" id="juice-date" name="juice_date_needed">
          </div>

          <fieldset class="field-fieldset">
            <legend>Delivery or Collection</legend>
            <div class="radio-row">
              <label class="radio-option"><input type="radio" name="juice_delivery" value="Delivery"> Delivery</label>
              <label class="radio-option"><input type="radio" name="juice_delivery" value="Collection"> Collection</label>
            </div>
          </fieldset>
        </div>

        <!-- CAKES & CELEBRATION FOODS FIELDS -->
        <div class="order-fields" data-service-fields="cakes" hidden>
          <div class="form-group">
            <label for="cake-occasion">Occasion Type</label>
            <select id="cake-occasion" name="cake_occasion">
              <option value="" disabled selected>Select an occasion</option>
              <option value="Birthday">Birthday</option>
              <option value="Wedding">Wedding</option>
              <option value="Graduation">Graduation</option>
              <option value="Baby Shower">Baby Shower</option>
              <option value="Other">Other</option>
            </select>
          </div>

          <div class="form-group">
            <label for="cake-size">Cake Size</label>
            <select id="cake-size" name="cake_size">
              <option value="" disabled selected>Select a size</option>
              <option value="1kg">1kg</option>
              <option value="2kg">2kg</option>
              <option value="3kg">3kg</option>
              <option value="4-tier">4-tier</option>
              <option value="Custom">Custom</option>
            </select>
          </div>

          <div class="form-group conditional-field" id="cake-size-custom-group" hidden>
            <label for="cake-size-custom-notes">Tell Us More About the Size You Need</label>
            <textarea id="cake-size-custom-notes" name="cake_size_custom_notes" rows="2"></textarea>
          </div>

          <fieldset class="field-fieldset">
            <legend>Cake Flavour</legend>
            <div class="checkbox-grid" id="cake-flavour-group">
              <label class="checkbox-option"><input type="checkbox" name="cake_flavour" value="Vanilla"> Vanilla</label>
              <label class="checkbox-option"><input type="checkbox" name="cake_flavour" value="Chocolate"> Chocolate</label>
              <label class="checkbox-option"><input type="checkbox" name="cake_flavour" value="Red Velvet"> Red Velvet</label>
              <label class="checkbox-option"><input type="checkbox" name="cake_flavour" value="Lemon"> Lemon</label>
              <label class="checkbox-option"><input type="checkbox" name="cake_flavour" value="Marble"> Marble</label>
              <label class="checkbox-option"><input type="checkbox" id="cake-flavour-other-check" name="cake_flavour" value="Other"> Other</label>
            </div>
          </fieldset>

          <div class="form-group conditional-field" id="cake-flavour-other-group" hidden>
            <label for="cake-flavour-other-text">Tell Us the Flavour</label>
            <input type="text" id="cake-flavour-other-text" name="cake_flavour_other">
          </div>

          <div class="form-group">
            <label for="cake-decoration-notes">Decoration Notes</label>
            <textarea id="cake-decoration-notes" name="cake_decoration_notes" rows="3"></textarea>
          </div>

          <div class="form-group">
            <label for="cake-date">Date Needed</label>
            <input type="date" id="cake-date" name="cake_date_needed">
          </div>
        </div>

        <div class="form-group">
          <label for="order-referral">How did you hear about us?</label>
          <select id="order-referral" name="referral_source">
            <option value="" disabled selected>Select an option</option>
            <option value="WhatsApp">WhatsApp</option>
            <option value="Social Media">Social Media</option>
            <option value="Friend or Family">Friend or Family</option>
            <option value="Church">Church</option>
            <option value="Google">Google</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <button type="submit" class="btn btn-gold btn-form-submit">Place Order</button>

        <p class="form-message" id="order-form-message" role="status" aria-live="polite"></p>
      </form>
    </div>
  </section>

  <!-- ============================================
       SECTION 9: FEEDBACK FORM
       ============================================ -->
  <section class="feedback" id="feedback">
    <img class="feedback-bg media-photo" src="images/form-bg.webp" alt="" loading="lazy">
    <div class="feedback-overlay"></div>
    <div class="container feedback-grid">
      <div class="feedback-intro">
        <h2 class="section-heading section-heading-left">Share Your Experience</h2>
        <p>We love hearing from the people we have served. If PMB catered your event, we would be grateful to know how we did. Your feedback helps us grow and serve Mombasa better.</p>
      </div>

      <form class="feedback-form" id="feedback-form" novalidate>
        <div class="form-group">
          <label for="name">Your Name</label>
          <input type="text" id="name" name="name" required autocomplete="name">
        </div>

        <div class="form-group">
          <label for="phone">Your Phone Number</label>
          <input type="tel" id="phone" name="phone" required autocomplete="tel">
        </div>

        <div class="form-group">
          <label for="event-type">Type of Event</label>
          <select id="event-type" name="event_type" required>
            <option value="" disabled selected>Select an event type</option>
            <option value="Church Event">Church Event</option>
            <option value="School Event">School Event</option>
            <option value="Corporate Event">Corporate Event</option>
            <option value="Wedding">Wedding</option>
            <option value="Birthday">Birthday</option>
            <option value="Graduation">Graduation</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="form-group">
          <label for="experience">Your Experience</label>
          <textarea id="experience" name="experience" rows="4" placeholder="Tell us about your experience with PMB..." required></textarea>
        </div>

        <fieldset class="form-group star-rating-group">
          <legend>Star Rating</legend>
          <div class="star-rating" id="star-rating">
            <input type="radio" id="star5" name="rating" value="5" required><label for="star5" aria-label="5 stars">★</label>
            <input type="radio" id="star4" name="rating" value="4"><label for="star4" aria-label="4 stars">★</label>
            <input type="radio" id="star3" name="rating" value="3"><label for="star3" aria-label="3 stars">★</label>
            <input type="radio" id="star2" name="rating" value="2"><label for="star2" aria-label="2 stars">★</label>
            <input type="radio" id="star1" name="rating" value="1"><label for="star1" aria-label="1 star">★</label>
          </div>
        </fieldset>

        <button type="submit" class="btn btn-gold btn-form-submit">Send Feedback</button>

        <p class="form-message" id="form-message" role="status" aria-live="polite"></p>
      </form>
    </div>
  </section>
@endsection

