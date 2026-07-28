@extends('layouts.app')
@section('title', 'Edit exam slot')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('exam-slots.index') }}">Exam timetable</a>
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h1 class="page-title-landing mb-0"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit exam slot</h1>
</div>
<div class="card card-landing">
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif
        <form action="{{ route('exam-slots.update', $exam_slot) }}" method="POST" id="examSlotForm">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Semester</label>
                    <select class="form-select" name="semester_id" id="semester_id" required>
                        @foreach($semesters as $s)
                            <option value="{{ $s->id }}" {{ (string) old('semester_id', $exam_slot->semester_id) === (string) $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Programme <span class="text-muted small">(optional — narrows modules)</span></label>
                    <select class="form-select" id="programme_filter">
                        <option value="">All programmes</option>
                        @foreach($programmes as $p)
                            <option value="{{ $p->id }}" {{ $exam_slot->course && (string) $exam_slot->course->programme_id === (string) $p->id ? 'selected' : '' }}>{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Module (course)</label>
                    <select class="form-select" name="course_id" id="course_id" required>
                        @foreach($courses as $c)
                            <option value="{{ $c->id }}" {{ (string) old('course_id', $exam_slot->course_id) === (string) $c->id ? 'selected' : '' }}>{{ $c->code }} — {{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Assessment</label>
                    <select class="form-select" name="assessment_type" required>
                        @foreach(\App\Models\ExamSlot::assessmentOptions() as $val => $label)
                            <option value="{{ $val }}" {{ old('assessment_type', $exam_slot->assessment_type ?? \App\Models\ExamSlot::ASSESSMENT_CAT1) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Exam format</label>
                    <select class="form-select" name="exam_format" required>
                        @foreach(\App\Models\ExamSlot::formatOptions() as $val => $label)
                            <option value="{{ $val }}" {{ old('exam_format', $exam_slot->exam_format ?? \App\Models\ExamSlot::FORMAT_THEORY) === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4"><label class="form-label">Exam date</label><input type="date" class="form-control" name="exam_date" value="{{ old('exam_date', $exam_slot->exam_date->format('Y-m-d')) }}" required></div>
                <div class="col-md-2"><label class="form-label">Start time</label><input type="time" class="form-control" name="start_time" value="{{ old('start_time', $exam_slot->start_time) }}"></div>
                <div class="col-md-2"><label class="form-label">End time</label><input type="time" class="form-control" name="end_time" value="{{ old('end_time', $exam_slot->end_time) }}"></div>
                <div class="col-md-4"><label class="form-label">Room</label><input type="text" class="form-control" name="room" value="{{ old('room', $exam_slot->room) }}"></div>
                <div class="col-12"><label class="form-label">Notes</label><textarea class="form-control" name="notes" rows="2">{{ old('notes', $exam_slot->notes) }}</textarea></div>
                <div class="col-12"><button type="submit" class="btn btn-primary">Update</button> <a href="{{ route('exam-slots.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
            </div>
        </form>
        <div class="border-top pt-3 mt-4">
            <p class="small text-muted mb-2">Added this row by mistake? You can remove only this slot; other slots for the same session are not affected.</p>
            <form method="POST" action="{{ route('exam-slots.destroy', $exam_slot) }}">
                @csrf
                @method('DELETE')
                <button type="button" class="btn btn-outline-danger btn-sm" data-swal-confirm data-swal-title="Delete this exam slot permanently?"><i class="bi bi-trash me-1"></i>Delete this slot</button>
            </form>
        </div>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const coursesUrl = @json(route('exam-slots.courses-json'));
    const semesterEl = document.getElementById('semester_id');
    const programmeEl = document.getElementById('programme_filter');
    const courseEl = document.getElementById('course_id');
    const selectedCourseId = @json((string) old('course_id', $exam_slot->course_id));

    async function refreshCourses() {
        const semesterId = semesterEl.value;
        const programmeId = programmeEl.value;
        if (!semesterId) {
            return;
        }
        const params = new URLSearchParams({ semester_id: semesterId });
        if (programmeId) params.set('programme_id', programmeId);
        const prev = courseEl.value;
        courseEl.innerHTML = '<option value="">Loading…</option>';
        try {
            const res = await fetch(coursesUrl + '?' + params.toString(), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await res.json();
            const courses = data.courses || [];
            let html = '';
            if (!courses.length) {
                html = '<option value="">No modules for this semester / programme</option>';
            } else {
                courses.forEach(function (c) {
                    html += '<option value="' + c.id + '">' + c.code + ' — ' + c.name + '</option>';
                });
            }
            courseEl.innerHTML = html;
            const pick = selectedCourseId || prev;
            if (pick && courseEl.querySelector('option[value="' + pick + '"]')) {
                courseEl.value = String(pick);
            }
        } catch (e) {
            courseEl.innerHTML = '<option value="">Could not load modules</option>';
        }
    }

    semesterEl.addEventListener('change', refreshCourses);
    programmeEl.addEventListener('change', refreshCourses);
})();
</script>
@endpush
@endsection
