@extends('layouts.print')
@section('title', 'Clinical rotation posting list')
@push('styles')
<style>
    .roster-group-table { font-size: 0.85rem; margin-top: 0.35rem; }
    .roster-group-table .roster-banner th {
        background: #1e293b;
        color: #fff;
        font-size: 0.9rem;
        font-weight: 700;
        padding: 0.45rem 0.5rem;
        border: 1px solid #111;
        vertical-align: middle;
    }
    .roster-group-table .roster-banner-dept { text-align: left; }
    .roster-group-table .roster-banner-hospital { text-align: right; width: 38%; }
    .roster-group-table .roster-columns th {
        background: #eee;
        font-size: 0.82rem;
        padding: 0.3rem 0.45rem;
    }
    .roster-group-table .roster-reg {
        font-size: 0.88rem;
        font-family: ui-monospace, monospace;
    }
    .roster-group-table .text-empty { text-align: center; color: #555; font-style: italic; }
</style>
@endpush
@section('content')
<div class="no-print">
    <button type="button" onclick="window.print()" style="padding:0.45rem 1rem;cursor:pointer;font-size:1rem;">Print</button>
    <a href="{{ route('clinical-rotations.show', $clinical_rotation_round) }}" style="margin-left:0.5rem;">Back to round</a>
</div>
<h1>Clinical rotation — student groups (notice)</h1>
<div class="meta">
    <strong>{{ config('app.name') }}</strong><br>
    {{ $clinical_rotation_round->semester?->label }} · {{ $clinical_rotation_round->programme?->name }} ({{ $clinical_rotation_round->programme?->code }}) · <strong>NTA Level {{ $clinical_rotation_round->nta_level }}</strong>
    @if($clinical_rotation_round->rotation_week_monday && $clinical_rotation_round->rotation_week_friday)
        <br><strong>Posting week:</strong> {{ $clinical_rotation_round->rotation_week_monday->format('l j F Y') }} to {{ $clinical_rotation_round->rotation_week_friday->format('l j F Y') }}
    @endif
    @if($clinical_rotation_round->title)
        <br><em>{{ $clinical_rotation_round->title }}</em>
    @endif
</div>
<p class="meta" style="margin-top:-0.5rem;font-size:0.85rem;">Each table shows group and department, hospital on the top row, then students for that posting.</p>

@foreach($clinical_rotation_round->groups as $group)
    <div class="group-block">
        @include('clinical-rotations.partials.roster-group-table', [
            'slot' => $group->slot_number,
            'department' => $departmentLabels[$group->department_code] ?? $group->department_code,
            'hospital' => \App\Support\ClinicalRotationCatalog::hospitalLabel($group->hospital_code),
            'students' => $group->students,
        ])
    </div>
@endforeach
@endsection
