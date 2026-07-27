@extends('layouts.app')
@section('title', 'Import CA results (CSV)')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('results.index') }}">Results</a>
    <span class="mx-2">/</span>
    <span>Import CA (CSV)</span>
</nav>

<div class="page-header-landing mb-4">
    <h1 class="page-title-landing"><i class="bi bi-upload me-2 opacity-90"></i>Import CA results</h1>
    <p class="page-subtitle-landing mb-0">Row 1 is the table header (SN, CANDIDATE NAME, … then each <strong>module code</strong>). Fill CA marks under each module column, save as CSV UTF-8, upload once.</p>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-landing h-100">
            <div class="card-header-landing"><i class="bi bi-download me-2"></i>Step 1 — Download template</div>
            <div class="card-body">
                <form method="GET" action="{{ route('results.import.ca') }}" id="ca-import-filter-form">
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester_id" class="form-select" required onchange="document.getElementById('ca-import-filter-form').submit()">
                            @forelse($semesters as $s)
                            <option value="{{ $s->id }}" {{ (string) ($semesterId ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                            @empty
                            <option value="" disabled>No active semester</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Programme</label>
                        <select name="programme_id" class="form-select" onchange="document.getElementById('ca-import-filter-form').submit()">
                            <option value="">— Select programme —</option>
                            @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ (string) ($programmeId ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NTA level</label>
                        <select name="nta_level" class="form-select" onchange="document.getElementById('ca-import-filter-form').submit()">
                            <option value="">All levels</option>
                            @foreach([4, 5, 6] as $lvl)
                            <option value="{{ $lvl }}" {{ (string) ($ntaLevel ?? '') === (string) $lvl ? 'selected' : '' }}>NTA Level {{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($programmeId && $courses->isNotEmpty())
                    <div class="alert alert-light border small py-2 mb-3">
                        <strong>{{ $courses->count() }} module(s)</strong> will appear as columns:
                        <span class="text-muted">{{ $courses->pluck('code')->take(8)->implode(', ') }}@if($courses->count() > 8)…@endif</span>
                    </div>
                    @elseif($programmeId)
                    <div class="alert alert-warning small py-2 mb-3">No modules found — add courses under this programme first.</div>
                    @endif
                    <button type="submit"
                        class="btn btn-outline-primary w-100 mb-2"
                        formaction="{{ route('results.import.ca.template') }}"
                        {{ !$programmeId || $courses->isEmpty() ? 'disabled' : '' }}>
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download template (CSV)
                    </button>
                    <button type="submit"
                        class="btn btn-success w-100"
                        formaction="{{ route('results.import.ca.demo-excel') }}"
                        {{ !$programmeId || $courses->isEmpty() || !$ntaLevel ? 'disabled' : '' }}>
                        <i class="bi bi-file-earmark-excel me-1"></i> Download demo Excel (sample CA marks)
                    </button>
                    @if($programmeId && $courses->isNotEmpty() && !$ntaLevel)
                    <p class="small text-muted mt-2 mb-0">Select an <strong>NTA level</strong> to download the demo Excel file.</p>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-landing h-100">
            <div class="card-header-landing"><i class="bi bi-upload me-2"></i>Step 2 — Upload filled file</div>
            <div class="card-body">
                <form method="POST" action="{{ route('results.import.ca.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester_id" class="form-select" required>
                            @forelse($semesters as $s)
                            <option value="{{ $s->id }}" {{ (string) ($semesterId ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                            @empty
                            <option value="" disabled>No active semester</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CSV or Excel file</label>
                        <input type="file" name="file" class="form-control" accept=".csv,.txt,.xls,.xml" required>
                        <div class="form-text">Keep header rows. One upload imports every module column you filled.</div>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" name="notify_sms" value="1" class="form-check-input" id="notify_sms_ca"
                            {{ config('college.result_sms.notify_on_ca_import') ? 'checked' : '' }}>
                        <label class="form-check-label" for="notify_sms_ca">SMS students &amp; guardians after CA import</label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Import CA results</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-landing border-0 bg-light">
            <div class="card-body small">
                <h3 class="h6">Columns</h3>
                <p class="mb-0">Row 1: SN, CANDIDATE NAME, … then one column per module (<strong>CMT04101</strong>, …). After upload, each module gets <strong>PASS</strong> or <strong>FAIL</strong> remarks for students (FAIL = cannot sit end-of-semester exam for that module).</p>
            </div>
        </div>
    </div>
</div>

<p class="mt-3"><a href="{{ route('results.index') }}" class="btn btn-outline-secondary btn-sm">Back to results</a></p>
@endsection
