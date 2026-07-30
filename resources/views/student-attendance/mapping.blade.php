@extends('layouts.app')
@section('title', 'Biometric ID mapping')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('student-attendance.index') }}">Student attendance</a>
    <span class="mx-2">/</span>
    <span>Biometric ID mapping</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-link-45deg me-2 opacity-90"></i>Biometric ID mapping</h1>
    <p class="page-subtitle-landing mb-0">Link each student to the User ID enrolled on the ZKTeco terminal, so imported punches can be matched to the right person.</p>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-file-earmark-arrow-up me-2"></i>Bulk mapping (CSV)</div>
            <div class="card-body">
                <form action="{{ route('student-attendance.mapping.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">CSV file — columns: <code>reg_no, biometric_id</code></label>
                        <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-upload me-1"></i> Upload mapping</button>
                </form>
                <p class="small text-muted mt-3 mb-0">Individual students can also be mapped one at a time from their profile's Edit page.</p>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card card-landing">
            <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Current mappings</div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 480px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead>
                            <tr><th>Reg No</th><th>Student</th><th>Biometric ID</th></tr>
                        </thead>
                        <tbody>
                            @forelse($students->whereNotNull('biometric_id') as $s)
                            <tr>
                                <td><code>{{ $s->reg_no }}</code></td>
                                <td>{{ $s->full_name }}</td>
                                <td><span class="badge bg-info text-dark">{{ $s->biometric_id }}</span></td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-5">No students mapped yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
