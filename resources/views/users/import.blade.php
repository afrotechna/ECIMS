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
        <h1 class="page-title-landing">
            <i class="bi bi-upload me-2 opacity-90"></i>Bulk Upload Users
            @include('partials.help-tip', ['text' => 'CSV columns: name, surname (initial password). Student rows: nactvet_reg_no. Staff rows: email, check_number, role slug — e.g. vice_principal_arc, vice_principal_afp, admission_officer, procurement_officer.', 'placement' => 'bottom'])
        </h1>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-light btn-sm text-dark border">Back to Users</a>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-file-earmark-arrow-up me-2"></i>Upload CSV</div>
    <div class="card-body">
        <form action="{{ route('users.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">CSV file
                    @include('partials.help-tip', ['text' => 'Initial password = surname for all users. They must change it on first login (min 8 chars, letters and numbers).'])
                </label>
                <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".csv,.txt" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="d-flex gap-2 mt-3">
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Upload and Create Users</button>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <a href="{{ route('users.import.template') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Download CSV template</a>
        </div>
        </form>
        <hr class="my-4">
        <h6 class="fw-semibold form-section-title">CSV example</h6>
        <p class="text-muted small">There is no <code>password</code> column — every new user's initial password is their <strong>surname</strong> (lowercase), and they must change it on first login.</p>
        <pre class="bg-light p-3 rounded small mb-0">name,surname,email,role,check_number,nactvet_reg_no
Jane,Mkapa,jane.mkapa@example.com,admission_officer,CN-0001,
John,Doe,,student,,S0001/0001/2026</pre>
    </div>
</div>
@endsection
