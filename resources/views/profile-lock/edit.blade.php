@extends('layouts.app')

@section('title', 'Profile edit lock')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Profile edit lock</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-person-lock me-2 opacity-90"></i>Profile edit lock</h1>
    <p class="page-subtitle-landing mb-0">Once every staff member has completed their profile, lock further edits. Staff who already completed their profile will still be able to view it — anyone who hasn't completed it yet can still do so for the first time.</p>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card card-landing">
    <div class="card-header-landing d-flex justify-content-between align-items-center">
        <span>Current status</span>
        @if($setting->is_locked)
            <span class="badge bg-danger">Locked — profiles are view-only</span>
        @else
            <span class="badge bg-success">Unlocked — staff can edit their profile</span>
        @endif
    </div>
    <div class="card-body">
        @if($setting->is_locked && $setting->locked_at)
            <p class="text-muted small mb-3">
                Locked {{ $setting->locked_at->format('d M Y, H:i') }}
                @if($setting->lockedByUser) by {{ $setting->lockedByUser->name }} @endif
            </p>
        @endif
        <form action="{{ route('profile-lock.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="is_locked" value="1" id="is_locked" {{ old('is_locked', $setting->is_locked) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_locked"><strong>Lock profile editing</strong></label>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
