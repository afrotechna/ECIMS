@extends('layouts.app')
@section('title', 'Transcript')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('courses.index') }}">Modules</a>
    <span class="mx-2">/</span>
    <a href="{{ route('results.index') }}">Results</a>
    <span class="mx-2">/</span>
    <a href="{{ route('results.transcript') }}">Transcript</a>
    <span class="mx-2">/</span>
    <span>{{ $student->full_name }}</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-file-text me-2 opacity-90"></i>Academic Transcript</h1>
        <p class="page-subtitle-landing mb-0">{{ $student->full_name }} · {{ $student->reg_no }} · {{ $student->programme->name ?? '' }} ({{ $student->programme->code ?? '' }}) · Intake {{ $student->intake_year }}</p>
    </div>
    <div class="d-flex gap-2">
    <a href="{{ route('results.transcript.print', $student) }}" target="_blank" class="btn btn-outline-light btn-sm"><i class="bi bi-printer me-1"></i>Print</a>
    <a href="{{ route('results.transcript') }}" class="btn btn-outline-light btn-sm">Select another student</a>
</div>
</div>

<div class="card card-landing mb-4">
    <div class="card-header-landing"><i class="bi bi-person-badge me-2"></i>Student info</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4"><span class="text-muted small d-block">Name</span><strong>{{ $student->full_name }}</strong></div>
            <div class="col-md-2"><span class="text-muted small d-block">Reg No</span><strong>{{ $student->reg_no }}</strong></div>
            <div class="col-md-2"><span class="text-muted small d-block">NACTVET</span><code class="small">{{ $student->nactvet_reg_no }}</code></div>
            <div class="col-md-4"><span class="text-muted small d-block">Programme</span>{{ $student->programme->name ?? '—' }} ({{ $student->programme->code ?? '—' }}) · Intake {{ $student->intake_year }}</div>
        </div>
    </div>
</div>

@php $summaries = $summaries ?? collect(); @endphp
@forelse($results as $semesterId => $semesterResults)
@php $sem = $semesterResults->first()->semester; $sum = $summaries->get($semesterId); @endphp
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-calendar3 me-2"></i>{{ $sem->label }}</div>
    @if($sum && ($sum->gpa !== null || $sum->academic_remarks))
    <div class="px-3 pt-3 pb-0 small border-bottom bg-light">
        <span class="text-muted">Semester GPA:</span> <strong>{{ $sum->gpa !== null ? number_format((float) $sum->gpa, 4) : '—' }}</strong>
        <span class="mx-2 text-muted">|</span>
        <span class="text-muted">Remarks:</span> <strong>{{ $sum->academic_remarks ?: '—' }}</strong>
    </div>
    @endif
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm mb-0 text-nowrap">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Module</th>
                    <th class="text-end" title="Written I">WRI</th>
                    <th class="text-end" title="Written II">WRII</th>
                    <th class="text-end">AS1</th>
                    <th class="text-end">AS2</th>
                    <th class="text-end">Skills</th>
                    <th class="text-end">CA</th>
                    <th>CA remarks</th>
                    <th class="text-end">Exam</th>
                    <th class="text-end">Total</th>
                    <th>Grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($semesterResults as $r)
                @php $c = $r->course; @endphp
                <tr>
                    <td>{{ $c->code }}</td>
                    <td>{{ $c->name }}</td>
                    <td class="text-end">{{ $r->ca_test1 !== null ? number_format($r->ca_test1, 1) : '—' }}</td>
                    <td class="text-end">{{ $r->ca_test2 !== null ? number_format($r->ca_test2, 1) : '—' }}</td>
                    <td class="text-end">{{ $r->ca_assignment1 !== null ? number_format($r->ca_assignment1, 1) : '—' }}</td>
                    <td class="text-end">{{ $r->ca_assignment2 !== null ? number_format($r->ca_assignment2, 1) : '—' }}</td>
                    <td class="text-end small text-muted" title="{{ $c->has_practical ? $c->practicalColumnLabel() : '' }}">
                        @if($c->has_practical)
                            {{ $r->ca_practical !== null ? number_format($r->ca_practical, 1) : '—' }}
                            <span class="d-block" style="font-size:.7rem">{{ $c->practicalColumnLabel() }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-end fw-medium">{{ $r->ca_mark !== null ? number_format($r->ca_mark, 1) : '—' }}</td>
                    <td class="small">@include('results.partials.ca-remark-badge', ['result' => $r])</td>
                    <td class="text-end">{{ $r->exam_mark !== null ? number_format($r->exam_mark, 1) : '—' }}</td>
                    <td class="text-end">{{ $r->total_mark !== null ? number_format($r->total_mark, 1) : '—' }}</td>
                    <td><span class="badge bg-{{ $r->grade === 'F' ? 'danger' : 'secondary' }}">{{ $r->grade ?? '-' }}</span></td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        <p class="small text-muted px-3 py-2 mb-0 border-top">Totals and grades follow the official NACTVET CSV imports. WRI / WRII = written assessments; AS1 / AS2 = assignments; Skills = Practical, OSPE, or OSCE depending on the module.</p>
    </div>
</div>
@empty
<div class="card card-landing">
    <div class="card-body">
        <p class="text-muted mb-0">No results recorded yet.</p>
    </div>
</div>
@endforelse
@endsection
