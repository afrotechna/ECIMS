@extends('layouts.app')
@section('title', 'Add timetable slot')
@section('content')
<nav class="student-breadcrumb"><a href="{{ route('dashboard') }}">Dashboard</a><span class="mx-2">/</span><a href="{{ route('timetable-slots.index') }}">Timetable</a><span class="mx-2">/</span><span>Add</span></nav>
<div class="page-header-landing"><h1 class="page-title-landing">Add timetable slot</h1></div>
<div class="card card-landing">
<div class="card-body">
<form action="{{ route('timetable-slots.store') }}" method="POST">
@csrf
<div class="row g-3">
<div class="col-md-3"><label class="form-label">Semester</label><select class="form-select" name="semester_id" id="slotSemester" required><option value="">Select</option>@foreach($semesters as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Level</label><select class="form-select" id="slotLevel"><option value="">All</option><option value="4">4</option><option value="5">5</option><option value="6">6</option></select></div>
<div class="col-md-2"><label class="form-label">Programme</label><select class="form-select" id="slotProgramme"><option value="">All</option>@foreach($programmes as $p)<option value="{{ $p->id }}">{{ $p->code }}</option>@endforeach</select></div>
<div class="col-md-5">
    <label class="form-label">Start time</label>
    <div class="row g-2">
        <div class="col-6"><input type="time" class="form-control" name="start_time" required></div>
        <div class="col-6"><input type="time" class="form-control" name="end_time" required placeholder="End"></div>
    </div>
</div>

<div class="col-md-6">
    <label class="form-label d-flex justify-content-between align-items-center">
        <span>Course(s)</span>
        <span class="form-check form-check-inline mb-0 small">
            <input class="form-check-input" type="checkbox" id="slotCourseSelectAll">
            <label class="form-check-label" for="slotCourseSelectAll">Select all</label>
        </span>
    </label>
    <div id="slotCourseWrap" class="border rounded p-2" style="max-height:220px; overflow-y:auto;">
        <p id="slotCourseEmpty" class="small text-muted mb-0">Select a semester first.</p>
        <div id="slotCourseList" class="row g-1"></div>
    </div>
</div>

<div class="col-md-6">
    <label class="form-label d-flex justify-content-between align-items-center">
        <span>Day(s)</span>
        <span class="form-check form-check-inline mb-0 small">
            <input class="form-check-input" type="checkbox" id="slotDaySelectAll" checked>
            <label class="form-check-label" for="slotDaySelectAll">Monday – Friday</label>
        </span>
    </label>
    <div class="border rounded p-2">
        @foreach(\App\Models\TimetableSlot::DAYS as $d => $label)
        <div class="form-check form-check-inline slot-day-item{{ in_array($d, \App\Models\TimetableSlot::WEEK_DAYS, true) ? ' slot-day-item--weekday' : '' }}">
            <input class="form-check-input slot-day" type="checkbox" name="days[]" value="{{ $d }}" id="slotDay{{ $d }}" {{ in_array($d, \App\Models\TimetableSlot::WEEK_DAYS, true) ? 'checked' : '' }}>
            <label class="form-check-label" for="slotDay{{ $d }}">{{ $label }}</label>
        </div>
        @endforeach
    </div>
</div>

<div class="col-md-4"><label class="form-label">Room</label><input type="text" class="form-control" name="room"></div>
<div class="col-12"><button type="submit" class="btn btn-primary">Save</button> <a href="{{ route('timetable-slots.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
</div>
</form>
</div>
</div>

<div class="card card-landing mt-3">
    <div class="card-header-landing"><i class="bi bi-magic me-2"></i>Or generate a whole week automatically</div>
    <div class="card-body">
        <p class="small text-muted mb-0">Pick a semester and its modules, then randomly fill the standard 3-session week in one go, from the <a href="{{ route('timetable-slots.index') }}">Timetable</a> page.</p>
    </div>
</div>

@push('styles')
<style>
.slot-day-item--weekday.slot-day-faint { opacity: .45; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var semesterSelect = document.getElementById('slotSemester');
    var levelSelect = document.getElementById('slotLevel');
    var programmeSelect = document.getElementById('slotProgramme');
    var courseList = document.getElementById('slotCourseList');
    var courseEmpty = document.getElementById('slotCourseEmpty');
    var courseSelectAll = document.getElementById('slotCourseSelectAll');
    var daySelectAll = document.getElementById('slotDaySelectAll');
    if (!semesterSelect || !courseList) return;

    var badgeClass = {
        CMT: 'bg-primary-subtle text-primary-emphasis',
        MLT: 'bg-success-subtle text-success-emphasis',
        DDR: 'bg-warning-subtle text-warning-emphasis'
    };

    function loadCourses() {
        var semesterId = semesterSelect.value;
        courseList.innerHTML = '';
        courseSelectAll.checked = false;
        if (!semesterId) {
            courseEmpty.textContent = 'Select a semester first.';
            courseEmpty.classList.remove('d-none');
            return;
        }
        courseEmpty.textContent = 'Loading…';
        courseEmpty.classList.remove('d-none');
        var url = '{{ route('timetable-slots.courses-by-semester') }}?semester_id=' + encodeURIComponent(semesterId);
        if (levelSelect.value) url += '&nta_level=' + encodeURIComponent(levelSelect.value);
        if (programmeSelect.value) url += '&programme_id=' + encodeURIComponent(programmeSelect.value);
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (courses) {
                if (!courses.length) {
                    courseEmpty.textContent = 'No modules in this semester.';
                    return;
                }
                courseEmpty.classList.add('d-none');
                courseList.innerHTML = courses.map(function (c) {
                    var badge = c.programme_code
                        ? '<span class="badge ' + (badgeClass[c.programme_code] || 'bg-secondary-subtle text-secondary-emphasis') + ' me-1">' + c.programme_code + '</span>'
                        : '';
                    return '<div class="col-md-6"><div class="form-check">' +
                        '<input class="form-check-input slot-course" type="checkbox" name="course_ids[]" value="' + c.id + '" id="slotCourse' + c.id + '">' +
                        '<label class="form-check-label small" for="slotCourse' + c.id + '">' + badge + c.code + ' — ' + c.name + '</label>' +
                        '</div></div>';
                }).join('');
            });
    }

    function applyDayFaint() {
        document.querySelectorAll('.slot-day-item--weekday').forEach(function (item) {
            item.classList.toggle('slot-day-faint', daySelectAll.checked);
        });
    }

    semesterSelect.addEventListener('change', loadCourses);
    levelSelect.addEventListener('change', loadCourses);
    programmeSelect.addEventListener('change', loadCourses);
    courseSelectAll.addEventListener('change', function () {
        courseList.querySelectorAll('.slot-course').forEach(function (cb) { cb.checked = courseSelectAll.checked; });
    });
    daySelectAll.addEventListener('change', function () {
        document.querySelectorAll('.slot-day').forEach(function (cb) {
            if ([1, 2, 3, 4, 5].indexOf(parseInt(cb.value, 10)) !== -1) cb.checked = daySelectAll.checked;
        });
        applyDayFaint();
    });

    applyDayFaint();
})();
</script>
@endpush
@endsection
