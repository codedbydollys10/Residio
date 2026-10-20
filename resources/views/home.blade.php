@extends('layouts.marketing')

@section('title', 'Residio — Run your society from one place')

@section('content')
@php
    $proofTypes = [
        ['icon' => 'bi-shield-check', 'label' => 'Gated societies'],
        ['icon' => 'bi-buildings', 'label' => 'Apartment complexes'],
        ['icon' => 'bi-houses', 'label' => 'Townships'],
        ['icon' => 'bi-briefcase', 'label' => 'Property managers'],
    ];
    $features = [
        ['icon' => 'bi-qr-code-scan', 'title' => 'Visitor management', 'description' => 'Issue QR visitor passes and keep the gate team in sync with a dedicated guard console.'],
        ['icon' => 'bi-exclamation-diamond', 'title' => 'Complaints with context', 'description' => 'Track requests with assignment, service-level targets and a clear activity timeline.'],
        ['icon' => 'bi-receipt', 'title' => 'Maintenance billing', 'description' => 'Create recurring bills with configurable GST, late fees, invoices and receipts.'],
        ['icon' => 'bi-journal-check', 'title' => 'Double-entry accounting', 'description' => 'Keep balanced ledgers and prepare trial balance, profit & loss and balance sheet reports.'],
        ['icon' => 'bi-calendar2-check', 'title' => 'Facility booking', 'description' => 'Manage shared spaces, time slots, charges and booking approvals in one calendar.'],
        ['icon' => 'bi-megaphone', 'title' => 'Notice board and polls', 'description' => 'Publish announcements and events, then collect resident votes in the same place.'],
    ];
    $strengths = [
        ['icon' => 'bi-diagram-3', 'title' => 'Row-level tenant isolation', 'copy' => 'Society data is separated by tenant in the shared database.'],
        ['icon' => 'bi-person-lock', 'title' => 'Dynamic access control', 'copy' => 'Role-based permissions can be managed with a per-society matrix.'],
        ['icon' => 'bi-shield-lock', 'title' => 'Security built in', 'copy' => 'Two-factor authentication, audit trails and login history.'],
        ['icon' => 'bi-phone', 'title' => 'Connected platform', 'copy' => 'Sanctum REST API and queued email, SMS, WhatsApp and push notifications.'],
    ];
    $steps = [
        ['title' => 'Register your society', 'copy' => 'Set up your community workspace and choose a plan.'],
        ['title' => 'Add your community', 'copy' => 'Create towers, flats and resident records.'],
        ['title' => 'Go live', 'copy' => 'Start managing billing, visitors, notices and daily operations.'],
    ];
    $faqs = [
        ['question' => 'How is each society’s data kept separate?', 'answer' => 'Residio is designed as a multi-tenant platform. Society records are associated with a tenant, and tenant-aware query scopes help keep one community’s records separate from another’s.'],
        ['question' => 'What is included in the free trial?', 'answer' => 'The configured Free Trial plan includes a 14-day trial with access to the plan features and limits shown below. No payment card is required to explore the product.'],
        ['question' => 'Who can access the different parts of the platform?', 'answer' => 'The platform includes configurable roles such as society administrators, accountants, security guards, maintenance staff, residents, tenants and vendors. Permissions determine what each role can do.'],
        ['question' => 'Is there a mobile app?', 'answer' => 'Residio includes a Sanctum-powered REST API and has a companion Flutter mobile app. Availability and setup may depend on your deployment.'],
        ['question' => 'Can residents pay bills online?', 'answer' => 'Maintenance bills and payment records can be managed in the platform. Online payment collection requires configuring and integrating a payment gateway for your deployment.'],
        ['question' => 'Can we migrate existing society records?', 'answer' => 'Existing records can be imported as part of a planned onboarding. The import format and migration approach should be confirmed against your current data before loading it.'],
    ];
@endphp

