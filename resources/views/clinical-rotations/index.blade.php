@extends('layouts.app')
@section('title', 'Clinical rotation')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Clinical rotation</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-hospital me-2 opacity-90"></i>Clinical rotation</h1>
        <p class="page-subtitle-landing mb-0">Semester II for <strong>NTA 4–6</strong>: split students into <strong>six groups (Level 4)</strong> or <strong>five groups (Levels 5–6)</strong>, assign hospitals, record Mon–Fri attendance. Download the official <strong>rotation schedule</strong> below (Word or PDF).</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('clinical.coordinator') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-speedometer2 me-1"></i>Coordinator</a>
        <a href="{{ route('clinical-logbook.index') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-journal-medical me-1"></i>Logbook review</a>
        <a href="{{ route('clinical-procedures.index') }}" class="btn btn-outline-secondary btn-sm">Procedures</a>
        <a href="{{ route('clinical-rotations.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New round</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="card card-landing mb-4">
    <div class="card-header-landing"><i class="bi bi-file-earmark-arrow-down me-2"></i>Export rotation schedule (Word / PDF)</div>
    <div class="card-body">
        <p class="small text-muted mb-2"><strong>NTA 4</strong> — 12 weeks, six fortnights, six clinical areas (Clinical Nutrition, Clinical Skills, Internal Medicine, OBGY, Paediatrics, Patient Care). <strong>NTA 5–6</strong> — 10 weeks, five fortnights (pattern matches standard NTA clinical schedules).</p>
        <form method="GET" action="{{ route('clinical-rotations.schedule.export.standalone') }}" class="row g-2 align-items-end flex-wrap">
            <div class="col-auto">
                <label class="form-label small mb-0">NTA level</label>
                <select name="nta_level" class="form-select form-select-sm" required>
                    <option value="4">4</option>
                    <option value="5" selected>5</option>
                    <option value="6">6</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Start (Monday)</label>
                <input type="date" name="start" class="form-control form-control-sm" required value="{{ old('start', $defaultScheduleMonday ?? \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY)->format('Y-m-d')) }}">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Weeks per department</label>
                <select name="weeks_per_block" class="form-select form-select-sm">
                    <option value="1" {{ (string) old('weeks_per_block', '2') === '1' ? 'selected' : '' }}>1 week</option>
                    <option value="2" {{ (string) old('weeks_per_block', '2') === '2' ? 'selected' : '' }}>2 weeks</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Format</label>
                <select name="format" class="form-select form-select-sm" required>
                    <option value="docx">Word (.docx)</option>
                    <option value="pdf">PDF</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Programme (optional)</label>
                <select name="programme_id" class="form-select form-select-sm">
                    <option value="">—</option>
                    @foreach($programmes as $p)
                        <option value="{{ $p->id }}" {{ (string) old('programme_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-download me-1"></i>Download</button>
            </div>
        </form>
    </div>
</div>

@php
    $bulkDelete = [
        'bulkModule' => 'clinical',
        'bulkAction' => route('clinical-rotations.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteClinicalRounds',
        'bulkTableId' => 'clinicalRoundsTable',
        'bulkItemCount' => $rounds->count(),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Rounds</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle" id="clinicalRoundsTable">
                <thead>
                    <tr>
                        @include('partials.bulk-delete.th', $bulkDelete)
                        <th>Semester</th>
                        <th>Programme</th>
                        <th>NTA</th>
                        <th class="d-none d-md-table-cell">Posting week</th>
                        <th>Groups</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rounds as $r)
                        <tr>
                            @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $r->id]))
                            <td class="small">{{ $r->semester?->label ?? '—' }}</td>
                            <td><strong>{{ $r->programme?->code }}</strong> {{ $r->programme?->name }}</td>
                            <td>Level {{ $r->nta_level }}</td>
                            <td class="d-none d-md-table-cell small">
                                @if($r->rotation_week_monday && $r->rotation_week_friday)
                                    {{ $r->rotation_week_monday->format('j M') }} – {{ $r->rotation_week_friday->format('j M Y') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $r->groups->count() }}</td>
                            <td class="text-end text-nowrap">
                                <a href="{{ route('clinical-rotations.show', $r) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                @canModule('clinical', 'delete')
                                <form method="POST" action="{{ route('clinical-rotations.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Delete this rotation round and all groups?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-cohas-delete ms-1" title="Delete" aria-label="Delete"><i class="bi bi-trash-fill"></i></button>
                                </form>
                                @endcanModule
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-5">No rotation rounds yet. Create one for Semester II.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($rounds->hasPages())
        <div class="card-footer border-0 bg-light py-2">{{ $rounds->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
