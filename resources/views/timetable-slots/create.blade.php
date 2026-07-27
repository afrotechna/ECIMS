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
<div class="col-md-6"><label class="form-label">Semester</label><select class="form-select" name="semester_id" required>@foreach($semesters as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach</select></div>
<div class="col-md-6"><label class="form-label">Course</label><select class="form-select" name="course_id" required>@foreach($courses as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>@endforeach</select></div>
<div class="col-md-4"><label class="form-label">Day</label><select class="form-select" name="day_of_week" required>@foreach(\App\Models\TimetableSlot::DAYS as $d => $label)<option value="{{ $d }}">{{ $label }}</option>@endforeach</select></div>
<div class="col-md-2"><label class="form-label">Start</label><input type="time" class="form-control" name="start_time" required></div>
<div class="col-md-2"><label class="form-label">End</label><input type="time" class="form-control" name="end_time" required></div>
<div class="col-md-4"><label class="form-label">Room</label><input type="text" class="form-control" name="room"></div>
<div class="col-12"><button type="submit" class="btn btn-primary">Save</button> <a href="{{ route('timetable-slots.index') }}" class="btn btn-outline-secondary">Cancel</a></div>
</div>
</form>
</div>
</div>
@endsection
