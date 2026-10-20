<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <script>(function(){try{document.documentElement.setAttribute('data-bs-theme',localStorage.getItem('co-theme')||'dark');}catch(e){}})();</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Residio brings society operations, resident services and finances together in one secure management platform.">
    <meta name="theme-color" content="#2563eb">
    <meta property="og:type" content="website">
    <meta property="og:title" content="Residio — Society management, all in one place">
    <meta property="og:description" content="Visitors, complaints, billing, accounting and community communication in one platform.">
    <meta property="og:url" content="{{ route('home') }}">
    <link rel="canonical" href="{{ route('home') }}">
    <title>@yield('title', 'Residio — Society management, all in one place')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/home.css') }}?v={{ @filemtime(public_path('css/home.css')) }}" rel="stylesheet">
</head>
<body class="home-body">
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
    <script src="{{ asset('js/home.js') }}" defer></script>
</body>
</html>
