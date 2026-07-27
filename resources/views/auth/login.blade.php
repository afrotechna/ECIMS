@extends('layouts.auth')

@section('title', 'Sign in')

@section('content')
<header class="auth-login-header">
    <div class="auth-login-logo">
        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}">
    </div>
    <h1 id="login-form-title">Welcome to {{ config('app.name') }}</h1>
    <p class="auth-login-subtitle">Sign in to your account to continue</p>
</header>

<form method="POST" action="{{ route('login.store') }}" id="loginForm" class="auth-login-form" novalidate>
    @csrf

    <div class="auth-field">
        <label for="login" class="form-label">Username</label>
        <input type="text"
               class="form-control @error('login') is-invalid @enderror"
               id="login"
               name="login"
               value="{{ old('login') }}"
               required
               autofocus
               autocomplete="username"
               placeholder="NACTVET reg. no., Staff ID, or email">
        @error('login')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <p class="auth-field-hint">Students: NACTVET number · Staff: Staff ID · Admin: email</p>
    </div>

    <div class="auth-field">
        <label for="password" class="form-label">Password</label>
        <input type="password"
               class="form-control @error('password') is-invalid @enderror"
               id="password"
               name="password"
               required
               autocomplete="current-password"
               placeholder="Enter your password">
        @error('password')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
    </div>

    <div class="auth-login-options">
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="showPassword" aria-label="Show password">
            <label class="form-check-label" for="showPassword">Show password</label>
        </div>
        <div class="form-check">
            <input type="checkbox" class="form-check-input" id="remember" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label" for="remember">Remember me</label>
        </div>
    </div>

    <button type="submit" class="btn btn-signin" id="btnSignIn">
        <i class="bi bi-box-arrow-in-right me-1" aria-hidden="true"></i> Sign in
    </button>
</form>

<p class="auth-login-footer-link">
    <a href="{{ route('password.request') }}" id="forgotPasswordLink">Forgot password?</a>
</p>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var pw = document.getElementById('password');
    var show = document.getElementById('showPassword');
    if (show && pw) {
        show.addEventListener('change', function () {
            pw.type = this.checked ? 'text' : 'password';
        });
    }

    var form = document.getElementById('loginForm');
    if (form) {
        form.addEventListener('submit', function () {
            var btn = document.getElementById('btnSignIn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Signing in…';
            }
        });
    }

    var brandColor = '#1a4fb5';
    var errors = @json($errors->any() ? $errors->getMessages() : []);
    var errMsg = @json(session('error'));
    var infoMsg = @json(session('info'));
    if (Object.keys(errors).length) {
        var firstMsg = Object.values(errors)[0][0] || 'Please check your input.';
        Swal.fire({ icon: 'error', title: 'Sign in failed', text: firstMsg, confirmButtonColor: brandColor });
    } else if (errMsg) {
        Swal.fire({ icon: 'error', title: 'Error', text: errMsg, confirmButtonColor: brandColor });
    } else if (infoMsg) {
        Swal.fire({ icon: 'info', title: 'Notice', text: infoMsg, confirmButtonColor: brandColor });
    }
});
</script>
@endpush
@endsection
