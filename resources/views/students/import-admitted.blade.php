@extends('layouts.app')
@section('title', 'Import admitted students')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.index') }}">Students</a>
    <span class="mx-2">/</span>
    <span>Admitted intake</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-person-lines-fill me-2 opacity-90"></i>Import admitted students</h1>
        <p class="page-subtitle-landing mb-0">Minimal rows from <strong>NACTVET</strong> or <strong>TAMISEMI</strong> lists. Further details are completed later in <a href="{{ route('registration-wizard.start') }}">Student registration (steps)</a>.</p>
</div>

@if($importSetting->restrict_single_programme)
<div class="alert alert-warning d-flex justify-content-between align-items-center">
    <span><i class="bi bi-shield-exclamation me-2"></i>Restricted: each upload must contain a single programme and NTA level. Mixed files are rejected.</span>
    @if(auth()->user()->isAdmin())
    <a href="{{ route('student-import-settings.edit') }}" class="btn btn-sm btn-outline-dark">Change</a>
    @endif
</div>
@endif

<div class="card card-landing">
    <div class="card-header-landing d-flex justify-content-between align-items-center">
        <span><i class="bi bi-file-earmark-arrow-up me-2"></i>CSV columns</span>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('student-import-settings.edit') }}" class="text-white small"><i class="bi bi-gear me-1"></i>Import settings</a>
        @endif
    </div>
    <div class="card-body">
        <p class="small text-muted">Upload CSV (from Excel: <em>Save As</em> → CSV). The importer recognises common NACTE/NACTVET list layouts, e.g. <strong>NACTE REGISTRATION</strong>, <strong>CANDIDAT</strong> (full name), <strong>SEX (M/F)</strong>, <strong>Intake</strong> (SEPT/MARCH). You can still use explicit columns: <code>nactvet_reg_no</code>, <code>first_name</code>, <code>last_name</code>, <code>programme_code</code>, <code>intake_year</code>, <code>intake_session</code> (<code>september</code> or <code>march</code>), <code>nta_level</code> (4–6). Optional: <code>middle_name</code>, <code>admission_source</code> (<code>nactvet</code> or <code>tamisemi</code>).</p>
        <ul class="small">
            <li><strong>NACTVET / NACTE</strong> rows: e.g. <code>S0001/0001/2026</code>, <code>P0001/0001/2026</code>, or <code>NS5391/0026/2024</code>.</li>
            @if($importSetting->restrict_single_programme)
            <li><strong>One programme and one NTA level per file</strong> — this is currently required (see banner above). Upload each level/programme combination as its own CSV.</li>
            @else
            <li>Each row can be a different programme and NTA level — the file's own <code>programme_code</code>/<code>nta_level</code> columns are used per row. The <strong>defaults</strong> below only fill in rows where that column is blank.</li>
            @endif
            <li>Intake year can be omitted when the registration ends with <code>/YYYY</code>. Intake session defaults to <strong>September</strong> when the file has no session/intake column and no default is chosen.</li>
            <li><strong>TAMISEMI</strong> / Form IV index: set <code>admission_source</code> to <code>tamisemi</code> and put the index in the registration column (plain text).</li>
        </ul>
        <form action="{{ route('students.import-admitted.store') }}" method="POST" enctype="multipart/form-data" class="mt-3">
            @csrf
            <div class="mb-3">
                <label for="file" class="form-label">CSV file</label>
                <input type="file" name="file" id="file" class="form-control @error('file') is-invalid @enderror" accept=".csv,.txt" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="row g-3 mb-3">
                <div class="col-md-3">
                    <label for="default_programme_id" class="form-label">Default programme <span class="text-muted fw-normal">(if not in file)</span></label>
                    <select name="default_programme_id" id="default_programme_id" class="form-select">
                        <option value="">— none —</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" @selected(old('default_programme_id') == $p->id)>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="default_intake_year" class="form-label">Default intake year <span class="text-muted fw-normal">(optional)</span></label>
                    <input type="number" name="default_intake_year" id="default_intake_year" class="form-control" min="1990" max="2100" placeholder="e.g. 2024" value="{{ old('default_intake_year') }}">
                </div>
                <div class="col-md-3">
                    <label for="default_intake_session" class="form-label">Default intake session <span class="text-muted fw-normal">(if not in file)</span></label>
                    <select name="default_intake_session" id="default_intake_session" class="form-select">
                        <option value="">— none (September) —</option>
                        @foreach(\App\Models\Student::INTAKE_SESSIONS as $value => $label)
                            <option value="{{ $value }}" @selected(old('default_intake_session') == $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="default_nta_level" class="form-label">Default NTA level <span class="text-muted fw-normal">(if not in file)</span></label>
                    <select name="default_nta_level" id="default_nta_level" class="form-select">
                        <option value="">— none —</option>
                        <option value="4" @selected(old('default_nta_level') == '4')>4</option>
                        <option value="5" @selected(old('default_nta_level') == '5')>5</option>
                        <option value="6" @selected(old('default_nta_level') == '6')>6</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="bi bi-upload me-1"></i> Upload</button>
            <a href="{{ route('students.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <a href="{{ route('students.import-admitted.template') }}" class="btn btn-outline-secondary"><i class="bi bi-download me-1"></i> Download CSV template</a>
        </form>
    </div>
</div>
@endsection