<header class="home-header" id="homeHeader">
    <nav class="home-navbar container" aria-label="Main navigation">
        <a class="home-brand" href="{{ route('home') }}" aria-label="Residio home">
            <span class="home-brand-mark"><i class="bi bi-buildings-fill" aria-hidden="true"></i></span>
            <span>Residio</span>
        </a>
        <div class="home-nav-links d-none d-lg-flex">
            <a href="#features">Features</a>
            <a href="#modules">Modules</a>
            <a href="#roles">Roles</a>
            <a href="#pricing">Pricing</a>
            <a href="#faq">FAQ</a>
        </div>
        <div class="home-nav-actions d-none d-lg-flex">
            <button class="home-theme-toggle" id="homeThemeToggle" type="button" aria-label="Toggle light or dark theme">
                <i class="bi bi-sun-fill" data-home-theme-icon="light" aria-hidden="true"></i>
                <i class="bi bi-moon-stars-fill" data-home-theme-icon="dark" aria-hidden="true"></i>
            </button>
            @auth
                <a class="home-button home-button-ghost" href="{{ route('login') }}">Sign in</a>
                <a class="home-button home-button-primary" href="{{ route('dashboard') }}">Go to dashboard</a>
            @else
                <a class="home-button home-button-ghost" href="{{ route('resident.login') }}">Resident sign in</a>
                <a class="home-button home-button-ghost" href="{{ route('resident.register') }}">Resident registration</a>
                <a class="home-button home-button-ghost" href="{{ route('login') }}">Sign in</a>
                <a class="home-button home-button-primary" href="{{ route('register') }}">Start free trial</a>
            @endauth
        </div>
        <button class="home-menu-button d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#homeMobileMenu" aria-controls="homeMobileMenu" aria-label="Open navigation menu">
            <i class="bi bi-list" aria-hidden="true"></i>
        </button>
    </nav>
</header>

<div class="offcanvas offcanvas-end home-offcanvas" tabindex="-1" id="homeMobileMenu" aria-labelledby="homeMobileMenuLabel">
    <div class="offcanvas-header">
        <a class="home-brand" href="{{ route('home') }}" id="homeMobileMenuLabel">
            <span class="home-brand-mark"><i class="bi bi-buildings-fill" aria-hidden="true"></i></span>
            <span>Residio</span>
        </a>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close menu"></button>
    </div>
    <div class="offcanvas-body">
        <nav class="home-mobile-links" aria-label="Mobile navigation">
            <a href="#features">Features</a>
            <a href="#modules">Modules</a>
            <a href="#roles">Roles</a>
            <a href="#pricing">Pricing</a>
            <a href="#faq">FAQ</a>
        </nav>
        <button class="home-theme-toggle home-mobile-theme" id="homeThemeToggleMobile" type="button" aria-label="Toggle light or dark theme">
            <i class="bi bi-sun-fill" data-home-theme-icon="light" aria-hidden="true"></i>
            <i class="bi bi-moon-stars-fill" data-home-theme-icon="dark" aria-hidden="true"></i>
            <span>Toggle theme</span>
        </button>
        <div class="d-grid gap-2 mt-4">
            @auth
                <a class="home-button home-button-ghost" href="{{ route('login') }}">Sign in</a>
                <a class="home-button home-button-primary" href="{{ route('dashboard') }}">Go to dashboard</a>
            @else
                <a class="home-button home-button-ghost" href="{{ route('resident.login') }}">Resident sign in</a>
                <a class="home-button home-button-ghost" href="{{ route('resident.register') }}">Resident registration</a>
                <a class="home-button home-button-ghost" href="{{ route('login') }}">Sign in</a>
                <a class="home-button home-button-primary" href="{{ route('register') }}">Start free trial</a>
            @endauth
        </div>
    </div>
</div>

