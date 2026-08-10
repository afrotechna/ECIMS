@extends('layouts.app')
@section('title', 'Bulk Import Students')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.index') }}">Students</a>
    <span class="mx-2">/</span>
    <span>Bulk Import</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-upload me-2 opacity-90"></i>Bulk Import Students</h1>
        <p class="page-subtitle-landing mb-0">Upload a CSV (from Excel: <em>Save As</em> → CSV UTF-8). Recognised headers include <strong>NACTE REGISTRATION</strong> / nactvet_reg_no, <strong>CANDIDAT</strong> / candidate (full name; split into first/last automatically), <strong>SEX (M/F)</strong> as gender when present, plus optional <strong>programme_code</strong>, <strong>intake_year</strong>, <strong>nta_level</strong>. NACTVET (<code>S0000/0000/2026</code>) and NACTE-style numbers (e.g. <code>NS5391/0026/2024</code>) are accepted. If your sheet has no programme, intake, or NTA column, set the defaults below (intake can also be taken from the last <code>/YYYY</code> in the registration number).</p>
    </div>
    <a href="{{ route('students.index') }}" class="btn btn-outline-light btn-sm">Back to Students</a>
</div>
<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-file-earmark-arrow-up me-2"></i>Upload CSV</div>
    <div class="card-body">
        <form action="{{ route('students.import.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">CSV file</label>
                <input type="file" class="form-control @error('file') is-invalid @enderror" id="file" name="file" accept=".csv,.txt" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="default_programme_id" class="form-label">Default programme <span class="text-muted fw-normal">(if not in file)</span></label>
                    <select name="default_programme_id" id="default_programme_id" class="form-select">
                        <option value="">— none —</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" @selected(old('default_programme_id') == $p->id)>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="default_intake_year" class="form-label">Default intake year <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="number" name="default_intake_year" id="default_intake_year" class="form-control" min="1990" max="2100" placeholder="e.g. 2024" value="{{ old('default_intake_year') }}">
                </div>
                <div class="col-md-4">
                    <label for="default_nta_level" class="form-label">Default NTA level <span class="text-muted fw-normal">(if not in file)</span></label>
                    <select name="default_nta_level" id="default_nta_level" class="form-select">
                        <option value="">— none —</option>
                        <option value="4" @selected(old('default_nta_level') == '4')>4</option>
                        <option value="5" @selected(old('default_nta_level') == '5')>5</option>
                        <option value="6" @selected(old('default_nta_level') == '6')>6</option>
                    </select>
                </div>
            </div>
            <p class="text-muted small">System will generate an internal Reg No for each row. Duplicate registration numbers and invalid programme codes are skipped.</p>
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Upload and Import Students</button>
            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <a href="{{ route('students.import.template') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Download CSV template</a>
        </form>
        <hr class="my-4">
        <h6 class="fw-semibold">CSV example</h6>
        @php $sampleCode = $programmes->first()->code ?? 'CODE'; @endphp
        <pre class="bg-light p-3 rounded small mb-0">nactvet_reg_no,first_name,last_name,programme_code,intake_year,nta_level,email,phone
S0001/0001/2026,John,Doe,{{ $sampleCode }},2026,4,john@example.com,0712345678
P0002/0001/2026,Jane,Mary,{{ $sampleCode }},2026,5,,</pre>
        @if($programmes->isNotEmpty())
            <p class="text-muted small mt-2 mb-0">Active programme codes: {{ $programmes->pluck('code')->implode(', ') }}</p>
        @endif
    </div>
</div>
@endsection
