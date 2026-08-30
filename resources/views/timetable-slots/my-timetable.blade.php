@extends('layouts.app')
@section('title', 'My Class Timetable')
@section('content')
@php
    $timetable = $timetable ?? ['grid_semester_one' => [], 'grid_semester_two' => [], 'semester_one' => null, 'semester_two' => null];
    $dayLabels = collect(\App\Models\TimetableSlot::WEEK_DAYS)->mapWithKeys(fn ($d) => [$d => \App\Models\TimetableSlot::DAYS[$d]]);
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Home</a>
    <span class="mx-2">/</span>
    <span>My Class Timetable</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-clock-history me-2 opacity-90"></i>My Class Timetable</h1>
        <p class="page-subtitle-landing mb-0">
            {{ $student->programme->name ?? '' }}
            @if($student->programme?->code) ({{ $student->programme->code }}) @endif
            &middot; {{ \App\Models\Student::NTA_LEVELS[(int) $student->nta_level] ?? 'Student' }}
        </p>
    </div>
    <span class="badge bg-light text-dark fs-6">Academic year {{ $academicYearStart }}/{{ $academicYearStart + 1 }}</span>
</div>

<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-funnel me-2"></i>Academic year</div>
    <div class="card-body py-3">
        <form method="GET" action="{{ route('my.timetable') }}" class="row g-3 align-items-end">
            <div class="col-md-6">
                <label for="academic_year" class="form-label">Session</label>
                <select name="academic_year" id="academic_year" class="form-select" data-no-search>
                    @foreach($academicYearOptions ?? [] as $year => $label)
                    <option value="{{ $year }}" {{ (int) ($academicYearStart ?? 0) === (int) $year ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i> Show timetable</button>
            </div>
        </form>
    </div>
</div>

@foreach([
    ['label' => 'Semester one', 'term' => \App\Models\Semester::PERIOD_FIRST, 'grid' => $timetable['grid_semester_one'] ?? [], 'semester' => $timetable['semester_one'] ?? null],
    ['label' => 'Semester two', 'term' => \App\Models\Semester::PERIOD_SECOND, 'grid' => $timetable['grid_semester_two'] ?? [], 'semester' => $timetable['semester_two'] ?? null],
] as $block)
@php
    $grid = $block['grid'];
    $hasSlots = collect($grid)->flatten()->filter()->isNotEmpty();
@endphp
<div class="card card-landing mb-3">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div>
            <span class="text-uppercase fw-semibold">{{ $block['label'] }}</span>
            @if($block['semester'])
            <span class="small opacity-75">{{ $block['semester']->label }}</span>
            @endif
        </div>
        @if($hasSlots)
        <a class="btn btn-outline-light btn-sm" target="_blank" href="{{ route('my.timetable.print', ['term' => $block['term'], 'academic_year' => $academicYearStart]) }}">
            <i class="bi bi-file-earmark-pdf me-1"></i>Download PDF
        </a>
        @endif
    </div>
    <div class="card-body p-0">
        @if($hasSlots)
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle" style="table-layout:fixed;width:100%;">
                <thead class="table-light">
                    <tr>
                        <th style="width:110px;white-space:nowrap;">Time</th>
                        @foreach($dayLabels as $day => $label)
                        <th class="text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\TimetableSlot::DAILY_SESSIONS as $sessionIndex => $session)
                    <tr>
                        <td class="small fw-semibold text-muted" style="white-space:nowrap;">{{ $session['label'] }}</td>
                        @foreach($dayLabels as $day => $label)
                        @php $cellSlot = $grid[$day][$session['start']] ?? null; @endphp
                        <td class="text-center {{ $cellSlot ? 'bg-light' : '' }}">
                            @if($cellSlot)
                                @if($cellSlot->course?->code)
                                <div class="small fw-semibold">{{ $cellSlot->course->code }}</div>
                                @endif
                                <div class="small">{{ $cellSlot->course->name ?? '—' }}</div>
                                <div class="small fw-semibold">{{ $cellSlot->lecturer ? 'Tutor: '.$cellSlot->lecturer : ' ' }}</div>
                                @if($cellSlot->room)
                                <div class="small text-muted">{{ $cellSlot->room }}</div>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @if($break = \App\Models\TimetableSlot::BREAKS[$sessionIndex] ?? null)
                    <tr>
                        <td class="small fw-semibold text-muted">{{ $break['start'] }} – {{ $break['end'] }}</td>
                        <td colspan="{{ count($dayLabels) }}" class="text-center small text-muted fw-semibold">{{ $break['label'] }}</td>
                    </tr>
                    @endif
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <p class="text-muted text-center py-4 mb-0">No timetable published for {{ strtolower($block['label']) }}.</p>
        @endif
    </div>
</div>
@endforeach
@endsection
