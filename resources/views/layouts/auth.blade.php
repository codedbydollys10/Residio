<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Sign in') — {{ config('app.name') }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}" rel="stylesheet">
    <link href="{{ asset('css/auth.css') }}?v={{ @filemtime(public_path('css/auth.css')) }}" rel="stylesheet">
</head>
<body class="auth-body d-flex align-items-center justify-content-center min-vh-100 bg-light @yield('body-class')">
    <video class="auth-background-video" aria-hidden="true" autoplay muted loop playsinline preload="metadata">
        <source src="{{ asset('videos/communityos-hero.mp4') }}" type="video/mp4">
    </video>
    <div class="auth-background-overlay" aria-hidden="true"></div>
    <div class="auth-card card shadow-sm border-0" style="max-width: @yield('card-width', '480px'); width: 100%;">
        <div class="card-body p-4 p-md-5">
            <a href="{{ route('home') }}" class="d-inline-flex align-items-center gap-2 text-decoration-none small mb-4">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to home
            </a>
            <div class="text-center mb-4">
                <i class="bi bi-buildings-fill fs-1 text-primary"></i>
                <h1 class="h4 mt-2 mb-0">{{ config('app.name') }}</h1>
                <p class="text-muted small">Society & Apartment Management</p>
            </div>

            @include('partials.flash')

            @yield('content')
        </div>
    </div>
    <script>
        document.addEventListener('click', (event) => {
            const button = event.target.closest('[data-password-toggle]');
            if (!button) {
                return;
            }

            const input = button.parentElement.querySelector('input');
            const icon = button.querySelector('i');
            const showPassword = input.type === 'password';

            input.type = showPassword ? 'text' : 'password';
            button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
            button.setAttribute('aria-pressed', String(showPassword));
            icon.classList.toggle('bi-eye', !showPassword);
            icon.classList.toggle('bi-eye-slash', showPassword);
        });
    </script>
</body>
</html>
