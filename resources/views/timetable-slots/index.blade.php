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
    <div class="col-auto"><button type="submit" class="btn btn-sm btn-primary">Filter</button></div>
</form>
@php
    $bulkDelete = [
        'bulkModule' => 'timetable',
        'bulkAction' => route('timetable-slots.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteTimetableSlots',
        'bulkTableId' => 'timetableSlotsTable',
        'bulkItemCount' => $slots->count(),
        'bulkHidden' => array_filter(['semester_id' => $semesterId ?? null]),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span>Timetable slots</span>
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
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($slots as $slot)
                <tr>
                    @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $slot->id]))
                    <td>{{ isset(\App\Models\TimetableSlot::DAYS[$slot->day_of_week]) ? \App\Models\TimetableSlot::DAYS[$slot->day_of_week] : $slot->day_of_week }}</td>
                    <td>{{ $slot->start_time }} – {{ $slot->end_time }}</td>
                    <td>{{ $slot->course ? $slot->course->code : '' }} {{ $slot->course ? $slot->course->name : '' }}</td>
                    <td>{{ $slot->room ?? '—' }}</td>
                    <td class="text-end text-nowrap">
                        @canModule('timetable', 'delete')
                        <form method="POST" action="{{ route('timetable-slots.destroy', $slot) }}" class="d-inline" onsubmit="return confirm('Remove this timetable slot?');">
                            @csrf
                            @method('DELETE')
                            @if($semesterId)<input type="hidden" name="semester_id" value="{{ $semesterId }}">@endif
                            <button type="submit" class="btn btn-sm btn-cohas-delete" title="Delete" aria-label="Delete"><i class="bi bi-trash-fill"></i></button>
                        </form>
                        @endcanModule
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted py-5">No timetable slots.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@include('partials.bulk-delete.scripts')
@endsection
