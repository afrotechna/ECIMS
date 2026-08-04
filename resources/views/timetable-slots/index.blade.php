@extends('layouts.app')
@section('title', 'Timetable')
@section('content')
<nav class="student-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a> / <span>Timetable</span></nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h1 class="page-title-landing mb-0">Timetable</h1>
    <a href="{{ route('timetable-slots.create') }}" class="btn btn-primary btn-sm">Add slot</a>
</div>

<form method="GET" class="mb-3 row g-2 align-items-end">
    <div class="col-auto">
        <label class="form-label small mb-0">Semester</label>
        <select name="semester_id" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($semesters as $s)
                <option value="{{ $s->id }}" {{ (string) $semesterId === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto">
        <label class="form-label small mb-0">Programme</label>
        <select name="programme_id" class="form-select form-select-sm">
            <option value="">All</option>
            @foreach($programmes as $p)
                <option value="{{ $p->id }}" {{ (string) $programmeId === (string) $p->id ? 'selected' : '' }}>{{ $p->code }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
</form>

@canModule('timetable', 'create')
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-magic me-2"></i>Auto-generate weekly timetable</div>
    <div class="card-body">
        <p class="small text-muted mb-3">Pick a semester, choose which of its modules to schedule, then randomly fill the standard Monday–Friday week (07:30–09:30, 10:00–12:30, 13:30–16:30 with a morning and lunch break) with two sessions per module. This replaces any existing slots for the selected modules in that semester.</p>
        <form method="POST" action="{{ route('timetable-slots.auto-generate') }}" id="autoGenerateForm" data-swal-confirm data-swal-title="Generate the weekly timetable?" data-swal-text="Existing slots for the selected modules in this semester will be replaced.">
            @csrf
            <div class="row g-2 align-items-end mb-2">
                <div class="col-auto">
                    <label class="form-label small mb-0">Semester</label>
                    <select name="semester_id" id="autoGenSemester" class="form-select form-select-sm" required>
                        <option value="">Select</option>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ (string) $semesterId === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary" id="autoGenSubmit" disabled><i class="bi bi-magic me-1"></i> Generate randomly</button>
                </div>
            </div>
            <div id="autoGenModulesWrap" class="d-none">
                <div class="form-check mb-1">
                    <input class="form-check-input" type="checkbox" id="autoGenSelectAll" checked>
                    <label class="form-check-label small fw-semibold" for="autoGenSelectAll">Select all modules</label>
                </div>
                <div id="autoGenModulesList" class="row g-1"></div>
            </div>
            <p id="autoGenEmpty" class="small text-muted mb-0 d-none">No active modules found for this semester.</p>
        </form>
    </div>
</div>
@endcanModule

@php
    $dayLabels = collect(\App\Models\TimetableSlot::WEEK_DAYS)->mapWithKeys(fn ($d) => [$d => \App\Models\TimetableSlot::DAYS[$d]]);
    $canManageSlots = auth()->user()->canModule('timetable', 'delete');
@endphp
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-calendar-week me-2"></i>Weekly grid</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm mb-0 align-middle" style="min-width:760px;">
                <thead class="table-light">
                    <tr>
                        <th style="width:130px;">Session</th>
                        @foreach($dayLabels as $day => $label)
                        <th class="text-center">{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach(\App\Models\TimetableSlot::DAILY_SESSIONS as $session)
                    <tr>
                        <td class="small fw-semibold text-muted">{{ $session['label'] }}</td>
                        @foreach($dayLabels as $day => $label)
                        @php $cellSlot = $grid[$day][$session['start']] ?? null; @endphp
                        <td class="text-center {{ $cellSlot ? 'bg-light' : '' }}" style="min-width:120px;">
                            @if($cellSlot)
                                <div class="small fw-semibold">{{ $cellSlot->course->code ?? '—' }}</div>
                                <div class="small text-muted">{{ \Illuminate\Support\Str::limit($cellSlot->course->name ?? '', 20) }}</div>
                                @if($cellSlot->room)
                                <div class="small text-muted">{{ $cellSlot->room }}</div>
                                @endif
                                @if($canManageSlots)
                                <form method="POST" action="{{ route('timetable-slots.destroy', $cellSlot) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    @if($semesterId)<input type="hidden" name="semester_id" value="{{ $semesterId }}">@endif
                                    @include('partials.action-delete', ['swalTitle' => 'Remove this timetable slot?', 'class' => 'btn-sm mt-1'])
                                </form>
                                @endif
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                    <tr>
                        <td class="small fw-semibold text-muted">07:30 – 10:00</td>
                        <td colspan="{{ count($dayLabels) }}" class="text-center small text-muted">30 min break</td>
                    </tr>
                    <tr>
                        <td class="small fw-semibold text-muted">12:30 – 13:30</td>
                        <td colspan="{{ count($dayLabels) }}" class="text-center small text-muted">Lunch break</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

@if($otherSlots->isNotEmpty())
@php
    $bulkDelete = [
        'bulkModule' => 'timetable',
        'bulkAction' => route('timetable-slots.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteTimetableSlots',
        'bulkTableId' => 'timetableSlotsTable',
        'bulkItemCount' => $otherSlots->count(),
        'bulkHidden' => array_filter(['semester_id' => $semesterId ?? null]),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>Other scheduled slots <span class="text-muted small">(outside the standard sessions)</span></span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0" id="timetableSlotsTable">
            <thead class="table-light">
                <tr>
                    @include('partials.bulk-delete.th', $bulkDelete)
                    <th>Day</th>
                    <th>Time</th>
                    <th>Course</th>
                    <th>Room</th>
                    @if($canManageSlots)
                    <th class="text-end">Actions</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach($otherSlots as $slot)
                <tr>
                    @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $slot->id]))
                    <td>{{ \App\Models\TimetableSlot::DAYS[$slot->day_of_week] ?? $slot->day_of_week }}</td>
                    <td>{{ $slot->start_time }} – {{ $slot->end_time }}</td>
                    <td>{{ $slot->course ? $slot->course->code : '' }} {{ $slot->course ? $slot->course->name : '' }}</td>
                    <td>{{ $slot->room ?? '—' }}</td>
                    @if($canManageSlots)
                    <td class="text-end text-nowrap">
                        <form method="POST" action="{{ route('timetable-slots.destroy', $slot) }}" class="d-inline">
                            @csrf
                            @method('DELETE')
                            @if($semesterId)<input type="hidden" name="semester_id" value="{{ $semesterId }}">@endif
                            @include('partials.action-delete', ['swalTitle' => 'Remove this timetable slot?'])
                        </form>
                    </td>
                    @endif
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@include('partials.bulk-delete.scripts')
@endif

@push('scripts')
<script>
(function () {
    var semesterSelect = document.getElementById('autoGenSemester');
    var modulesWrap = document.getElementById('autoGenModulesWrap');
    var modulesList = document.getElementById('autoGenModulesList');
    var emptyMsg = document.getElementById('autoGenEmpty');
    var selectAll = document.getElementById('autoGenSelectAll');
    var submitBtn = document.getElementById('autoGenSubmit');
    if (!semesterSelect) return;

    function setSubmitEnabled() {
        var anyChecked = modulesList.querySelectorAll('input[name="course_ids[]"]:checked').length > 0;
        submitBtn.disabled = !anyChecked;
    }

    function loadModules() {
        var semesterId = semesterSelect.value;
        modulesList.innerHTML = '';
        modulesWrap.classList.add('d-none');
        emptyMsg.classList.add('d-none');
        submitBtn.disabled = true;
        if (!semesterId) return;

        fetch('{{ route('timetable-slots.courses-by-semester') }}?semester_id=' + encodeURIComponent(semesterId))
            .then(function (r) { return r.json(); })
            .then(function (courses) {
                if (!courses.length) {
                    emptyMsg.classList.remove('d-none');
                    return;
                }
                courses.forEach(function (c) {
                    var col = document.createElement('div');
                    col.className = 'col-md-4 col-lg-3';
                    col.innerHTML = '<div class="form-check">' +
                        '<input class="form-check-input" type="checkbox" name="course_ids[]" value="' + c.id + '" id="autoGenCourse' + c.id + '" checked>' +
                        '<label class="form-check-label small" for="autoGenCourse' + c.id + '">' + c.code + '</label>' +
                        '</div>';
                    modulesList.appendChild(col);
                });
                modulesWrap.classList.remove('d-none');
                selectAll.checked = true;
                setSubmitEnabled();
            });
    }

    semesterSelect.addEventListener('change', loadModules);
    selectAll.addEventListener('change', function () {
        modulesList.querySelectorAll('input[name="course_ids[]"]').forEach(function (cb) { cb.checked = selectAll.checked; });
        setSubmitEnabled();
    });
    modulesList.addEventListener('change', function (e) {
        if (e.target.name === 'course_ids[]') setSubmitEnabled();
    });

    if (semesterSelect.value) loadModules();
})();
</script>
@endpush
@endsection
