@extends('layouts.app')
@section('title', 'Clinical rotation')
@section('content')
@php
    $unassigned = max(0, $eligibleTotal - $assigned);
    $groupSlotCount = max(1, $clinical_rotation_round->groups->count());
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('clinical-rotations.index') }}">Clinical rotation</a>
    <span class="mx-2">/</span>
    <span>{{ $clinical_rotation_round->programme?->code }} · NTA {{ $clinical_rotation_round->nta_level }}</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-hospital me-2 opacity-90"></i>{{ $clinical_rotation_round->title ?: 'Clinical rotation round' }}</h1>
        <p class="page-subtitle-landing mb-0">{{ $clinical_rotation_round->semester?->label }} · {{ $clinical_rotation_round->programme?->name }} · <strong>NTA Level {{ $clinical_rotation_round->nta_level }}</strong>
            @if($clinical_rotation_round->rotation_week_monday && $clinical_rotation_round->rotation_week_friday)
                · <strong>Posting week:</strong> {{ $clinical_rotation_round->rotation_week_monday->format('D j M Y') }} – {{ $clinical_rotation_round->rotation_week_friday->format('D j M Y') }}
            @endif
        </p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('clinical-rotations.index') }}" class="btn btn-outline-secondary btn-sm">All rounds</a>
        <form method="POST" action="{{ route('clinical-rotations.destroy', $clinical_rotation_round) }}" class="d-inline">
            @csrf
            @method('DELETE')
            <button type="button" class="btn btn-outline-danger btn-sm" data-swal-confirm data-swal-title="Delete this rotation round?" data-swal-text="This deletes all groups, placements, and attendance."><i class="bi bi-trash me-1"></i>Delete round</button>
        </form>
    </div>
</div>


@php
    $defaultAttendanceWeek = ($clinical_rotation_round->rotation_week_monday
        ? $clinical_rotation_round->rotation_week_monday->copy()
        : \Carbon\Carbon::now()->startOfWeek(\Carbon\Carbon::MONDAY))->format('Y-m-d');
@endphp
<div class="card card-landing mb-4">
    <div class="card-header-landing"><i class="bi bi-printer me-2"></i>Notice board &amp; downloads</div>
    <div class="card-body">
        <p class="small text-muted mb-2"><strong>Posting list</strong></p>
        <div class="d-flex flex-wrap gap-2 mb-3">
            <a href="{{ route('clinical-rotations.roster.print', $clinical_rotation_round) }}" class="btn btn-outline-primary btn-sm" target="_blank" rel="noopener"><i class="bi bi-printer me-1"></i>Print posting list</a>
            <a href="{{ route('clinical-rotations.roster.csv', $clinical_rotation_round) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-file-earmark-spreadsheet me-1"></i>Download roster (Excel / CSV)</a>
            @if(in_array((int) $clinical_rotation_round->nta_level, [4, 5, 6], true))
                <div class="w-100 small text-muted mb-1 mt-2"><strong>Complete roster (all weeks)</strong></div>
                <form method="GET" action="{{ route('clinical-rotations.roster.all-weeks.print', $clinical_rotation_round) }}" class="row g-2 align-items-end flex-wrap w-100 mb-3">
                    <div class="col-auto">
                        <label class="form-label small mb-0">Rotation starts (Monday)</label>
                        <input type="date" name="start" class="form-control form-control-sm" value="{{ $clinical_rotation_round->rotation_week_monday?->format('Y-m-d') ?? '' }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-0">Weeks per department</label>
                        <select name="weeks_per_block" class="form-select form-select-sm">
                            <option value="1" {{ (int) $scheduleWeeksPerBlock === 1 ? 'selected' : '' }}>1 week</option>
                            <option value="2" {{ (int) $scheduleWeeksPerBlock === 2 ? 'selected' : '' }}>2 weeks</option>
                        </select>
                    </div>
                    <div class="col-auto d-flex flex-wrap gap-1">
                        <button type="submit" class="btn btn-primary btn-sm" formtarget="_blank"><i class="bi bi-journal-text me-1"></i>Print full roster (all weeks)</button>
                        <button type="submit" class="btn btn-outline-primary btn-sm" formaction="{{ route('clinical-rotations.roster.all-weeks.csv', $clinical_rotation_round) }}"><i class="bi bi-file-earmark-excel me-1"></i>Excel / CSV (all weeks)</button>
                    </div>
                </form>
            @endif
            @if(in_array((int) $clinical_rotation_round->nta_level, [4, 5, 6], true))
                <div class="w-100 small text-muted mb-1"><strong>Rotation schedule</strong></div>
                <form method="GET" action="{{ route('clinical-rotations.schedule.print', $clinical_rotation_round) }}" class="row g-2 align-items-end flex-wrap w-100">
                    <div class="col-auto">
                        <label class="form-label small mb-0">Schedule starts (Monday)</label>
                        <input type="date" name="start" class="form-control form-control-sm" value="{{ $clinical_rotation_round->rotation_week_monday?->format('Y-m-d') ?? '' }}">
                    </div>
                    <div class="col-auto">
                        <label class="form-label small mb-0">Weeks per department</label>
                        <select name="weeks_per_block" class="form-select form-select-sm">
                            <option value="1" {{ (int) $scheduleWeeksPerBlock === 1 ? 'selected' : '' }}>1 week</option>
                            <option value="2" {{ (int) $scheduleWeeksPerBlock === 2 ? 'selected' : '' }}>2 weeks</option>
                        </select>
                    </div>
                    <div class="col-auto d-flex flex-wrap gap-1">
                        <button type="submit" class="btn btn-outline-dark btn-sm" formtarget="_blank"><i class="bi bi-calendar3-range me-1"></i>Print</button>
                        <button type="submit" class="btn btn-outline-dark btn-sm" formaction="{{ route('clinical-rotations.schedule.export', $clinical_rotation_round) }}" name="format" value="docx"><i class="bi bi-file-earmark-word me-1"></i>Word</button>
                        <button type="submit" class="btn btn-outline-dark btn-sm" formaction="{{ route('clinical-rotations.schedule.export', $clinical_rotation_round) }}" name="format" value="pdf"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</button>
                    </div>
                </form>
            @endif
        </div>
        <p class="small text-muted mb-2"><strong>Attendance (CSV)</strong></p>
        <form method="GET" action="{{ route('clinical-rotations.attendance.round-csv', $clinical_rotation_round) }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Week (Monday)</label>
                <input type="date" name="week_start" class="form-control form-control-sm" required value="{{ old('week_start', $defaultAttendanceWeek) }}">
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-download me-1"></i>Download attendance (all groups)</button>
            </div>
        </form>
    </div>
