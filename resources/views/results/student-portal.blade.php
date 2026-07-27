@extends('layouts.app')
@section('title', 'My Modules Result')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>My Modules Result</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-award me-2 opacity-90"></i>My Modules Result</h1>
        <p class="page-subtitle-landing mb-0">Choose year of study and semester to view final module marks as published from college records.</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('results.transcript.show', $student) }}" class="btn btn-outline-light btn-sm"><i class="bi bi-file-text me-1"></i>Full transcript</a>
    </div>
</div>

<div class="card card-landing mb-4">
    <div class="card-body">
        <h2 class="h6 mb-2">Request official transcript</h2>
        <p class="small text-muted mb-3">Submit a request to the academic office. You will be notified when it is ready.</p>
        <form action="{{ route('transcript-requests.store') }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-md-8">
                <label class="form-label small mb-0">Purpose (optional)</label>
                <input type="text" name="purpose" class="form-control form-control-sm" placeholder="e.g. Employment, further studies">
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-primary btn-sm w-100">Submit request</button>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing mb-4">
    <div class="card-header-landing"><i class="bi bi-person-badge me-2"></i>{{ $student->full_name }}</div>
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-4"><span class="text-muted d-block">Registration</span><strong>{{ $student->reg_no }}</strong></div>
            <div class="col-md-4"><span class="text-muted d-block">NACTVET reg.</span><code>{{ $student->nactvet_reg_no ?: '—' }}</code></div>
            <div class="col-md-4"><span class="text-muted d-block">Programme</span>{{ $student->programme->name ?? '—' }}</div>
        </div>
    </div>
</div>

<form method="GET" action="{{ route('results.portal') }}" class="card card-landing mb-4">
    <div class="card-body">
        <div class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small mb-0">Year of study</label>
                <select name="academic_year" class="form-select">
                    <option value="">Select academic year…</option>
                    @foreach($distinctYears as $y)
                    <option value="{{ $y }}" {{ (int) $selectedYear === (int) $y ? 'selected' : '' }}>{{ $yearLabels[$y] ?? $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small mb-0">Semester</label>
                <select name="semester_number" class="form-select" {{ !$selectedYear ? 'disabled' : '' }}>
                    <option value="">Semester…</option>
                    @foreach($numbersForYear as $num)
                    <option value="{{ $num }}" {{ (int) $selectedSemesterNumber === (int) $num ? 'selected' : '' }}>Semester {{ $num }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100" {{ !$selectedYear ? 'disabled' : '' }}>Show results</button>
            </div>
        </div>
        @if(!$semestersWithResults->count())
        <p class="text-muted small mt-3 mb-0">No result rows are published yet for your record.</p>
        @endif
    </div>
</form>

@if($semester && $results->count())
    <ul class="nav nav-pills mb-3 gap-2">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('my.assessments') }}">My Assessments (CA)</a>
        </li>
        <li class="nav-item">
            <a class="nav-link active" href="{{ route('my.module-results', request()->except('tab')) }}">My Modules Result</a>
        </li>
    </ul>

    <div class="card card-landing mb-3">
        <div class="card-header-landing"><i class="bi bi-calendar3 me-2"></i>{{ $semester->label }}</div>
        <div class="card-body p-0">
            <div class="px-3 pt-3 pb-2 border-bottom bg-light">
                @if($summary)
                <div class="row g-2 small">
                    <div class="col-auto"><span class="text-muted">GPA:</span> <strong>{{ $summary->gpa !== null ? number_format((float) $summary->gpa, 4) : '—' }}</strong></div>
                    <div class="col-auto"><span class="text-muted">Status:</span> <strong>{{ $summary->academic_remarks ?: '—' }}</strong></div>
                </div>
                @else
                <p class="small text-muted mb-0">Semester GPA and remarks appear after final results are imported (NACTVET sheet).</p>
                @endif
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Module</th>
                            <th class="text-end">SE</th>
                            <th class="text-end">Final score</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($results as $r)
                        <tr>
                            <td><span class="fw-medium">{{ $r->course->code }}</span> — {{ $r->course->name }}</td>
                            <td class="text-end">{{ $r->exam_mark !== null ? number_format($r->exam_mark, 1) : '—' }}</td>
                            <td class="text-end">{{ $r->total_mark !== null ? number_format($r->total_mark, 1) : '—' }}</td>
                            <td><span class="badge bg-{{ ($r->grade ?? '') === 'F' ? 'danger' : 'secondary' }}">{{ $r->grade ?? '—' }}</span></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@elseif($semester && $results->isEmpty())
<div class="alert alert-info">No modules found for this semester yet.</div>
@endif
@endsection
