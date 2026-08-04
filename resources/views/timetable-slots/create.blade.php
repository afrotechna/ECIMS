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
<div class="col-md-4"><label class="form-label">Semester</label><select class="form-select" name="semester_id" id="slotSemester" required><option value="">Select</option>@foreach($semesters as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Level</label><select class="form-select" id="slotLevel"><option value="">All</option><option value="4">4</option><option value="5">5</option><option value="6">6</option></select></div>
<div class="col-md-6"><label class="form-label">Course</label><select class="form-select" name="course_id" id="slotCourse" required><option value="">Select a semester first</option>@foreach($courses as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Day</label><select class="form-select" name="day_of_week" required>@foreach(\App\Models\TimetableSlot::DAYS as $d => $label)<option value="{{ $d }}">{{ $label }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" required></div>
<div class="col-md-2"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" required></div>
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

@push('scripts')
<script>
(function () {
    var semesterSelect = document.getElementById('slotSemester');
    var levelSelect = document.getElementById('slotLevel');
    var courseSelect = document.getElementById('slotCourse');
    if (!semesterSelect || !courseSelect) return;

    function loadCourses() {
        var semesterId = semesterSelect.value;
        courseSelect.innerHTML = '<option value="">Loading…</option>';
        if (!semesterId) {
            courseSelect.innerHTML = '<option value="">Select a semester first</option>';
            return;
        }
        var url = '{{ route('timetable-slots.courses-by-semester') }}?semester_id=' + encodeURIComponent(semesterId);
        if (levelSelect.value) url += '&nta_level=' + encodeURIComponent(levelSelect.value);
        fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (courses) {
                if (!courses.length) {
                    courseSelect.innerHTML = '<option value="">No modules in this semester</option>';
                    return;
                }
                courseSelect.innerHTML = courses.map(function (c) {
                    return '<option value="' + c.id + '">' + c.code + ' — ' + c.name + '</option>';
                }).join('');
            });
    }

    semesterSelect.addEventListener('change', loadCourses);
    levelSelect.addEventListener('change', loadCourses);
})();
</script>
@endpush
@endsection
