@extends('layouts.app')
@section('title', 'My Class Timetable')
@section('content')
@php
    $timetable = $timetable ?? ['slots_semester_one' => collect(), 'slots_semester_two' => collect()];
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
            Â· {{ \App\Models\Student::NTA_LEVELS[(int) $student->nta_level] ?? 'Student' }}
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

<div id="student-timetable-collapse-scope">
    @foreach([
        ['key' => 'one', 'label' => 'Semester one', 'slots' => $timetable['slots_semester_one'] ?? collect(), 'open' => true],
        ['key' => 'two', 'label' => 'Semester two', 'slots' => $timetable['slots_semester_two'] ?? collect(), 'open' => false],
    ] as $block)
    <div class="card card-landing mb-3 courses-tree-card">
        <div
            class="card-header-landing d-flex justify-content-between align-items-center gap-2 py-3 courses-fold-trigger {{ ($block['open'] ?? false) ? '' : 'collapsed' }}"
            data-bs-toggle="collapse"
            data-bs-target="#student-timetable-{{ $block['key'] }}"
            aria-expanded="{{ ($block['open'] ?? false) ? 'true' : 'false' }}"
            role="button"
            tabindex="0"
        >
            <h2 class="h5 mb-0 fw-semibold text-uppercase">{{ $block['label'] }}</h2>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-light text-dark">{{ $block['slots']->count() }} session(s)</span>
                <i class="bi bi-chevron-down courses-fold-icon flex-shrink-0"></i>
            </div>
        </div>
        <div id="student-timetable-{{ $block['key'] }}" class="collapse {{ ($block['open'] ?? false) ? 'show' : '' }} border-top border-light-subtle">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Module</th>
                                <th>Room</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($block['slots'] as $slot)
                            <tr>
                                <td>{{ \App\Models\TimetableSlot::DAYS[$slot->day_of_week] ?? $slot->day_of_week }}</td>
                                <td class="text-nowrap">{{ $slot->start_time }} â€“ {{ $slot->end_time }}</td>
                                <td>{{ $slot->course ? $slot->course->code.' — '.$slot->course->name : '—' }}</td>
                                <td>{{ $slot->room ?? $slot->venue ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-4">No timetable published for {{ strtolower($block['label']) }}.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

@push('styles')
<style>
.courses-fold-trigger { cursor: pointer; user-select: none; }
.courses-fold-icon { transition: transform 0.2s ease; display: inline-block; }
.courses-fold-trigger.collapsed .courses-fold-icon { transform: rotate(-90deg); }
</style>
@endpush
