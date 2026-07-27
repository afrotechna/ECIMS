@extends('layouts.print')
@section('title', 'Clinical rotation — full roster (all weeks)')
@push('styles')
<style>
    h1 { font-size: 1.2rem; }
    .week-block { page-break-before: always; margin-top: 1rem; }
    .week-block:first-of-type { page-break-before: auto; }
    .week-head { border-bottom: 2px solid #222; margin-bottom: 0.5rem; padding-bottom: 0.25rem; }
    .week-head h2 { font-size: 1.05rem; margin: 0; }
    .group-block { page-break-inside: avoid; margin-bottom: 0.85rem; }
    .toc-week { font-size: 0.82rem; margin: 0.15rem 0; }
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
    .roster-group-table .roster-abbr { font-weight: 400; font-size: 0.82rem; }
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
@php
    $round = $roster['round'];
@endphp
<div class="no-print">
    <button type="button" onclick="window.print()" style="padding:0.45rem 1rem;cursor:pointer;font-size:1rem;">Print</button>
    <a href="{{ route('clinical-rotations.show', $round) }}" style="margin-left:0.5rem;">Back to round</a>
</div>

<h1>Clinical rotation — complete roster (all weeks)</h1>
<div class="meta">
    <strong>{{ config('app.name') }}</strong><br>
    {{ $round->semester?->label }} · {{ $round->programme?->name }} ({{ $round->programme?->code }}) · <strong>NTA Level {{ $roster['nta_level'] }}</strong><br>
    <strong>{{ $roster['total_weeks'] }} weeks</strong> ({{ $roster['weeks_per_block'] }} week{{ $roster['weeks_per_block'] > 1 ? 's' : '' }} per department posting) · starts {{ $roster['start_monday']->format('l j F Y') }}<br>
    Working days: Monday – Friday.
    @if($round->title)
        <br><em>{{ $round->title }}</em>
    @endif
</div>

@if(!empty($usedDefaultStart))
    <p class="meta" style="background:#fff3cd;padding:0.35rem 0.5rem;font-size:0.85rem;">
        No schedule start Monday was set — dates use <strong>this week’s Monday</strong>. Regenerate from the round page with the correct start date.
    </p>
@endif

<h2 style="font-size:1rem;margin-top:0.75rem;">Week index</h2>
@foreach($roster['weeks'] as $week)
    <div class="toc-week"><strong>{{ $week['week_label'] }}</strong> — {{ $week['date_range'] }}</div>
@endforeach

@foreach($roster['weeks'] as $week)
    <section class="week-block">
        <div class="week-head">
            <h2>{{ $week['week_label'] }} · {{ $week['date_range'] }}</h2>
        </div>
        @foreach($week['groups'] as $group)
            <div class="group-block">
                @include('clinical-rotations.partials.roster-group-table', [
                    'slot' => $group['slot'],
                    'department' => $group['department'],
                    'departmentAbbr' => $group['department_abbr'],
                    'hospital' => $group['hospital'],
                    'students' => $group['students'],
                ])
            </div>
        @endforeach
    </section>
@endforeach

<div class="meta" style="margin-top:1rem;font-size:0.78rem;">
    <strong>Abbreviations:</strong>
    @foreach($roster['legend'] as $abbr => $full)
        {{ $abbr }} = {{ $full }}{{ !$loop->last ? '; ' : '' }}
    @endforeach
</div>
@endsection
