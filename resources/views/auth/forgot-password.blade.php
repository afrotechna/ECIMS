@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
<header class="auth-login-header">
    <div class="auth-login-logo">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
    </div>
    <h1>Reset your password</h1>
    <p class="auth-login-subtitle">Enter your login ID; we will email a reset link to the address on file</p>
</header>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<form method="POST" action="{{ route('password.email') }}" class="auth-login-form">
    @csrf
    <div class="mb-3">
        <label for="login" class="form-label">Login ID</label>
        <input type="text" class="form-control @error('login') is-invalid @enderror" id="login" name="login" value="{{ old('login') }}" required autofocus placeholder="Email, NACTVET reg no, or staff check number">
        @error('login')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Students: NACTVET registration number. Staff: check number. Administrators: email.</div>
    </div>
    <button type="submit" class="btn btn-signin w-100">Send reset link</button>
</form>

<p class="auth-field-hint mt-3 mb-0 text-center small">No email on file? Contact the Academic Office or IT.</p>

<a href="{{ route('login.create') }}" class="btn btn-link d-block text-center mt-2">
    <i class="bi bi-arrow-left me-1"></i> Back to sign in
</a>
@endsection
