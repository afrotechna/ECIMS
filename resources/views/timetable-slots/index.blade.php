@extends('layouts.app')
@section('title', 'Timetable')
@section('content')
<nav class="student-breadcrumb timetable-no-print"><a href="{{ route('dashboard') }}">Dashboard</a> / <span>Timetable</span></nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2 timetable-no-print">
    <h1 class="page-title-landing mb-0">Timetable</h1>
    <a href="{{ route('timetable-slots.create') }}" class="btn btn-primary btn-sm">Add slot</a>
</div>

<form method="GET" class="mb-3 row g-2 align-items-end timetable-no-print">
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
<div class="card card-landing mb-3 timetable-no-print">
    <div class="card-header-landing"><i class="bi bi-magic me-2"></i>Auto-generate weekly timetable</div>
    <div class="card-body">
        <ol class="small text-muted mb-3 ps-3">
            <li>Pick the semester, then narrow to one department/level (recommended) — <strong>Programme</strong> first, then <strong>Level</strong>.</li>
            <li>Untick any modules you don't want scheduled; each module gets two sessions.</li>
            <li>Click <strong>Generate randomly</strong> to fill Monday–Friday (07:30–09:30, 10:00–12:00, 13:00–15:00, 15:00–16:30, with a tea and lunch break). This replaces any existing slots for the selected modules in that semester.</li>
        </ol>
        <form method="POST" action="{{ route('timetable-slots.auto-generate') }}" id="autoGenerateForm">
            @csrf
            <div class="row g-2 align-items-end mb-2">
                <div class="col-auto">
                    <label class="form-label small mb-0">Semester</label>
                    <select name="semester_id" id="autoGenSemester" class="form-select form-select-sm" required>
                        <option value="">Select</option>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ (string) $semesterId === (string) $s->id || (! $semesterId && $semesters->count() === 1) ? 'selected' : '' }}>{{ $s->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Programme <span class="text-muted fw-normal">(recommended)</span></label>
                    <select id="autoGenProgramme" class="form-select form-select-sm">
                        <option value="">All programmes</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}">{{ $p->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-0">Level <span class="text-muted fw-normal">(recommended)</span></label>
                    <select id="autoGenLevel" class="form-select form-select-sm">
                        <option value="">All levels</option>
                        <option value="4">NTA Level 4</option>
                        <option value="5">NTA Level 5</option>
                        <option value="6">NTA Level 6</option>
                    </select>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary" id="autoGenSubmit" disabled data-swal-confirm data-swal-title="Generate the weekly timetable?" data-swal-text="Existing slots for the selected modules in this semester will be replaced."><i class="bi bi-magic me-1"></i> Generate randomly</button>
                </div>
            </div>
            <div id="autoGenModulesWrap" class="d-none">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" id="autoGenSelectAll" checked>
                        <label class="form-check-label small fw-semibold" for="autoGenSelectAll">Select all modules</label>
                    </div>
                    <span id="autoGenCapacity" class="small text-muted"></span>
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

@php
    $panelsWithSlots = collect($panels)->filter(fn ($p) => collect($p['grid'])->flatten()->filter()->isNotEmpty())->values();
@endphp

@forelse($panelsWithSlots as $panel)
@php
    $grid = $panel['grid'];
    $panelKey = ($panel['programme']->id ?? 0).'-'.$panel['level'];
@endphp
<div class="card card-landing mb-3 timetable-panel" data-panel-key="{{ $panelKey }}">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <div><i class="bi bi-calendar-week me-2"></i>{{ $panel['programme']->name ?? 'Department' }} ({{ $panel['programme']->code ?? '—' }}) — {{ $panel['level_label'] }}</div>
            <div class="small opacity-75">
                {{ config('college.school_name', config('college.institution_name')) }}
                @if($selectedSemester ?? null)
                    · {{ $selectedSemester->label }}
                    @if($selectedSemester->start_date && $selectedSemester->end_date)
                        · {{ $selectedSemester->start_date->format('d M Y') }} – {{ $selectedSemester->end_date->format('d M Y') }}
                    @endif
                @endif
            </div>
        </div>
        <div class="timetable-no-print d-flex gap-1">
            @if($panel['semester_id'])
            <a class="btn btn-outline-light btn-sm" target="_blank" href="{{ route('timetable-slots.print', ['semester_id' => $panel['semester_id'], 'programme_id' => $panel['programme']->id ?? '', 'nta_level' => $panel['level']]) }}">
                <i class="bi bi-printer me-1"></i>Print / PDF
            </a>
            <a class="btn btn-outline-light btn-sm" href="{{ route('timetable-slots.download-word', ['semester_id' => $panel['semester_id'], 'programme_id' => $panel['programme']->id ?? '', 'nta_level' => $panel['level']]) }}">
                <i class="bi bi-file-earmark-word me-1"></i>Word
            </a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
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
                        <td class="text-center {{ $cellSlot ? 'bg-light' : '' }}" style="white-space:normal;">
                            @if($cellSlot)
                                @if($cellSlot->course?->code)
                                <div class="small fw-semibold">{{ $cellSlot->course->code }}</div>
                                @endif
                                <div class="small">{{ $cellSlot->course->name ?? '—' }}</div>
                                <div class="small fw-semibold">{{ $cellSlot->lecturer ? 'Tutor: '.$cellSlot->lecturer : ' ' }}</div>
                                @if($cellSlot->room)
                                <div class="small text-muted">{{ $cellSlot->room }}</div>
                                @endif
                                @if($canManageSlots)
                                <form method="POST" action="{{ route('timetable-slots.destroy', $cellSlot) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    @if($semesterId)<input type="hidden" name="semester_id" value="{{ $semesterId }}">@endif
                                    @include('partials.action-delete', ['swalTitle' => 'Remove this timetable slot?', 'class' => 'btn-sm mt-1 timetable-no-print'])
                                </form>
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
    </div>
</div>
@empty
<div class="card card-landing mb-3">
    <div class="card-body">
        <p class="text-muted mb-0">No timetable slots created yet. Use "Auto-generate weekly timetable" above, or add slots manually.</p>
    </div>
</div>
@endforelse

@push('scripts')
<script>
(function () {
    var semesterSelect = document.getElementById('autoGenSemester');
    var levelSelect = document.getElementById('autoGenLevel');
    var programmeSelect = document.getElementById('autoGenProgramme');
    var modulesWrap = document.getElementById('autoGenModulesWrap');
    var modulesList = document.getElementById('autoGenModulesList');
    var emptyMsg = document.getElementById('autoGenEmpty');
    var selectAll = document.getElementById('autoGenSelectAll');
    var submitBtn = document.getElementById('autoGenSubmit');
    var capacityLabel = document.getElementById('autoGenCapacity');
    if (!semesterSelect) return;

    var maxModules = {{ (int) floor(count(\App\Models\TimetableSlot::WEEK_DAYS) * count(\App\Models\TimetableSlot::DAILY_SESSIONS) / 2) }};

    var badgeClass = {
        CMT: 'bg-primary-subtle text-primary-emphasis',
        MLT: 'bg-success-subtle text-success-emphasis',
        DDR: 'bg-warning-subtle text-warning-emphasis'
    };

    function setSubmitEnabled() {
        var checkedCount = modulesList.querySelectorAll('input[name="course_ids[]"]:checked').length;
        var overCapacity = checkedCount > maxModules;
        submitBtn.disabled = checkedCount === 0 || overCapacity;
        capacityLabel.classList.toggle('text-danger', overCapacity);
        capacityLabel.classList.toggle('fw-semibold', overCapacity);
        capacityLabel.textContent = overCapacity
            ? checkedCount + ' selected — only ' + maxModules + ' modules fit this week. Uncheck some.'
            : checkedCount + ' of ' + maxModules + ' weekly slots used';
    }

    function loadModules() {
        var semesterId = semesterSelect.value;
        var level = levelSelect.value;
        var programmeId = programmeSelect.value;
        modulesList.innerHTML = '';
        modulesWrap.classList.add('d-none');
        emptyMsg.classList.add('d-none');
        submitBtn.disabled = true;
        if (!semesterId) return;

        var url = '{{ route('timetable-slots.courses-by-semester') }}?semester_id=' + encodeURIComponent(semesterId);
        if (level) url += '&nta_level=' + encodeURIComponent(level);
        if (programmeId) url += '&programme_id=' + encodeURIComponent(programmeId);

        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (courses) {
                if (!courses.length) {
                    emptyMsg.classList.remove('d-none');
                    return;
                }
                courses.forEach(function (c) {
                    var badge = c.programme_code
                        ? '<span class="badge ' + (badgeClass[c.programme_code] || 'bg-secondary-subtle text-secondary-emphasis') + ' me-1">' + c.programme_code + '</span>'
                        : '';
                    var col = document.createElement('div');
                    col.className = 'col-md-4 col-lg-3';
                    col.innerHTML = '<div class="form-check">' +
                        '<input class="form-check-input" type="checkbox" name="course_ids[]" value="' + c.id + '" id="autoGenCourse' + c.id + '" checked>' +
                        '<label class="form-check-label small" for="autoGenCourse' + c.id + '">' + badge + c.code + '</label>' +
                        '</div>';
                    modulesList.appendChild(col);
                });
                modulesWrap.classList.remove('d-none');
                selectAll.checked = true;
                setSubmitEnabled();
            });
    }

    semesterSelect.addEventListener('change', loadModules);
    levelSelect.addEventListener('change', loadModules);
    programmeSelect.addEventListener('change', loadModules);
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
