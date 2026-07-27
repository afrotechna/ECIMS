@extends('layouts.app')
@section('title', 'Rotation attendance')
@section('content')
@php
    $weekCarbon = \Carbon\Carbon::parse($weekStart);
    $prevWeek = $weekCarbon->copy()->subWeek()->format('Y-m-d');
    $nextWeek = $weekCarbon->copy()->addWeek()->format('Y-m-d');
    $daySelect = function (?bool $present): string {
        if ($present === true) {
            return '1';
        }
        if ($present === false) {
            return '0';
        }

        return '';
    };
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('clinical-rotations.index') }}">Clinical rotation</a>
    <span class="mx-2">/</span>
    <a href="{{ route('clinical-rotations.show', $clinical_rotation_round) }}">{{ $clinical_rotation_round->programme?->code }}</a>
    <span class="mx-2">/</span>
    <span>Attendance</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0"><i class="bi bi-calendar-week me-2 opacity-90"></i>Weekly attendance</h1>
        <p class="page-subtitle-landing mb-0">{{ $clinical_rotation_group->name }} · Week starting <strong>{{ $weekCarbon->format('l j M Y') }}</strong></p>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('clinical-rotations.attendance.group-csv', [$clinical_rotation_round, $clinical_rotation_group, 'week_start' => $weekStart]) }}" class="btn btn-success btn-sm"><i class="bi bi-download me-1"></i>Download attendance (CSV)</a>
        <a href="{{ route('clinical-rotations.show', $clinical_rotation_round) }}" class="btn btn-outline-secondary btn-sm">Back to round</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('warning'))
    <div class="alert alert-warning">{{ session('warning') }}</div>
@endif

<div class="card card-landing mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('clinical-rotations.attendance.edit', [$clinical_rotation_round, $clinical_rotation_group]) }}" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Week (Monday)</label>
                <input type="date" name="week_start" class="form-control form-control-sm" value="{{ $weekStart }}" required>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-primary btn-sm">Go</button>
            </div>
            <div class="col-auto ms-md-auto d-flex gap-1">
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('clinical-rotations.attendance.edit', [$clinical_rotation_round, $clinical_rotation_group]) }}?week_start={{ $prevWeek }}">← Prev week</a>
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('clinical-rotations.attendance.edit', [$clinical_rotation_round, $clinical_rotation_group]) }}?week_start={{ $nextWeek }}">Next week →</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing">Mark <strong>P</strong> (present) or <strong>A</strong> (absent); leave “—” if not recorded.</div>
    <div class="card-body p-0">
        <form method="POST" action="{{ route('clinical-rotations.attendance.store', [$clinical_rotation_round, $clinical_rotation_group]) }}">
            @csrf
            <input type="hidden" name="week_start" value="{{ $weekStart }}">
            <div class="table-responsive">
                <table class="table table-bordered table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:2.5rem">SN</th>
                            <th>Student name</th>
                            <th style="width:9rem">NACTVET reg. no.</th>
                            <th class="text-center">Mon</th>
                            <th class="text-center">Tue</th>
                            <th class="text-center">Wed</th>
                            <th class="text-center">Thu</th>
                            <th class="text-center">Fri</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($clinical_rotation_group->students as $stu)
                            @php
                                $m = $marks->get($stu->id);
                                $ri = $loop->index;
                            @endphp
                            <tr>
                                <td>
                                    {{ $loop->iteration }}
                                    <input type="hidden" name="rows[{{ $ri }}][student_id]" value="{{ $stu->id }}">
                                </td>
                                <td>{{ $stu->full_name }}</td>
                                <td class="font-monospace small">{{ $stu->registrationNumberDisplay() ?: '—' }}</td>
                                <td class="p-1">
                                    <select name="rows[{{ $ri }}][mon]" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="1" {{ $daySelect($m?->mon_present) === '1' ? 'selected' : '' }}>P</option>
                                        <option value="0" {{ $daySelect($m?->mon_present) === '0' ? 'selected' : '' }}>A</option>
                                    </select>
                                </td>
                                <td class="p-1">
                                    <select name="rows[{{ $ri }}][tue]" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="1" {{ $daySelect($m?->tue_present) === '1' ? 'selected' : '' }}>P</option>
                                        <option value="0" {{ $daySelect($m?->tue_present) === '0' ? 'selected' : '' }}>A</option>
                                    </select>
                                </td>
                                <td class="p-1">
                                    <select name="rows[{{ $ri }}][wed]" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="1" {{ $daySelect($m?->wed_present) === '1' ? 'selected' : '' }}>P</option>
                                        <option value="0" {{ $daySelect($m?->wed_present) === '0' ? 'selected' : '' }}>A</option>
                                    </select>
                                </td>
                                <td class="p-1">
                                    <select name="rows[{{ $ri }}][thu]" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="1" {{ $daySelect($m?->thu_present) === '1' ? 'selected' : '' }}>P</option>
                                        <option value="0" {{ $daySelect($m?->thu_present) === '0' ? 'selected' : '' }}>A</option>
                                    </select>
                                </td>
                                <td class="p-1">
                                    <select name="rows[{{ $ri }}][fri]" class="form-select form-select-sm">
                                        <option value="">—</option>
                                        <option value="1" {{ $daySelect($m?->fri_present) === '1' ? 'selected' : '' }}>P</option>
                                        <option value="0" {{ $daySelect($m?->fri_present) === '0' ? 'selected' : '' }}>A</option>
                                    </select>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-3 border-top bg-light">
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Save attendance</button>
            </div>
        </form>
    </div>
</div>
@endsection
