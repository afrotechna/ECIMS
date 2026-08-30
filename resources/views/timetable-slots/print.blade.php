@extends('layouts.print')
@section('title', strtoupper($programme->code).' Level '.$level.' Timetable')
@push('styles')
<style>
    @page { size: landscape; margin: 10mm; }
    body { font-family: 'Times New Roman', Times, Georgia, serif; margin: 0; padding: 10mm; color: #000; }
    .tt-header-table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    .tt-header-table td { border: none; padding: 0; vertical-align: middle; }
    .tt-header-logo { width: 130px; text-align: center; padding: 0 18px !important; }
    .tt-header-logo img { max-width: 95px; max-height: 95px; }
    .tt-header-text { text-align: center; }
    .tt-header-text .line { font-weight: bold; text-transform: uppercase; font-size: 13pt; line-height: 1.35; }
    .tt-header-text .sub { font-weight: normal; font-size: 10.5pt; margin-top: 2px; text-transform: uppercase; }
    .tt-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 9.5pt; }
    .tt-table th, .tt-table td { border: 1px solid #000; padding: 5px 6px; text-align: center; vertical-align: middle; word-wrap: break-word; }
    .tt-table thead th { text-transform: uppercase; font-weight: bold; }
    .tt-table th.time-col, .tt-table td.time-col { white-space: nowrap; width: 90px; font-weight: bold; }
    .tt-table td.break-row { font-weight: bold; text-transform: uppercase; }
    .tt-table .course-code { font-weight: bold; }
    .tt-table .course-name { font-weight: normal; }
    .tt-table .lecturer { font-size: 8.5pt; font-weight: bold; }
    .tt-signoff-table { width: 100%; border-collapse: collapse; margin-top: 26px; }
    .tt-signoff-table td { border: none; padding: 0; width: 50%; vertical-align: top; font-size: 10pt; }
    .tt-signoff-table td.right { text-align: right; }
    .tt-signoff-line { border-top: 1px solid #000; width: 220px; margin-top: 22px; }
    .tt-signoff-table td.right .tt-signoff-line { margin-left: auto; }
    .tt-signoff-role { font-size: 10pt; font-weight: bold; text-transform: uppercase; margin-top: 3px; }
    .tt-footer { margin-top: 18px; padding-top: 6px; border-top: 1px solid #000; text-align: center; font-size: 8pt; }
</style>
@endpush
@section('content')
<div class="no-print" style="margin-bottom:1rem;">
    <button type="button" onclick="window.print()" style="padding:0.45rem 1rem;cursor:pointer;">Print / Save as PDF</button>
    <a href="{{ $backUrl ?? route('timetable-slots.index', ['semester_id' => $semester->id, 'programme_id' => $programme->id]) }}" style="margin-left:0.5rem;">Back to Timetable</a>
</div>

<table class="tt-header-table">
    <tr>
        <td class="tt-header-logo">
            @if(file_exists(public_path('images/national-emblem.png')))
            <img src="{{ asset('images/national-emblem.png') }}" alt="National Emblem">
            @endif
        </td>
        <td class="tt-header-text">
            <div class="line">Ministry of Health</div>
            <div class="line">Musoma Clinical Officer Training Centre</div>
            <div class="line">Department of {{ $programme->name }}</div>
            <div class="line">Academic Year: {{ $semester->academicYearRange() }}</div>
            <div class="line">{{ $semester->periodName() }}</div>
            <div class="line">NTA Level {{ $level }}</div>
            @if($semester->start_date && $semester->end_date)
            <div class="sub">From {{ $semester->start_date->format('jS F Y') }} – {{ $semester->end_date->format('jS F Y') }}</div>
            @endif
        </td>
        <td class="tt-header-logo">
            @if(file_exists(public_path('images/logo.png')))
            <img src="{{ asset('images/logo.png') }}" alt="College Logo">
            @endif
        </td>
    </tr>
</table>

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
                    @if($cellSlot->course?->code)
                    <div class="course-code">{{ $cellSlot->course->code }}</div>
                    @endif
                    <div class="course-name">{{ $cellSlot->course->name ?? '—' }}</div>
                    <div class="lecturer">{{ $cellSlot->lecturer ? 'Tutor: '.$cellSlot->lecturer : ' ' }}</div>
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

<table class="tt-signoff-table">
    <tr>
        <td>
            <div class="tt-signoff-line"></div>
            <div class="tt-signoff-role">VICE PRINCIPAL</div>
            <div class="tt-signoff-role">ACADEMIC, RESEARCH AND CONSULTANCY</div>
        </td>
        <td class="right">
            <div class="tt-signoff-line"></div>
            <div class="tt-signoff-role">HEAD OF DEPARTMENT</div>
            <div class="tt-signoff-role">{{ strtoupper($programme->name) }}</div>
        </td>
    </tr>
</table>

<div class="tt-footer">
    {{ config('college.institution_name', config('college.school_name')) }}<br>
    info@musomacohas.ac.tz &nbsp;|&nbsp; +255 28 262 0000 &nbsp;|&nbsp; www.musomacohas.ac.tz
</div>
@endsection
