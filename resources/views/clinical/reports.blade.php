@extends('layouts.app')
@section('title', 'Clinical reports')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('clinical.coordinator') }}">Coordinator dashboard</a>
    <span class="mx-2">/</span>
    <span>Reports</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing mb-0">Clinical reports</h1>
</div>

<form method="GET" class="mb-3">
    <select name="round_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        @foreach($rounds as $r)
            <option value="{{ $r->id }}" {{ (int)$roundId === $r->id ? 'selected' : '' }}>{{ $r->semester?->label }} — {{ $r->programme?->code }}</option>
        @endforeach
    </select>
</form>

<div class="card card-landing mb-4">
    <div class="card-header-landing">By hospital &amp; department</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Department</th><th>Hospital</th><th>Students</th><th>With alerts</th></tr></thead>
            <tbody>
                @forelse($sites as $s)
                <tr>
                    <td>{{ $s['department'] }}</td>
                    <td>{{ $s['hospital'] }}</td>
                    <td>{{ $s['students'] }}</td>
                    <td>{{ $s['alerts'] }}</td>
                </tr>
                @empty
                <tr><td colspan="4" class="text-muted text-center py-3">No data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card card-landing">
    <div class="card-header-landing">Attendance vs logbook mismatches</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Student</th><th>Group</th><th>Issue</th></tr></thead>
            <tbody>
                @forelse($mismatches as $m)
                <tr>
                    <td>{{ $m['student']->full_name }}</td>
                    <td>{{ $m['group']->name }}</td>
                    <td class="text-danger">{{ $m['issue'] }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-muted text-center py-3">No mismatches for this round.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
