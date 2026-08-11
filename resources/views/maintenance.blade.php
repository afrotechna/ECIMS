<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — Under maintenance</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap-icons/font/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-brand.css') }}" rel="stylesheet">
    <link href="{{ asset('css/cohas-fonts.css') }}" rel="stylesheet">
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }
        body.maintenance-page {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--cohas-gradient) !important;
            color: #fff;
            padding: 1rem 1.5rem;
            overflow: hidden;
        }
        .maintenance-card {
            width: 100%;
            max-width: 480px;
            max-height: 100%;
            text-align: center;
            overflow-y: auto;
        }
        .maintenance-logo {
            width: clamp(48px, 8vh, 80px);
            height: clamp(48px, 8vh, 80px);
            border-radius: 50%;
            background: #fff;
            padding: 6px;
            margin: 0 auto .75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 28px rgba(0, 0, 0, .25);
        }
        .maintenance-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }
        .maintenance-card h1 {
            font-size: clamp(1.25rem, 3vh, 2.1rem);
            font-weight: 800;
            margin-bottom: .35rem;
        }
        .maintenance-card .lead {
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: .25rem;
        }
        .maintenance-card .tagline {
            font-size: .9375rem;
            color: rgba(255, 255, 255, .8);
            margin-bottom: .85rem;
        }
        .maintenance-schedule {
            display: flex;
            flex-wrap: wrap;
            gap: .5rem;
            justify-content: center;
            margin-bottom: .85rem;
        }
        .maintenance-schedule-item {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: .5rem;
            padding: .45rem .85rem;
            font-size: .8rem;
        }
        .maintenance-countdown {
            margin-bottom: .85rem;
        }
        .maintenance-countdown-label {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: rgba(255, 255, 255, .7);
            margin-bottom: .35rem;
        }
        .maintenance-countdown-clock {
            display: inline-flex;
            gap: .5rem;
        }
        .maintenance-countdown-unit {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .3);
            border-radius: .5rem;
            padding: .4rem .7rem;
            min-width: 58px;
        }
        .maintenance-countdown-unit .value {
            display: block;
            font-size: 1.25rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
        }
        .maintenance-countdown-unit .unit {
            display: block;
            font-size: .65rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            color: rgba(255, 255, 255, .7);
        }
        .maintenance-retry {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            border: 2px solid #fff;
            color: #fff;
            background: transparent;
            padding: .5rem 1.3rem;
            border-radius: .35rem;
            font-weight: 700;
            font-size: .8rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            text-decoration: none;
            transition: background .15s, color .15s;
        }
        .maintenance-retry:hover {
            background: #fff;
            color: var(--cohas-blue-900);
        }
        .maintenance-footer {
            margin-top: 1.25rem;
            font-size: .75rem;
            color: rgba(255, 255, 255, .6);
        }
    </style>
</head>
<body class="maintenance-page">
    <div class="maintenance-card">
        <div class="maintenance-logo">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
        </div>
        <h1><i class="bi bi-cone-striped me-2"></i>Under maintenance</h1>
        <p class="lead">{{ $setting->title ?: 'Scheduled system maintenance' }}</p>
        <p class="tagline">{{ $setting->message ?: 'The system is temporarily unavailable while we perform scheduled maintenance. Please check back shortly.' }}</p>

        @if($setting->starts_at)
        <div class="maintenance-schedule">
            <div class="maintenance-schedule-item"><i class="bi bi-calendar-event me-1"></i>Started {{ $setting->starts_at->format('d M Y, H:i') }}</div>
            @if($setting->ends_at)
            <div class="maintenance-schedule-item"><i class="bi bi-calendar-check me-1"></i>Expected back {{ $setting->ends_at->format('d M Y, H:i') }}</div>
            @endif
        </div>
        @endif

        @if($setting->ends_at)
        <div class="maintenance-countdown">
            <div class="maintenance-countdown-label">Back online in</div>
            <div class="maintenance-countdown-clock" id="maintenanceCountdown" data-ends-at="{{ $setting->ends_at->toIso8601String() }}">
                <div class="maintenance-countdown-unit"><span class="value" data-unit="hours">00</span><span class="unit">Hrs</span></div>
                <div class="maintenance-countdown-unit"><span class="value" data-unit="minutes">00</span><span class="unit">Min</span></div>
                <div class="maintenance-countdown-unit"><span class="value" data-unit="seconds">00</span><span class="unit">Sec</span></div>
            </div>
        </div>
        @endif

        <div>
            <a href="{{ route('home') }}" class="maintenance-retry"><i class="bi bi-arrow-clockwise me-1"></i> Try again</a>
        </div>

        <div class="maintenance-footer">
            Copyright © {{ now()->year }} {{ config('app.name') }}. Powered by AfroTechna Group <span class="app-footer-version">· v{{ config('app.version') }}</span>
        </div>
    </div>

    <script>
    (function () {
        var el = document.getElementById('maintenanceCountdown');
        if (!el) return;
        var endsAt = new Date(el.getAttribute('data-ends-at')).getTime();
        var hoursEl = el.querySelector('[data-unit="hours"]');
        var minutesEl = el.querySelector('[data-unit="minutes"]');
        var secondsEl = el.querySelector('[data-unit="seconds"]');

        function pad(n) { return String(n).padStart(2, '0'); }

        function tick() {
            var diff = Math.max(0, endsAt - Date.now());
            var totalSeconds = Math.floor(diff / 1000);
            var hours = Math.floor(totalSeconds / 3600);
            var minutes = Math.floor((totalSeconds % 3600) / 60);
            var seconds = totalSeconds % 60;
            hoursEl.textContent = pad(hours);
            minutesEl.textContent = pad(minutes);
            secondsEl.textContent = pad(seconds);
            if (diff <= 0) {
                clearInterval(timer);
                window.location.reload();
            }
        }

        tick();
        var timer = setInterval(tick, 1000);
    })();
    </script>
</body>
</html>
