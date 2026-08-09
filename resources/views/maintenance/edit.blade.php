@extends('layouts.app')

@section('title', 'Maintenance mode')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Maintenance mode</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-cone-striped me-2 opacity-90"></i>Maintenance mode</h1>
    <p class="page-subtitle-landing mb-0">While active, only administrators can sign in. Everyone else sees a maintenance page with the details below, and is notified in-app as soon as you save.</p>
</div>


<div class="card card-landing">
    <div class="card-header-landing d-flex justify-content-between align-items-center">
        <span>Current status</span>
        @if($setting->isEffectiveNow())
            <span class="badge bg-danger">Active now — non-admins are locked out</span>
        @elseif($setting->isUpcoming())
            <span class="badge bg-warning text-dark">Scheduled — starts {{ $setting->starts_at->format('d M Y, H:i') }}</span>
        @else
            <span class="badge bg-success">Inactive — system open to everyone</span>
        @endif
    </div>
    <div class="card-body">
        <form action="{{ route('maintenance.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="is_active" {{ old('is_active', $setting->is_active) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active"><strong>Activate maintenance mode</strong></label>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label for="title" class="form-label">Title</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $setting->title) }}" placeholder="Scheduled system maintenance">
                    @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="starts_at" class="form-label">Starts at</label>
                    <input type="datetime-local" class="form-control @error('starts_at') is-invalid @enderror" id="starts_at" name="starts_at" value="{{ old('starts_at', $setting->starts_at?->format('Y-m-d\TH:i')) }}">
                    @error('starts_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-3">
                    <label for="ends_at" class="form-label">Ends at</label>
                    <input type="datetime-local" class="form-control @error('ends_at') is-invalid @enderror" id="ends_at" name="ends_at" value="{{ old('ends_at', $setting->ends_at?->format('Y-m-d\TH:i')) }}">
                    @error('ends_at')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-text">Leave blank to keep it active until you switch it off.</div>
                </div>
                <div class="col-12">
                    <label for="message" class="form-label">Message shown to users</label>
                    <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="3" placeholder="The system will be briefly unavailable while we perform scheduled maintenance.">{{ old('message', $setting->message) }}</textarea>
                    @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@endsection
