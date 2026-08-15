@extends('layouts.print')
@section('title', strtoupper($programme->code).' Level '.$level.' Timetable')
@push('styles')
<style>
    @page { size: landscape; margin: 10mm; }
    body { font-family: 'Times New Roman', Times, Georgia, serif; margin: 0; padding: 10mm; color: #000; }
    .tt-header { text-align: center; margin-bottom: 10px; }
    .tt-header .line { font-weight: bold; text-transform: uppercase; font-size: 13pt; line-height: 1.35; }
    .tt-header .sub { font-weight: normal; font-size: 10.5pt; margin-top: 2px; text-transform: uppercase; }
    .tt-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9.5pt; }
    .tt-table th, .tt-table td { border: 1px solid #000; padding: 5px 6px; text-align: center; vertical-align: middle; word-wrap: break-word; }
    .tt-table thead th { text-transform: uppercase; font-weight: bold; }
    .tt-table th.time-col, .tt-table td.time-col { white-space: nowrap; width: 90px; font-weight: bold; }
    .tt-table td.break-row { font-weight: bold; text-transform: uppercase; }
    .tt-table .course-name { font-weight: bold; }
    .tt-table .lecturer { font-size: 8.5pt; font-style: italic; }
</style>
@endpush
@section('content')
<div class="no-print" style="margin-bottom:1rem;">
    <button type="button" onclick="window.print()" style="padding:0.45rem 1rem;cursor:pointer;">Print / Save as PDF</button>
    <a href="{{ route('timetable-slots.index', ['semester_id' => $semester->id, 'programme_id' => $programme->id]) }}" style="margin-left:0.5rem;">Back to Timetable</a>
</div>

<div class="tt-header">
    <div class="line">Ministry of Health</div>
    <div class="line">Musoma Clinical Officer Training Centre</div>
    <div class="line">Department of {{ $programme->name }}</div>
    <div class="line">Academic Year: {{ $semester->academicYearRange() }}</div>
    <div class="line">{{ $semester->periodName() }}</div>
    <div class="line">NTA Level {{ $level }}</div>
    @if($semester->start_date && $semester->end_date)
    <div class="sub">From {{ $semester->start_date->format('jS F Y') }} – {{ $semester->end_date->format('jS F Y') }}</div>
    @endif
</div>

<table class="tt-table">
    <thead>
        <tr>
            <th class="time-col">Time</th>
            @foreach($dayLabels as $label)
            <th>{{ $label }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach(\App\Models\TimetableSlot::DAILY_SESSIONS as $sessionIndex => $session)
        <tr>
            <td class="time-col">{{ $session['label'] }}</td>
            @foreach($dayLabels as $day => $label)
            @php $cellSlot = $grid[$day][$session['start']] ?? null; @endphp
            <td>
                @if($cellSlot)
                    <div class="course-name">{{ $cellSlot->course->name ?? '—' }}</div>
                    <div class="lecturer">Tutor: {{ $cellSlot->lecturer ?: '________________' }}</div>
                @else
                    —
                @endif
            </td>
            @endforeach
        </tr>
        @if($break = \App\Models\TimetableSlot::BREAKS[$sessionIndex] ?? null)
        <tr>
            <td class="time-col">{{ $break['start'] }} – {{ $break['end'] }}</td>
            <td class="break-row" colspan="{{ count($dayLabels) }}">{{ $break['label'] }}</td>
        </tr>
        @endif
        @endforeach
    </tbody>
</table>
@endsection
