@extends('layouts.app')
@section('title', 'Clinical progression decisions')
@section('content')
<div class="page-header-landing d-flex justify-content-between flex-wrap gap-2">
    <div>
        <h1 class="page-title-landing mb-0">Clinical progression decisions</h1>
        <p class="page-subtitle-landing mb-0">Final competency / progression for clinical semester</p>
    </div>
    <a href="{{ route('clinical.coordinator') }}" class="btn btn-outline-light btn-sm">Coordinator dashboard</a>
</div>

<form method="GET" class="mb-3">
    <select name="semester_id" class="form-select form-select-sm w-auto" onchange="this.form.submit()">
        @foreach($semesters as $s)
            <option value="{{ $s->id }}" {{ (int)$semesterId === $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
        @endforeach
    </select>
</form>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="card card-landing">
    <div class="table-responsive">
    <table class="table table-sm mb-0">
        <thead class="table-light"><tr><th>Student</th><th>Decision</th><th>By</th><th>Date</th><th>Notes</th></tr></thead>
        <tbody>
            @forelse($decisions as $d)
            <tr>
                <td><a href="{{ route('clinical-logbook.student', $d->student) }}">{{ $d->student->full_name }}</a></td>
                <td>{{ \App\Models\ClinicalProgressionDecision::decisionLabel($d->decision) }}</td>
                <td class="small">{{ $d->decider?->name ?? '—' }}</td>
                <td class="small">{{ $d->decided_at->format('j M Y') }}</td>
                <td class="small">{{ Str::limit($d->notes, 60) }}</td>
            </tr>
            @empty
            <tr><td colspan="5" class="text-center text-muted py-4">No decisions recorded. Record from a student's clinical progress page.</td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($decisions->hasPages())<div class="card-body">{{ $decisions->links() }}</div>@endif
</div>
@endsection
