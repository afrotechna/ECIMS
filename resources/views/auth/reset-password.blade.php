@extends('layouts.auth')

@section('title', 'Set new password')

@section('content')
<header class="auth-login-header">
    <div class="auth-login-logo">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
    </div>
    <h1>Choose a new password</h1>
</header>

<form method="POST" action="{{ route('password.update') }}" class="auth-login-form">
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $email) }}" required readonly>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">New password</label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required minlength="8">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm password</label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required minlength="8">
    </div>
    <button type="submit" class="btn btn-signin w-100">Update password</button>
</form>
@endsection
