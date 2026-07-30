@extends('layouts.app')
@section('title', 'Import attendance')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('student-attendance.index') }}">Student attendance</a>
    <span class="mx-2">/</span>
    <span>Import</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-upload me-2 opacity-90"></i>Import attendance punches</h1>
    <p class="page-subtitle-landing mb-0">Upload the CSV exported from the ZKTeco terminal software. Students must have a Biometric ID set first — see <a href="{{ route('student-attendance.mapping') }}">Biometric ID mapping</a>.</p>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-file-earmark-arrow-up me-2"></i>Upload export</div>
            <div class="card-body">
                <form action="{{ route('student-attendance.import.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">CSV file</label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i> Import</button>
                </form>
                <hr class="my-3">
                <a href="{{ route('student-attendance.import.template') }}" class="btn btn-outline-secondary btn-sm w-100"><i class="bi bi-download me-1"></i> Download expected column template</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-info-circle me-2"></i>What's expected</div>
            <div class="card-body small">
                <p class="mb-2">The file needs at minimum a <strong>User ID</strong> column (the device's enrolled ID, matched against each student's Biometric ID) and a <strong>Time</strong> column. Common header spellings from ZKTeco software are recognised automatically — for example "User ID", "PIN", "Enroll No" for the ID column, and "Time", "DateTime", "Punch Time" for the timestamp.</p>
                <p class="mb-2">A <strong>State</strong> or <strong>Status</strong> column is optional and used to record check-in vs check-out where present.</p>
                <p class="mb-0 text-muted">Re-uploading a file that overlaps a previous import is safe — punches already recorded for the same device ID and exact timestamp are skipped automatically, not duplicated.</p>
            </div>
        </div>
    </div>
</div>
@endsection
