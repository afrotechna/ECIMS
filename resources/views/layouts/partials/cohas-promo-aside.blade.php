<aside class="auth-promo" aria-label="College information">
    <div class="auth-promo-brand">
        <div class="logo-wrap">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
        </div>
        <div class="brand-text">{{ config('college.institution_name', config('app.name')) }}</div>
    </div>

    <div class="auth-promo-inner">
        <div class="auth-promo-slide active" data-slide="0">
            <div class="auth-promo-copy">
                <h2>Semester registration</h2>
                <p class="lead-line">Register for the current semester, view fees, and track your approval status online.</p>
                <div class="auth-deadline-label">Academic year</div>
                <div class="auth-deadline-box">{{ now()->month >= 7 ? now()->year.'/'.(now()->year + 1) : (now()->year - 1).'/'.now()->year }}</div>
                <a href="{{ $promoCtaHref ?? '#login-form' }}" class="auth-cta-slat"><span>{{ $promoCtaLabel ?? 'Sign in to register' }}</span></a>
            </div>
            <div class="auth-promo-visual">
                <div class="photo-frame">
                    <div class="photo-inner" style="--photo-inner-bg: url('https://images.unsplash.com/photo-1624727828489-a1e03b79bba8?auto=format&amp;fit=crop&amp;w=800&amp;q=70')"><i class="bi bi-person-check"></i></div>
                </div>
            </div>
        </div>

        <div class="auth-promo-slide" data-slide="1">
            <div class="auth-promo-copy">
                <h2>Academic &amp; clinical records</h2>
                <p class="lead-line">Integrated management for programmes, rotations, results, and college operations.</p>
                <ul class="auth-promo-features">
                    <li><i class="bi bi-check-circle-fill"></i> Student records &amp; transcripts</li>
                    <li><i class="bi bi-check-circle-fill"></i> Continuous assessment &amp; exams</li>
                    <li><i class="bi bi-check-circle-fill"></i> Clinical rotation scheduling</li>
                </ul>
                <a href="{{ $promoCtaHref ?? '#login-form' }}" class="auth-cta-slat"><span>{{ $promoCtaLabel ?? 'Sign in to continue' }}</span></a>
            </div>
            <div class="auth-promo-visual">
                <div class="photo-frame">
                    <div class="photo-inner" style="--photo-inner-bg: url('https://images.unsplash.com/photo-1672655412906-8e10ba6ee373?auto=format&amp;fit=crop&amp;w=800&amp;q=70')"><i class="bi bi-hospital"></i></div>
                </div>
            </div>
        </div>

        <div class="auth-promo-slide" data-slide="2">
            <div class="auth-promo-copy">
                <h2>Finance &amp; accommodation</h2>
                <p class="lead-line">Fee payments, receipts, hostel allocation, and semester clearance in one place.</p>
                <ul class="auth-promo-features">
                    <li><i class="bi bi-check-circle-fill"></i> Online payment tracking</li>
                    <li><i class="bi bi-check-circle-fill"></i> Hostel room allocation</li>
                    <li><i class="bi bi-check-circle-fill"></i> Official college documents</li>
                </ul>
                <a href="{{ $promoCtaHref ?? '#login-form' }}" class="auth-cta-slat"><span>{{ $promoCtaLabel ?? 'Sign in now' }}</span></a>
            </div>
            <div class="auth-promo-visual">
                <div class="photo-frame">
                    <div class="photo-inner" style="--photo-inner-bg: url('https://images.unsplash.com/photo-1549057446-9f5c6ac91a04?auto=format&amp;fit=crop&amp;w=800&amp;q=70')"><i class="bi bi-wallet2"></i></div>
                </div>
            </div>
        </div>
    </div>

    <div class="auth-promo-contact">
        <a href="mailto:info@musomacohas.ac.tz"><i class="bi bi-envelope"></i> info@musomacohas.ac.tz</a>
        <a href="tel:+255000000000"><i class="bi bi-telephone"></i> +255 28 262 0000</a>
        <a href="#"><i class="bi bi-globe2"></i> www.musomacohas.ac.tz</a>
    </div>

    <div class="auth-carousel-dots" role="tablist" aria-label="Promotional slides">
        <button type="button" class="active" data-go="0" aria-label="Slide 1"></button>
        <button type="button" data-go="1" aria-label="Slide 2"></button>
        <button type="button" data-go="2" aria-label="Slide 3"></button>
    </div>
</aside>
