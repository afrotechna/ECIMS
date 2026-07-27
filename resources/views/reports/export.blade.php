@extends('layouts.app')
@section('title', 'Data Export')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Export</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-download me-2 opacity-90"></i>Data Export</h1>
    <p class="page-subtitle-landing mb-0">Download CSV backup (admin only).</p>
</div>
<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-file-earmark-arrow-down me-2"></i>Export</div>
    <div class="card-body">
        <p class="text-muted">Choose a dataset to download as CSV.</p>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('export.run', ['type' => 'students']) }}" class="btn btn-primary">Export Students</a>
            <a href="{{ route('export.run', ['type' => 'payments']) }}" class="btn btn-primary">Export Payments</a>
        </div>
    </div>
</div>
@endsection
