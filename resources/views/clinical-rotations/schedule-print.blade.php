@extends('layouts.print')
@section('title', 'NTA clinical rotation schedule')
@push('styles')
<style>
    h1 { font-size: 1.15rem; }
    .schedule-sub { font-size: 0.9rem; margin-bottom: 0.75rem; }
    .schedule-table { font-size: 0.82rem; }
    .schedule-table th, .schedule-table td { padding: 0.3rem 0.35rem; }
    .schedule-table th { text-align: center; }
    .schedule-table td { text-align: center; font-weight: 600; }
    .schedule-table td:first-child, .schedule-table th:first-child { text-align: left; font-weight: normal; }
    .schedule-table td:nth-child(2), .schedule-table th:nth-child(2) { text-align: left; font-weight: normal; }
    .summary-table td:first-child { font-weight: 600; width: 6rem; }
    .legend em { font-size: 0.78rem; }
    .no-print { margin-bottom: 1rem; }
</style>
@endpush
@section('content')
<div class="no-print">
    <button type="button" onclick="window.print()" style="padding:0.45rem 1rem;cursor:pointer;">Print</button>
    <a href="{{ route('clinical-rotations.show', $clinical_rotation_round) }}" style="margin-left:0.5rem;">Back to round</a>
</div>

@include('clinical-rotations.partials.schedule-doc-body', [
    'payload' => $payload,
    'programmeName' => $clinical_rotation_round->programme?->name.' ('.$clinical_rotation_round->programme?->code.')',
    'semesterName' => $clinical_rotation_round->semester?->label,
    'usedDefaultStart' => $usedDefaultStart ?? false,
])
@endsection
