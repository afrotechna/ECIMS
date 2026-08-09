<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} — @yield('title', 'Sign in')</title>
    <script>
    (function(){try{var t=localStorage.getItem('cohas-theme');if(t!=='light'&&t!=='dark'){t=window.matchMedia&&window.matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}document.documentElement.setAttribute('data-theme',t);document.documentElement.setAttribute('data-bs-theme',t);}catch(e){}})();
    </script>
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    @include('layouts.partials.cohas-auth-styles')
    <link href="{{ asset('css/cohas-customizer.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-fonts.css') }}" rel="stylesheet">
    @stack('styles')
</head>
<body class="cohas-split-page">
    @include('layouts.partials.cohas-page-loader')
    <div class="auth-split">
        @include('layouts.partials.cohas-promo-aside')

        <main class="auth-main" role="main">
            <div class="auth-main-shell">
                <header class="auth-main-top">
                    <a href="{{ route('home') }}" class="auth-home-link">
                        <i class="bi bi-house-door" aria-hidden="true"></i>
                        <span>Home</span>
                    </a>
                    <div class="auth-main-tools">
                        @include('layouts.partials.customizer-toggle')
                        @include('layouts.partials.language-toggle')
                    </div>
                </header>

                <section class="auth-login-card" aria-labelledby="login-form-title">
                    @include('layouts.partials.maintenance-banner')
                    @yield('content')
                </section>

                <footer class="auth-main-footer" role="contentinfo">
                    Copyright © {{ now()->year }} {{ config('app.name') }}. Powered by AfroTechna Group <span class="app-footer-version">· v{{ config('app.version') }}</span>
                </footer>
            </div>
        </main>
    </div>
    @include('layouts.partials.customizer')

    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('js/cohas-theme.js') }}"></script>
    <script src="{{ asset('js/cohas-customizer.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert2/sweetalert2.all.min.js') }}"></script>
    @include('layouts.partials.cohas-promo-carousel-script')
    @include('layouts.partials.cohas-loader-script')
    @stack('scripts')
</body>
</html>
