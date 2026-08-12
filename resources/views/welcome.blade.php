@extends('layouts.landing')

@section('title', 'Welcome')

@section('content')
@include('layouts.partials.maintenance-banner')
<div class="landing-welcome">
    <div class="logo-hero">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
    </div>
    <h1>{{ config('app.name') }}</h1>
    <p class="lead">Electronic College Information Management System</p>
    <p class="tagline">Musoma College of Health and Allied Sciences &mdash; digitize student registration, fees, results, clinical training, and college finance in one secure platform.</p>

    <div class="landing-actions">
        <a href="{{ route('login.create') }}" class="auth-cta-slat"><span><i class="bi bi-box-arrow-in-right me-1"></i> Sign in</span></a>
        @auth
        <a href="{{ route('dashboard') }}" class="btn-outline-navy"><i class="bi bi-grid-1x2 me-1"></i> Dashboard</a>
        @endauth
    </div>

    <div class="landing-pills">
        <div class="landing-pill"><i class="bi bi-ui-checks-grid"></i><span>Online semester registration</span></div>
        <div class="landing-pill"><i class="bi bi-journal-check"></i><span>CA &amp; end-of-semester results</span></div>
        <div class="landing-pill"><i class="bi bi-wallet2"></i><span>Financial statements &amp; fees</span></div>
        <div class="landing-pill"><i class="bi bi-hospital"></i><span>Clinical rotations &amp; records</span></div>
    </div>

    <div class="landing-stats">
        <div class="landing-stat">
            <span class="figure">{{ number_format($stats['students']) }}</span>
            <span class="label">Students enrolled</span>
        </div>
        <div class="landing-stat">
            <span class="figure">{{ number_format($stats['programmes']) }}</span>
            <span class="label">Active programmes</span>
        </div>
        <div class="landing-stat">
            <span class="figure">{{ number_format($stats['staff']) }}</span>
            <span class="label">Staff members</span>
        </div>
    </div>
</div>
@endsection
