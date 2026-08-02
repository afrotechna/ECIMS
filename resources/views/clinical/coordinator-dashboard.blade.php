@extends('layouts.app')
@section('title', 'Clinical coordinator dashboard')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('clinical-rotations.index') }}">Clinical</a>
    <span class="mx-2">/</span>
    <span>Coordinator dashboard</span>
</nav>

<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-speedometer2 me-2 opacity-90"></i>Clinical coordinator dashboard</h1>
        <p class="page-subtitle-landing mb-0">Attendance, logbook, competency, and remediation at a glance</p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('clinical.reports') }}" class="btn btn-outline-light btn-sm">Reports</a>
        <a href="{{ route('clinical.progression.index') }}" class="btn btn-outline-light btn-sm">Progression decisions</a>
    </div>
</div>

<form method="GET" class="row g-2 mb-3 align-items-end">
    <div class="col-md-6">
        <label class="form-label small mb-0">Rotation round</label>
        <select name="round_id" class="form-select form-select-sm" onchange="this.form.submit()">
            @foreach($rounds as $r)
                <option value="{{ $r->id }}" {{ ($round?->id ?? null) === $r->id ? 'selected' : '' }}>{{ $r->title ?: 'Round' }} — {{ $r->semester?->label }} · {{ $r->programme?->code }}</option>
            @endforeach
        </select>
    </div>
    @if($round)
    <div class="col-auto">
        <a href="{{ route('clinical.export.logbook', ['round_id' => $round->id]) }}" class="btn btn-sm btn-outline-primary">Export logbook CSV</a>
    </div>
    @endif
</form>

@if($round)
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="card card-landing text-center p-3"><div class="text-muted small">Students</div><div class="fs-3 fw-bold">{{ $stats['students'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="card card-landing text-center p-3"><div class="text-muted small">With alerts</div><div class="fs-3 fw-bold text-warning">{{ $stats['alerts'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="card card-landing text-center p-3"><div class="text-muted small">Logbook pending</div><div class="fs-3 fw-bold">{{ $stats['pending_logbook'] }}</div></div></div>
    <div class="col-6 col-md-3"><div class="card card-landing text-center p-3"><div class="text-muted small">Open remediation</div><div class="fs-3 fw-bold text-danger">{{ $stats['open_remediation'] }}</div></div></div>
</div>

<div class="card card-landing">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 align-middle">
            <thead class="table-light">
                <tr>
                    <th>Student</th>
                    <th>Group</th>
                    <th>Attendance (week)</th>
                    <th>Logbook</th>
                    <th>Competency</th>
                    <th>Alerts</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                <tr>
                    <td>
                        <div class="fw-semibold">{{ $row['student']->full_name }}</div>
                    </td>
                    <td class="small">{{ $row['group']->name }}</td>
                    <td>{{ $row['attendance']['days_present'] }}/{{ $row['attendance']['days_total'] }}</td>
                    <td class="small">{{ $row['logbook']['approved'] ?? 0 }} approved / {{ $row['logbook']['total'] ?? 0 }} total</td>
                    <td class="small">
                        @if($row['competency_total'] > 0)
                            {{ $row['competency_met'] }}/{{ $row['competency_total'] }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @forelse($row['alerts'] as $a)
                            <span class="badge bg-warning text-dark me-1 mb-1">{{ $a }}</span>
                        @empty
                            <span class="text-muted small">OK</span>
                        @endforelse
                    </td>
                    <td class="text-end">
                        @include('partials.action-view', ['href' => route('clinical-logbook.student', $row['student'])])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
<div class="alert alert-info">Create a clinical rotation round first.</div>
@endif
@endsection