</div>

@if($clinical_rotation_round->rotation_week_monday && $clinical_rotation_round->rotation_week_friday)
<div class="alert alert-secondary small py-2 mb-3">
    <strong>Rotation dates (Mon–Fri):</strong> {{ $clinical_rotation_round->rotation_week_monday->format('l j F Y') }} to {{ $clinical_rotation_round->rotation_week_friday->format('l j F Y') }}.
</div>
@endif
<div class="alert alert-info small py-2 mb-3">
    <strong>Active at this programme &amp; NTA level:</strong> <strong>{{ $activeAtLevel ?? $eligibleTotal }}</strong>.
    <strong>Need clinical rotation</strong> (registered a rotation module for {{ $clinical_rotation_round->semester?->periodName() ?? 'this semester' }}): <strong>{{ $eligibleTotal }}</strong>.
    @if(($excludedFromRotation ?? 0) > 0)
        · <strong>Excluded:</strong> {{ $excludedFromRotation }} (classroom-only repeats, e.g. pathology, or no module registration yet)
    @endif
    <strong>Placed in a rotation group:</strong> <strong>{{ $assigned }}</strong>
    @if($eligibleTotal > 0)
        · <strong>Group sizes (1–{{ $groupSlotCount }}):</strong> {{ implode(', ', $groupSizes) }}
    @endif
    @if($unassigned > 0)
        <span class="text-warning d-block mt-1"><strong>Note:</strong> {{ $unassigned }} rotation-eligible student(s) not in a group — use <strong>Regenerate</strong> after students register modules or fix programme/level/status.</span>
    @endif
</div>

<div class="card card-landing mb-4">
    <div class="card-header-landing"><i class="bi bi-arrow-repeat me-2"></i>Re-divide rotation groups</div>
    <div class="card-body">
        <p class="small text-muted mb-2">Re-divides <strong>rotation-eligible</strong> students (registered at least one module that requires clinical rotation for this semester) evenly across all <strong>{{ $groupSlotCount }}</strong> groups. Clears saved <strong>Mon–Fri attendance</strong> for this round.</p>
        <form method="POST" action="{{ route('clinical-rotations.regenerate', $clinical_rotation_round) }}" class="d-inline">
            @csrf
            <button type="button" class="btn btn-warning btn-sm" data-swal-confirm data-swal-title="Regenerate groups?" data-swal-text="Re-divides all students across rotation groups and clears weekly attendance for this round."><i class="bi bi-shuffle me-1"></i>Regenerate groups</button>
        </form>
    </div>
</div>

@foreach($clinical_rotation_round->groups as $group)
    <div class="card card-landing mb-3">
        <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
            <span><i class="bi bi-people me-2"></i>Group {{ $group->slot_number }} — {{ $group->name }}</span>
            <a href="{{ route('clinical-rotations.attendance.edit', [$clinical_rotation_round, $group]) }}" class="btn btn-sm btn-success"><i class="bi bi-calendar-week me-1"></i>Attendance (Mon–Fri)</a>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('clinical-rotations.groups.update', [$clinical_rotation_round, $group]) }}" class="row g-3 mb-3">
                @csrf
                @method('PUT')
                <div class="col-md-4">
                    <label class="form-label small">Group name</label>
                    <input type="text" name="name" class="form-control form-control-sm" value="{{ $group->name }}" required maxlength="150">
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Department (Semester II block)</label>
                    <select name="department_code" class="form-select form-select-sm" required>
                        @foreach($departmentLabels as $code => $label)
                            <option value="{{ $code }}" {{ $group->department_code === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Hospital / site</label>
                    @php $hospitalSelected = \App\Support\ClinicalRotationCatalog::normalizeHospitalCode($group->hospital_code) ?? $group->hospital_code; @endphp
                    <select name="hospital_code" class="form-select form-select-sm">
                        <option value="">— Select —</option>
                        @foreach($hospitalLabels as $code => $label)
                            <option value="{{ $code }}" {{ (string) $hospitalSelected === (string) $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save</button>
                </div>
            </form>
            <div class="small text-uppercase text-muted fw-semibold mb-1">Students in this group</div>
            <div class="table-responsive border rounded">
                <table class="table table-sm table-bordered mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:3rem">SN</th>
                            <th>Student name</th>
                            <th>NACTVET reg. no.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($group->students as $stu)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $stu->full_name }}</td>
                                <td class="font-monospace small">{{ $stu->registrationNumberDisplay() ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="text-muted text-center py-2">No students assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endforeach
@endsection
