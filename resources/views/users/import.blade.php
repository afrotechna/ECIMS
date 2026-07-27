@extends('layouts.app')
@section('title', 'Bulk Upload Users')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('users.index') }}">Users</a>
    <span class="mx-2">/</span>
    <span>Bulk Upload</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-upload me-2 opacity-90"></i>Bulk Upload Users</h1>
        <p class="page-subtitle-landing mb-0">CSV: name, surname (initial password). Student: nactvet_reg_no. Staff: email, check_number, role slug — e.g. <code>vice_principal_arc</code> (Academic, Research &amp; Consultancy), <code>vice_principal_afp</code> (Administrative, Financial &amp; Planning), <code>admission_officer</code>, <code>procurement_officer</code>.</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-light btn-sm text-dark border">Back to Users</a>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-file-earmark-arrow-up me-2"></i>Upload CSV</div>
    <div class="card-body">
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <form action="{{ route('users.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">CSV file</label>
                <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".csv,.txt" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <p class="text-muted small">Initial password = surname for all users. They must change it on first login (min 8 chars, letters and numbers).</p>
            <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Upload and Create Users</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
        </form>
        <hr class="my-4">
        <h6 class="fw-semibold form-section-title">CSV example</h6>
        <pre class="bg-light p-3 rounded small mb-0">name,email,password,role
John Doe,john@example.com,Secret123,user
Jane Admin,jane@example.com,,admin</pre>
    </div>
</div>
@endsection
