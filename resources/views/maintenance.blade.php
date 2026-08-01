@extends('layouts.landing')

@section('title', 'Under maintenance')

@section('content')
<div class="landing-welcome">
    <div class="logo-hero">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
    </div>
    <h1><i class="bi bi-cone-striped me-2"></i>Under maintenance</h1>
    <p class="lead">{{ $setting->title ?: 'Scheduled system maintenance' }}</p>
    <p class="tagline">{{ $setting->message ?: 'The system is temporarily unavailable while we perform scheduled maintenance. Please check back shortly.' }}</p>

    @if($setting->starts_at || $setting->ends_at)
    <div class="landing-pills">
        @if($setting->starts_at)
        <div class="landing-pill"><i class="bi bi-calendar-event"></i><span>Started {{ $setting->starts_at->format('d M Y, H:i') }}</span></div>
        @endif
        @if($setting->ends_at)
        <div class="landing-pill"><i class="bi bi-calendar-check"></i><span>Expected back {{ $setting->ends_at->format('d M Y, H:i') }}</span></div>
        @endif
    </div>
    @endif

    <div class="landing-actions mt-4">
        <a href="{{ route('home') }}" class="btn-outline-navy"><i class="bi bi-arrow-clockwise me-1"></i> Try again</a>
        <a href="{{ route('login.create') }}" class="auth-cta-slat"><span><i class="bi bi-shield-lock me-1"></i> Administrator sign in</span></a>
    </div>
</div>
@endsection