<main>
    <section class="home-hero" aria-labelledby="homeHeroTitle">
        <video class="home-hero-video" aria-hidden="true" autoplay muted loop playsinline preload="metadata">
            <source src="{{ asset('videos/communityos-hero.mp4') }}" type="video/mp4">
        </video>
        <div class="home-container home-hero-grid">
            <div class="home-hero-copy home-reveal">
                <span class="home-eyebrow"><i class="bi bi-stars" aria-hidden="true"></i> Society management, made simpler</span>
                <h1 id="homeHeroTitle">Run your entire society <span>from one place.</span></h1>
                <p class="home-hero-subtitle">Visitors, complaints, billing, accounting, facilities and communication — all in one multi-tenant platform built for the way communities work.</p>
                <div class="home-hero-actions">
                    @auth
                        <a class="home-button home-button-primary home-button-large" href="{{ route('dashboard') }}">Go to dashboard <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    @else
                        <a class="home-button home-button-primary home-button-large" href="{{ route('register') }}">Start free trial <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                        <a class="home-button home-button-ghost home-button-large" href="{{ route('login') }}">Sign in</a>
                        <a class="home-button home-button-ghost home-button-large" href="{{ route('resident.login') }}">Resident sign in</a>
                        <a class="home-button home-button-ghost home-button-large" href="{{ route('resident.register') }}">Resident registration</a>
                    @endauth
                </div>
                <p class="home-trust-line"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> 14-day full-feature trial <span aria-hidden="true">·</span> No card required</p>
            </div>

            <div class="home-hero-visual home-reveal" aria-label="Illustrative Residio dashboard preview">
                <div class="home-dashboard-glow" aria-hidden="true"></div>
                <div class="home-dashboard-grid" aria-hidden="true"></div>
                <div class="home-dashboard">
                    <div class="home-dashboard-topbar">
                        <div class="home-dashboard-brand"><span><i class="bi bi-buildings-fill" aria-hidden="true"></i></span> Residio <small>OVERVIEW</small></div>
                        <span class="home-demo-label"><i class="bi bi-circle-fill" aria-hidden="true"></i> Sample dashboard</span>
                    </div>
                    <div class="home-dashboard-welcome">
                        <div><small>MONDAY, OCTOBER 20</small><h2>Good morning, Alex</h2></div>
                        <span class="home-dashboard-avatar" aria-hidden="true">A</span>
                    </div>
                    <div class="home-stat-grid">
                        <article class="home-stat-card"><span class="home-stat-icon home-stat-blue"><i class="bi bi-currency-rupee" aria-hidden="true"></i></span><small>Dues collected</small><strong>₹8.42L</strong><span class="home-stat-positive"><i class="bi bi-arrow-up-right" aria-hidden="true"></i> This month</span></article>
                        <article class="home-stat-card"><span class="home-stat-icon home-stat-amber"><i class="bi bi-exclamation-circle" aria-hidden="true"></i></span><small>Open complaints</small><strong>12</strong><span class="home-stat-muted">Needs attention</span></article>
                        <article class="home-stat-card"><span class="home-stat-icon home-stat-green"><i class="bi bi-person-walking" aria-hidden="true"></i></span><small>Visitors today</small><strong>34</strong><span class="home-stat-muted">Gate activity</span></article>
                        <article class="home-stat-card"><span class="home-stat-icon home-stat-purple"><i class="bi bi-calendar2-check" aria-hidden="true"></i></span><small>Facility bookings</small><strong>8</strong><span class="home-stat-muted">Upcoming</span></article>
                    </div>
                    <div class="home-dashboard-chart">
                        <div class="home-chart-heading"><div><strong>Monthly collection</strong><small>Illustrative sample data</small></div><span>Last 6 months <i class="bi bi-chevron-down" aria-hidden="true"></i></span></div>
                        <div class="home-chart-canvas"><canvas id="homeCollectionChart" role="img" aria-label="Illustrative line chart showing sample monthly collections"></canvas></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="home-proof" aria-label="Communities Residio is built for">
        <div class="home-container home-proof-inner">
            <span class="home-proof-label">Built for</span>
            @foreach ($proofTypes as $proof)
                <span class="home-proof-chip"><i class="bi {{ $proof['icon'] }}" aria-hidden="true"></i>{{ $proof['label'] }}</span>
            @endforeach
        </div>
    </section>

    <section class="home-section" id="features" aria-labelledby="homeFeaturesTitle">
        <div class="home-container">
            <div class="home-section-heading home-reveal">
                <span class="home-eyebrow">Less chasing. More community.</span>
                <h2 id="homeFeaturesTitle">The everyday essentials, <span>working together.</span></h2>
                <p>Bring the moving parts of society management into one clear, connected workspace.</p>
            </div>
            <div class="home-feature-grid">
                @foreach ($features as $feature)
                    <article class="home-feature-card home-reveal">
                        <span class="home-icon-tile"><i class="bi {{ $feature['icon'] }}" aria-hidden="true"></i></span>
                        <h3>{{ $feature['title'] }}</h3>
                        <p>{{ $feature['description'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-section home-section-soft" id="modules" aria-labelledby="homeModulesTitle">
        <div class="home-container">
            <div class="home-section-heading home-reveal">
                <span class="home-eyebrow">One connected platform</span>
                <h2 id="homeModulesTitle">Everything your community needs.</h2>
                <p>Explore the modules that bring your society’s daily work into a single workspace.</p>
            </div>
            <div class="home-module-tabs home-reveal" role="tablist" aria-label="Residio module groups">
                @foreach ($moduleGroups as $group => $modules)
                    <button class="home-module-tab{{ $loop->first ? ' is-active' : '' }}" id="module-tab-{{ \Illuminate\Support\Str::slug($group) }}" type="button" role="tab" aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="module-panel-{{ \Illuminate\Support\Str::slug($group) }}" data-module-tab="{{ \Illuminate\Support\Str::slug($group) }}">{{ $group }}</button>
                @endforeach
            </div>
            @foreach ($moduleGroups as $group => $modules)
                <div class="home-module-panel{{ $loop->first ? ' is-active' : '' }}" id="module-panel-{{ \Illuminate\Support\Str::slug($group) }}" role="tabpanel" aria-labelledby="module-tab-{{ \Illuminate\Support\Str::slug($group) }}" data-module-panel="{{ \Illuminate\Support\Str::slug($group) }}" @if (!$loop->first) hidden @endif>
                    <div class="home-module-grid">
                        @foreach ($modules as $module)
                            <div class="home-module-item"><i class="bi {{ $module['icon'] }}" aria-hidden="true"></i><span>{{ $module['name'] }}</span></div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <section class="home-section" id="roles" aria-labelledby="homeRolesTitle">
        <div class="home-container">
            <div class="home-section-heading home-reveal">
                <span class="home-eyebrow">Clear roles, smoother days</span>
                <h2 id="homeRolesTitle">A workspace for everyone in the society.</h2>
                <p>Give each person a focused view of the work they need to get done.</p>
            </div>
            <div class="home-role-grid">
                @foreach ($roles as $role)
                    @php($roleDetails = config('communityos.roles.'.$role['key']))
                    <article class="home-role-card home-reveal">
                        <span class="home-role-icon"><i class="bi {{ ['society-admin' => 'bi-person-gear', 'accountant' => 'bi-calculator', 'security-guard' => 'bi-shield-check', 'maintenance-staff' => 'bi-tools', 'resident' => 'bi-house-heart', 'vendor' => 'bi-truck'][$role['key']] }}" aria-hidden="true"></i></span>
                        <h3>{{ $role['name'] ?? $roleDetails['name'] }}</h3>
                        <p class="home-role-summary">{{ $role['description'] ?? $roleDetails['description'] }}</p>
                        <p class="home-role-daily">{{ $role['daily'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-strength" aria-labelledby="homeStrengthTitle">
        <div class="home-container">
            <div class="home-strength-heading home-reveal">
                <span class="home-eyebrow">Built for trust</span>
                <h2 id="homeStrengthTitle">A stronger foundation for every community.</h2>
                <p>Thoughtful controls and connected technology help your team work with confidence.</p>
            </div>
            <div class="home-strength-grid">
                @foreach ($strengths as $strength)
                    <article class="home-strength-item home-reveal">
                        <i class="bi {{ $strength['icon'] }}" aria-hidden="true"></i>
                        <h3>{{ $strength['title'] }}</h3>
                        <p>{{ $strength['copy'] }}</p>
                    </article>
                @endforeach
            </div>
            <p class="home-mobile-note home-reveal"><i class="bi bi-phone" aria-hidden="true"></i> A companion Flutter mobile app connects your community on the go.</p>
        </div>
    </section>

    <section class="home-section home-section-soft" id="how-it-works" aria-labelledby="homeStepsTitle">
        <div class="home-container">
            <div class="home-section-heading home-reveal">
                <span class="home-eyebrow">A simple start</span>
                <h2 id="homeStepsTitle">From setup to a smoother society.</h2>
            </div>
            <div class="home-steps">
                @foreach ($steps as $step)
                    <article class="home-step home-reveal">
                        <span class="home-step-number">{{ $loop->iteration }}</span>
                        <h3>{{ $step['title'] }}</h3>
                        <p>{{ $step['copy'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-section" id="pricing" aria-labelledby="homePricingTitle">
        <div class="home-container">
            <div class="home-section-heading home-reveal">
                <span class="home-eyebrow">Plans that grow with you</span>
                <h2 id="homePricingTitle">Straightforward plans for your community.</h2>
                <p>Compare the currently available plans and choose the right fit.</p>
            </div>
            @if ($plans->isNotEmpty())
                <div class="home-pricing-grid">
                    @foreach ($plans as $plan)
                        <article class="home-plan-card{{ $plan->is_featured ? ' is-featured' : '' }} home-reveal">
                            @if ($plan->is_featured)
                                <span class="home-plan-ribbon">Most popular</span>
                            @endif
                            <div class="home-plan-header">
                                <h3>{{ $plan->name }}</h3>
                                <p>{{ $plan->description }}</p>
                            </div>
                            <div class="home-plan-price">
                                <span class="home-plan-currency">₹</span><strong>{{ number_format((float) $plan->price, 0) }}</strong>
                                <span class="home-plan-cycle">/ {{ $plan->billing_cycle }}</span>
                            </div>
                            @if ($plan->trial_days > 0)
                                <p class="home-plan-trial">{{ $plan->trial_days }}-day trial</p>
                            @endif
                            <ul class="home-plan-limits">
                                <li><i class="bi bi-check2" aria-hidden="true"></i>{{ $plan->max_units === null ? 'Unlimited' : number_format($plan->max_units) }} units</li>
                                <li><i class="bi bi-check2" aria-hidden="true"></i>{{ $plan->max_users === null ? 'Unlimited' : number_format($plan->max_users) }} users</li>
                                <li><i class="bi bi-check2" aria-hidden="true"></i>{{ $plan->max_storage_mb === null ? 'Unlimited' : number_format($plan->max_storage_mb).' MB' }} storage</li>
                            </ul>
                            <div class="home-plan-features">
                                <p>Included features</p>
                                @forelse ((array) $plan->features as $feature)
                                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i>{{ \Illuminate\Support\Str::headline($feature) }}</span>
                                @empty
                                    <span><i class="bi bi-check-circle-fill" aria-hidden="true"></i>Core society management</span>
                                @endforelse
                            </div>
                            <a class="home-button {{ $plan->is_featured ? 'home-button-primary' : 'home-button-outline' }} home-plan-cta" href="{{ route('register', ['plan' => $plan->slug]) }}">Choose {{ $plan->name }}</a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="home-empty-plans"><i class="bi bi-info-circle" aria-hidden="true"></i><p>Plans are being prepared. Please check back soon.</p></div>
            @endif
        </div>
    </section>

    <section class="home-section home-section-soft" id="faq" aria-labelledby="homeFaqTitle">
        <div class="home-container home-faq-container">
            <div class="home-section-heading home-reveal">
                <span class="home-eyebrow">Good to know</span>
                <h2 id="homeFaqTitle">Frequently asked questions.</h2>
            </div>
            <div class="accordion home-accordion home-reveal" id="homeFaqAccordion">
                @foreach ($faqs as $faq)
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="homeFaqHeading{{ $loop->iteration }}">
                            <button class="accordion-button{{ $loop->first ? '' : ' collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#homeFaqAnswer{{ $loop->iteration }}" aria-expanded="{{ $loop->first ? 'true' : 'false' }}" aria-controls="homeFaqAnswer{{ $loop->iteration }}">{{ $faq['question'] }}</button>
                        </h3>
                        <div id="homeFaqAnswer{{ $loop->iteration }}" class="accordion-collapse collapse{{ $loop->first ? ' show' : '' }}" aria-labelledby="homeFaqHeading{{ $loop->iteration }}" data-bs-parent="#homeFaqAccordion">
                            <div class="accordion-body">{{ $faq['answer'] }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <section class="home-final-cta" aria-labelledby="homeFinalTitle">
        <div class="home-container home-final-inner home-reveal">
            <div>
                <span class="home-eyebrow">Ready when you are</span>
                <h2 id="homeFinalTitle">Bring your society online today.</h2>
                <p>Make the daily work of managing your community feel a little more connected.</p>
            </div>
            <div class="home-final-actions">
                @auth
                    <a class="home-button home-button-light home-button-large" href="{{ route('dashboard') }}">Go to dashboard</a>
                @else
                    <a class="home-button home-button-light home-button-large" href="{{ route('register') }}">Start free trial</a>
                    <a class="home-button home-button-transparent home-button-large" href="{{ route('login') }}">Sign in</a>
                @endauth
            </div>
        </div>
    </section>
</main>

<footer class="home-footer">
    <div class="home-container">
        <div class="home-footer-grid">
            <div class="home-footer-brand">
                <a class="home-brand" href="{{ route('home') }}"><span class="home-brand-mark"><i class="bi bi-buildings-fill" aria-hidden="true"></i></span><span>Residio</span></a>
                <p>One connected workspace for the people and operations that keep communities running.</p>
                <span class="home-credit">Designed and built by Rahul Padol</span>
            </div>
            <div class="home-footer-column"><h2>Product</h2><a href="#features">Features</a><a href="#modules">Modules</a><a href="#roles">Roles</a><a href="#pricing">Pricing</a></div>
            <div class="home-footer-column"><h2>Company</h2><a href="#how-it-works">How it works</a><a href="#faq">FAQ</a><a href="{{ route('register') }}">Get started</a></div>
            <div class="home-footer-column"><h2>Account & legal</h2><a href="{{ route('login') }}">Sign in</a><a href="{{ route('register') }}">Create an account</a><a href="#faq">Privacy & security</a></div>
        </div>
        <div class="home-footer-bottom"><span>© {{ date('Y') }} Residio. All rights reserved.</span><a href="#homeHeader">Back to top <i class="bi bi-arrow-up" aria-hidden="true"></i></a></div>
    </div>
</footer>
@endsection
