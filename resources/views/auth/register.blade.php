@extends('layouts.auth')

@section('title', 'Register')

@section('content')
<h2 class="welcome">Create your account</h2>
<p class="subtitle">Register to access {{ config('app.name') }}.</p>

<form method="POST" action="{{ route('register.store') }}" id="registerForm">
    @csrf
    <div class="mb-3">
        <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required autofocus placeholder="Your full name">
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required placeholder="your@email.com">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3">
        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required placeholder="Min. 8 characters" autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="mb-3 form-check">
        <input type="checkbox" class="form-check-input" id="showPasswordReg" aria-label="Show password">
        <label class="form-check-label" for="showPasswordReg">Show Password</label>
    </div>
    <div class="mb-3">
        <label for="password_confirmation" class="form-label">Confirm password <span class="text-danger">*</span></label>
        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required placeholder="Confirm password" autocomplete="new-password">
    </div>
    <button type="submit" class="btn btn-primary btn-signin">Register</button>
</form>

<p class="mt-3 mb-0 text-center text-muted small">
    Already have an account? <a href="{{ route('login.create') }}" class="link-forgot">Sign in</a>
</p>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var pw = document.getElementById('password');
    var show = document.getElementById('showPasswordReg');
    if (show && pw) show.addEventListener('change', function() { pw.type = this.checked ? 'text' : 'password'; });

    var errors = @json($errors->any() ? $errors->getMessages() : []);
    if (Object.keys(errors).length) {
        var firstMsg = Object.values(errors)[0][0] || 'Please check your input.';
        Swal.fire({ icon: 'error', title: 'Registration failed', text: firstMsg, confirmButtonColor: '#0d6efd' });
    }
});
</script>
@endpush
@endsection
