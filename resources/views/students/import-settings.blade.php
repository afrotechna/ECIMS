@extends('layouts.app')

@section('title', 'Admitted-students import settings')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.index') }}">Students</a>
    <span class="mx-2">/</span>
    <span>Import settings</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-sliders me-2 opacity-90"></i>Admitted-students import settings</h1>
    <p class="page-subtitle-landing mb-0">Controls the <a href="{{ route('students.import-admitted') }}" class="link-light">Import admitted students</a> upload only.</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing d-flex justify-content-between align-items-center">
        <span>Current status</span>
        @if($setting->restrict_single_programme)
            <span class="badge bg-warning text-dark">Restricted — one programme &amp; level per upload</span>
        @else
            <span class="badge bg-success">Unrestricted — mixed uploads allowed</span>
        @endif
    </div>
    <div class="card-body">
        @if($setting->updated_at)
            <p class="text-muted small mb-3">
                Last changed {{ $setting->updated_at->format('d M Y, H:i') }}
                @if($setting->updatedByUser) by {{ $setting->updatedByUser->name }} @endif
            </p>
        @endif
        <form action="{{ route('student-import-settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            <div class="form-check form-switch mb-3">
                <input class="form-check-input" type="checkbox" role="switch" name="restrict_single_programme" value="1" id="restrict_single_programme" {{ old('restrict_single_programme', $setting->restrict_single_programme) ? 'checked' : '' }}>
                <label class="form-check-label" for="restrict_single_programme"><strong>Require one programme and one NTA level per CSV upload</strong></label>
            </div>
            <p class="text-muted small">
                When on, a CSV that mixes more than one programme code or NTA level is rejected outright — nothing from it is imported. Each intake list (e.g. CMT level 4, MLT level 6) must then be uploaded as its own separate file. When off, one file can freely mix programmes and levels, using each row's own <code>programme_code</code>/<code>nta_level</code> columns.
            </p>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save</button>
                <a href="{{ route('students.import-admitted') }}" class="btn btn-outline-secondary">Back to import</a>
            </div>
        </form>
    </div>
</div>
@endsection
