@extends('layouts.app')
@section('title', 'Add exam slots')
@section('content')
@php
    use Illuminate\Support\Arr;
    $oldCourseIds = Arr::wrap(old('course_ids', []));
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('exam-slots.index') }}">Exam timetable</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h1 class="page-title-landing mb-0">
        <i class="bi bi-calendar-plus me-2 opacity-90"></i>Add exam slots
        @include('partials.help-tip', ['text' => 'Choose the same date, time, and exam format for every module in this session (e.g. morning theory papers for NTA 4-6, then a separate session for practical / OSCE / OSPE). Ticking a module confirms it for this session (shown faint) until you use Clear session.', 'placement' => 'bottom'])
    </h1>
</div>
<div class="card card-landing">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('exam-slots.store') }}" method="POST" id="examSlotForm">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Semester</label>
                    <select class="form-select" name="semester_id" id="semester_id" required>
                        <option value="">— Select —</option>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ (string) ($semesterId ?? old('semester_id')) === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Programme <span class="text-muted small">(optional — narrows modules)</span></label>
                    <select class="form-select" id="programme_filter">
                        <option value="">All programmes</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ (string) ($programmeId ?? '') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Modules for this session <span class="text-danger">*</span></label>
                    <div class="d-flex flex-wrap gap-2 mb-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_select_all_modules"><i class="bi bi-check2-all me-1"></i>Select all</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn_clear_modules"><i class="bi bi-eraser me-1"></i>Clear session</button>
                    </div>
                    <div id="course_list_wrap" class="border rounded p-3 bg-light module-pick-wrap" style="max-height: 320px; overflow: auto;">
                        @if($courses->isEmpty())
                            <p class="text-muted small mb-0" id="course_list_empty">Choose a semester to load modules offered in that semester.</p>
                        @else
                            @foreach($courses as $c)
                                @php $wasOld = in_array((string) $c->id, array_map('strval', $oldCourseIds), true); @endphp
                                <div class="form-check mb-1 module-pick-row{{ $wasOld ? ' module-locked' : '' }}" data-course-id="{{ $c->id }}">
                                    @if($wasOld)
                                        <input type="hidden" name="course_ids[]" value="{{ $c->id }}" class="js-module-course-id">
                                    @endif
                                    <input class="form-check-input module-cb" type="checkbox" id="course_cb_{{ $c->id }}"
                                        data-course-id="{{ $c->id }}"
                                        {{ $wasOld ? 'checked disabled' : '' }}>
                                    <label class="form-check-label small" for="course_cb_{{ $c->id }}">
                                        <span class="font-monospace">{{ $c->code }}</span> — {{ $c->name }}
                                        <span class="text-muted">(NTA L{{ $c->resolvedNtaLevel() }})</span>
                                    </label>
                                </div>
                            @endforeach
                        @endif
                    </div>
                    <div class="form-text">Only modules linked to the selected semester in the module catalogue are listed. Order: NTA level, then module code.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assessment</label>
                    <select class="form-select" name="assessment_type" required>
                        @foreach(\App\Models\ExamSlot::assessmentOptions() as $val => $label)
                            <option value="{{ $val }}" {{ old('assessment_type', \App\Models\ExamSlot::ASSESSMENT_CAT1) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam format</label>
                    <select class="form-select" name="exam_format" required>
                        @foreach(\App\Models\ExamSlot::formatOptions() as $val => $label)
                            <option value="{{ $val }}" {{ old('exam_format', \App\Models\ExamSlot::FORMAT_THEORY) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Theory papers are listed first on the timetable; then practical, OSCE, and OSPE.</div>
                </div>
                <div class="col-md-4"><label class="form-label">Exam date</label><input type="date" class="form-control" name="exam_date" value="{{ old('exam_date') }}" required></div>
                <div class="col-md-2"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" value="{{ old('start_time') }}"></div>
                <div class="col-md-2"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" value="{{ old('end_time') }}"></div>
                <div class="col-md-4"><label class="form-label">Room</label><input type="text" class="form-control" name="room" value="{{ old('room') }}"></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea></div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i>Save session</button>
                    <a href="{{ route('exam-slots.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@push('styles')
<style>
    .module-pick-row.module-locked {
        opacity: 0.52;
    }
    .module-pick-row.module-locked .form-check-label {
        color: #6c757d;
    }
</style>
@endpush
@push('scripts')
<script>
(function () {
    const coursesUrl = @json(route('exam-slots.courses-json'));
    const semesterEl = document.getElementById('semester_id');
    const programmeEl = document.getElementById('programme_filter');
    const wrap = document.getElementById('course_list_wrap');
    const preserveIds = @json($oldCourseIds);
    const form = document.getElementById('examSlotForm');

    function escapeHtml(s) {
        const d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function lockCheckbox(cb) {
        const row = cb.closest('.module-pick-row');
        if (!row) return;
        const id = cb.getAttribute('data-course-id') || row.getAttribute('data-course-id');
        row.querySelectorAll('.js-module-course-id').forEach(function (el) { el.remove(); });
        cb.checked = true;
        cb.disabled = true;
        row.classList.add('module-locked');
        const hid = document.createElement('input');
        hid.type = 'hidden';
        hid.name = 'course_ids[]';
        hid.value = id;
        hid.className = 'js-module-course-id';
        row.insertBefore(hid, row.firstChild);
    }

    function unlockAll() {
        wrap.querySelectorAll('.module-pick-row').forEach(function (row) {
            row.classList.remove('module-locked');
            row.querySelectorAll('.js-module-course-id').forEach(function (el) { el.remove(); });
            const cb = row.querySelector('.module-cb');
            if (cb) {
                cb.disabled = false;
                cb.checked = false;
            }
        });
    }

    function lockAllAvailable() {
        wrap.querySelectorAll('.module-cb:not(:disabled)').forEach(function (cb) {
            if (!cb.checked) {
                cb.checked = true;
            }
            lockCheckbox(cb);
        });
    }

    function renderCourseList(courses) {
        if (!courses.length) {
            wrap.innerHTML = '<p class="text-muted small mb-0" id="course_list_empty">No modules for this semester / programme.</p>';
            return;
        }
        let html = '';
        const selected = new Set(preserveIds.map(function (id) { return String(id); }));
        courses.forEach(function (c) {
            const id = 'course_cb_' + c.id;
            const locked = selected.has(String(c.id));
            const lvl = c.nta_level != null ? ' <span class="text-muted">(NTA L' + escapeHtml(String(c.nta_level)) + ')</span>' : '';
            html += '<div class="form-check mb-1 module-pick-row' + (locked ? ' module-locked' : '') + '" data-course-id="' + c.id + '">';
            if (locked) {
                html += '<input type="hidden" name="course_ids[]" value="' + c.id + '" class="js-module-course-id">';
            }
            html += '<input class="form-check-input module-cb" type="checkbox" data-course-id="' + c.id + '" id="' + id + '"';
            if (locked) {
                html += ' checked disabled';
            }
            html += '>';
            html += '<label class="form-check-label small" for="' + id + '"><span class="font-monospace">' + escapeHtml(c.code) + '</span> — ' + escapeHtml(c.name) + lvl;
            html += '</label></div>';
        });
        wrap.innerHTML = html;
    }

    wrap.addEventListener('change', function (e) {
        const t = e.target;
        if (!t.classList || !t.classList.contains('module-cb')) return;
        if (t.checked && !t.disabled) {
            lockCheckbox(t);
        }
    });

    async function refreshCourses() {
        const semesterId = semesterEl.value;
        const programmeId = programmeEl.value;
        if (!semesterId) {
            wrap.innerHTML = '<p class="text-muted small mb-0">Choose a semester to load modules offered in that semester.</p>';
            return;
        }
        wrap.innerHTML = '<p class="text-muted small mb-0">Loading…</p>';
        const params = new URLSearchParams({ semester_id: semesterId });
        if (programmeId) params.set('programme_id', programmeId);
        try {
            const res = await fetch(coursesUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            renderCourseList(data.courses || []);
        } catch (err) {
            wrap.innerHTML = '<p class="text-danger small mb-0">Could not load modules.</p>';
        }
    }

    document.getElementById('btn_select_all_modules').addEventListener('click', function () {
        lockAllAvailable();
    });
    document.getElementById('btn_clear_modules').addEventListener('click', function () {
        unlockAll();
    });

    semesterEl.addEventListener('change', refreshCourses);
    programmeEl.addEventListener('change', refreshCourses);

    form.addEventListener('submit', function (e) {
        const semesterId = semesterEl.value;
        if (!semesterId) return;
        const n = wrap.querySelectorAll('input.js-module-course-id').length;
        if (n === 0) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'No modules selected', text: 'Select at least one module for this date and time (tick modules to confirm — use Clear session to start over).' });
        }
    });
})();
</script>
@endpush
@endsection
