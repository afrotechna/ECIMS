@extends('layouts.app')
@section('title', 'Import final results (CSV)')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('results.index') }}">Results</a>
    <span class="mx-2">/</span>
    <span>Import final (CSV)</span>
</nav>

<div class="page-header-landing mb-4">
    <h1 class="page-title-landing"><i class="bi bi-upload me-2 opacity-90"></i>Import final semester results</h1>
</div>


<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-landing h-100">
            <div class="card-header-landing"><i class="bi bi-download me-2"></i>Step 1 — Download template</div>
            <div class="card-body">
                <form method="GET" action="{{ route('results.import.final') }}" id="final-import-filter-form">
                    <div class="mb-3">
                        <label class="form-label">Semester</label>
                        <select name="semester_id" class="form-select" required onchange="document.getElementById('final-import-filter-form').submit()">
                            @forelse($semesters as $s)
                            <option value="{{ $s->id }}" {{ (string) ($semesterId ?? '') === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                            @empty
                            <option value="" disabled>No active semester</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Programme</label>
                        <select name="programme_id" class="form-select" onchange="document.getElementById('final-import-filter-form').submit()">
                            <option value="">— Select programme —</option>
                            @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ (string) ($programmeId ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }} ({{ $p->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">NTA level</label>
                        <select name="nta_level" class="form-select" required onchange="document.getElementById('final-import-filter-form').submit()">
                            <option value="" disabled {{ ($ntaLevel ?? '') === '' ? 'selected' : '' }}>— Select NTA level —</option>
                            @foreach([4, 5, 6] as $lvl)
                            <option value="{{ $lvl }}" {{ (string) ($ntaLevel ?? '') === (string) $lvl ? 'selected' : '' }}>NTA Level {{ $lvl }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if($programmeId && $courses->isNotEmpty())
                    <div class="alert alert-light border small py-2 mb-3">
                        <strong>{{ $courses->count() }} module(s)</strong> — 4 columns each (AVCA, AVES, FSCORE, GRADE).
                    </div>
                    @endif
                    <button type="submit"
                        class="btn btn-outline-primary w-100 mb-2"
                        formaction="{{ route('results.import.final.template') }}"
                        {{ !$programmeId || $courses->isEmpty() ? 'disabled' : '' }}>
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Download template (CSV)
                    </button>
                    <button type="submit"
                        class="btn btn-success w-100"
                        formaction="{{ route('results.import.final.demo-excel') }}"
                        {{ !$programmeId || $courses->isEmpty() || !$ntaLevel ? 'disabled' : '' }}>
                        <i class="bi bi-file-earmark-excel me-1"></i> Download demo Excel (sample final results)
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
                <form method="POST" action="{{ route('results.import.final.store') }}" enctype="multipart/form-data">
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
                    </div>
                    <p class="small text-muted mb-3">Imported rows are hidden from students and guardians until the Principal or VP (ARC) approves them on the <a href="{{ route('results.approvals.index') }}">Results approvals</a> page — SMS (Twilio) goes out at that point.</p>
                    <button type="submit" class="btn btn-primary w-100">Import final results</button>
                </form>
            </div>
        </div>
    </div>
</div>

<p class="mt-3"><a href="{{ route('results.index') }}" class="btn btn-outline-secondary btn-sm">Back to results</a></p>
@endsection
