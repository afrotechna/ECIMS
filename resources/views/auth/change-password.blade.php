@extends('layouts.app')

@section('title', 'Change Password')

@section('content')
<div class="page-header mb-4">
    <h1 class="page-title mb-1"><i class="bi bi-key me-2 text-primary"></i>Change password</h1>
    <p class="text-muted small mb-0">You must set a new password (min 8 characters, letters and numbers).</p>
</div>
<div class="card card-modern" style="max-width: 420px;">
    <div class="card-body">
        @if(session('info'))
            <div class="alert alert-info">{{ session('info') }}</div>
        @endif
        <form action="{{ route('password.change.store') }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="current_password" class="form-label">Current password</label>
                <input type="password" class="form-control @error('current_password') is-invalid @enderror" id="current_password" name="current_password" required autocomplete="current-password">
                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">New password</label>
                <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required autocomplete="new-password" minlength="8" placeholder="At least 8 characters, letters and numbers">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label for="password_confirmation" class="form-label">Confirm new password</label>
                <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
            </div>
            <button type="submit" class="btn btn-primary btn-modern">Change password</button>
        </form>
    </div>
</div>
@endsection
