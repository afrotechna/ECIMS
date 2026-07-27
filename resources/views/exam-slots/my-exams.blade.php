@extends('layouts.app')
@section('title', 'My exams')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>My exams</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-calendar-event me-2 opacity-90"></i>My exams</h1>
    <p class="page-subtitle-landing mb-0">Exam schedule for your registered semesters.</p>
</div>
@if($semesters->isNotEmpty())
<div class="card card-landing mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-0">Semester</label>
                <select name="semester_id" class="form-select form-select-sm">
                    @foreach($semesters as $s)
                        <option value="{{ $s->id }}" {{ $semesterId == $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Show</button></div>
        </form>
    </div>
</div>
@endif
<div class="card card-landing">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Time</th><th>Course</th><th>Format</th><th>Assessment</th><th>Room</th></tr></thead>
            <tbody>
                @forelse($slots as $slot)
                <tr>
                    <td>{{ $slot->exam_date->format('d/m/Y') }}</td>
                    <td>@if($slot->start_time){{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }}@if($slot->end_time) – {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}@endif @else — @endif</td>
                    <td>{{ $slot->course->code ?? '' }} — {{ $slot->course->name ?? '' }}</td>
                    <td class="small text-uppercase">{{ $slot->formatDisplayLabel() }}</td>
                    <td class="small">{{ \App\Models\ExamSlot::assessmentOptions()[$slot->assessment_type] ?? $slot->assessment_type }}</td>
                    <td>{{ $slot->room ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No exams scheduled for the selected semester.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
