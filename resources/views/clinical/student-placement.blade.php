@extends('layouts.app')
@section('title', 'My clinical placement')
@section('content')
@php
    $primary = $primary ?? null;
    $attendance = $attendance_summary ?? null;
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Clinical placement</span>
</nav>

<div class="page-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-hospital me-2 opacity-90"></i>My clinical placement</h1>
        <p class="page-subtitle-landing mb-0">Current posting, group, hospital, and attendance for this week</p>
    </div>
    <a href="{{ route('my.clinical.logbook.index') }}" class="btn btn-primary btn-sm"><i class="bi bi-journal-medical me-1"></i>Clinical logbook</a>
</div>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

@if($placements->isEmpty())
    <div class="alert alert-info">
        Not yet assigned to a clinical rotation group.
        <a href="{{ route('my.module-registration') }}" class="alert-link">Register your clinical modules</a>
    </div>
@else
    @foreach($placements as $group)
        @php $round = $group->round; @endphp
        <div class="card card-landing mb-3">
            <div class="card-header-landing d-flex flex-wrap justify-content-between gap-2">
                <span><i class="bi bi-people me-2"></i>{{ $group->name }} · NTA {{ $round->nta_level }}</span>
                @if($primary && $primary->id === $group->id)
                    <span class="badge bg-warning text-dark">Current placement</span>
                @endif
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <p class="small text-muted mb-1">Semester / round</p>
                        <p class="mb-0 fw-semibold">{{ $round->semester?->label }} — {{ $round->title ?: 'Clinical rotation' }}</p>
                    </div>
                    <div class="col-md-6">
                        <p class="small text-muted mb-1">Programme</p>
                        <p class="mb-0">{{ $round->programme?->code }} — {{ $round->programme?->name }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="small text-muted mb-1">Department (this block)</p>
                        <p class="mb-0">{{ \App\Support\ClinicalRotationCatalog::departmentLabel($group->department_code) }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="small text-muted mb-1">Hospital / site</p>
                        <p class="mb-0">{{ \App\Support\ClinicalRotationCatalog::hospitalLabel($group->hospital_code) ?: '— assign on staff roster —' }}</p>
                    </div>
                    <div class="col-md-4">
                        <p class="small text-muted mb-1">Posting week</p>
                        <p class="mb-0">
                            @if($round->rotation_week_monday && $round->rotation_week_friday)
                                {{ $round->rotation_week_monday->format('j M') }} – {{ $round->rotation_week_friday->format('j M Y') }}
                            @else
                                Set by clinical coordinator
                            @endif
                        </p>
                    </div>
                </div>
                @if($primary && $primary->id === $group->id && $attendance)
                    <hr>
                    <p class="small text-muted mb-2"><strong>This week (Mon–Fri):</strong> {{ $attendance['week_label'] }}</p>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="badge {{ $attendance['days_present'] >= 4 ? 'bg-success' : ($attendance['has_record'] ? 'bg-warning text-dark' : 'bg-secondary') }} fs-6">
                            {{ $attendance['days_present'] }} / {{ $attendance['days_total'] }} days marked present
                        </span>
                        @if(! $attendance['has_record'])
                            <span class="small text-muted">Attendance not yet recorded by supervisor for this week.</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    @php $checklist = app(\App\Services\ClinicalCompetencyService::class)->checklistForStudent($student, $semester?->id); @endphp
    @if(count($checklist) > 0)
    <div class="card card-landing mb-3">
        <div class="card-header-landing"><i class="bi bi-list-check me-2"></i>Competency checklist</div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Procedure</th><th>Progress</th></tr></thead>
                <tbody>
                    @foreach($checklist as $item)
                    <tr>
                        <td class="small">{{ $item['procedure']->code }} {{ $item['procedure']->name }}</td>
                        <td>{{ $item['approved'] }}/{{ $item['required'] }} @if($item['met'])<span class="badge bg-success">Met</span>@endif</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="card card-landing">
        <div class="card-header-landing"><i class="bi bi-journal-check me-2"></i>Logbook summary (this semester)</div>
        <div class="card-body d-flex flex-wrap gap-3">
            <div><span class="text-muted small">Total entries</span><div class="fs-4 fw-bold">{{ $logbook_counts['total'] ?? 0 }}</div></div>
            <div><span class="text-muted small">Approved</span><div class="fs-4 fw-bold text-success">{{ $logbook_counts['approved'] ?? 0 }}</div></div>
            <div><span class="text-muted small">Awaiting instructor</span><div class="fs-4 fw-bold text-warning">{{ $logbook_counts['pending'] ?? 0 }}</div></div>
            <div><span class="text-muted small">Drafts</span><div class="fs-4 fw-bold text-secondary">{{ $logbook_counts['draft'] ?? 0 }}</div></div>
            <div class="ms-auto align-self-center">
                <a href="{{ route('my.clinical.logbook.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New logbook entry</a>
            </div>
        </div>
    </div>
@endif
@endsection
