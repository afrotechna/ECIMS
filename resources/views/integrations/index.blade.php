@extends('layouts.app')
@section('title', 'Integrations')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Integrations</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plug me-2 opacity-90"></i>External integrations</h1>
    <p class="page-subtitle-landing mb-0">Configure URLs in <code>.env</code> (<code>MOODLE_URL</code>, <code>LIBRARY_SYSTEM_URL</code>). GEPG payment is pending authorization.</p>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing">Moodle (LMS)</div>
            <div class="card-body">
                @if($moodleUrl)
                <a href="{{ $moodleUrl }}" target="_blank" rel="noopener" class="btn btn-primary">Open Moodle</a>
                @else
                <p class="text-muted small mb-0">Set <code>MOODLE_URL</code> in .env</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing">Library system</div>
            <div class="card-body">
                @if($libraryUrl)
                <a href="{{ $libraryUrl }}" target="_blank" rel="noopener" class="btn btn-primary">Open library</a>
                <p class="small text-muted mt-2 mb-0">COHAS does not manage loans; use your separate library software.</p>
                @else
                <p class="text-muted small mb-0">Set <code>LIBRARY_SYSTEM_URL</code> in .env when ready to link.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-landing h-100">
            <div class="card-header-landing">GEPG / online pay</div>
            <div class="card-body">
                <p class="small text-muted mb-0">{{ $gepgNote }}</p>
            </div>
        </div>
    </div>
</div>

<div class="card card-landing mt-3">
    <div class="card-header-landing">Database backup</div>
    <div class="card-body">
        <p class="mb-2">Run on the server (requires <code>mysqldump</code>):</p>
        <code>php artisan cohas:backup-database</code>
        <p class="small text-muted mt-2 mb-0">Files saved under <code>storage/app/backups</code>. Schedule via Windows Task Scheduler or cron.</p>
    </div>
</div>
@endsection
