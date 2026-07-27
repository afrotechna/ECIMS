@extends('layouts.app')
@section('title', 'Clinical progress — '.$student->full_name)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('clinical-logbook.index') }}">Logbook review</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a>
    <span class="mx-2">/</span>
    <span>Clinical progress</span>
</nav>

@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0">Clinical progress</h1>
        <p class="page-subtitle-landing mb-0">{{ $student->reg_no }} · {{ $student->programme?->name }} · NTA {{ $student->nta_level }}</p>
    </div>
    <a href="{{ route('clinical.print.student', $student) }}" class="btn btn-outline-light btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Print logbook</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card card-landing mb-3">
            <div class="card-header-landing">Competency checklist</div>
            <div class="card-body p-0">
                @if(empty($overview['competency']))
                    <p class="p-3 text-muted mb-0 small">Set minimum counts on procedures in the catalogue to track competency.</p>
                @else
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Procedure</th><th>Required</th><th>Approved</th><th></th></tr></thead>
                    <tbody>
                        @foreach($overview['competency'] as $item)
                        <tr>
                            <td>{{ $item['procedure']->code }} — {{ $item['procedure']->name }}</td>
                            <td>{{ $item['required'] }}</td>
                            <td>{{ $item['approved'] }}</td>
                            <td>@if($item['met'])<span class="badge bg-success">Met</span>@else<span class="badge bg-warning text-dark">Incomplete</span>@endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif
            </div>
        </div>

        <div class="card card-landing mb-3">
            <div class="card-header-landing">Clinical results (rotation modules)</div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Module</th><th>Grade</th><th>Outcome</th></tr></thead>
                    <tbody>
                        @forelse($overview['clinical_results'] as $r)
                        <tr>
                            <td>{{ $r->course?->code }} {{ $r->course?->name }}</td>
                            <td>{{ $r->grade ?? '—' }}</td>
                            <td>{{ $r->finalOutcome() }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="3" class="text-muted text-center py-3">No clinical module results.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card card-landing">
            <div class="card-header-landing">Logbook entries</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light"><tr><th>Date</th><th>Procedure</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach($entries as $e)
                        <tr>
                            <td>{{ $e->performed_on->format('j M Y') }}</td>
                            <td>{{ $e->procedure?->name }}</td>
                            <td><span class="badge {{ \App\Models\ClinicalLogbookEntry::statusBadgeClass($e->status) }}">{{ \App\Models\ClinicalLogbookEntry::statusLabel($e->status) }}</span></td>
                            <td><a href="{{ route('clinical-logbook.show', $e) }}" class="btn btn-sm btn-outline-primary">Review</a></td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-landing mb-3">
            <div class="card-header-landing">Progression decision</div>
            <div class="card-body">
                @if($overview['progression'] ?? null)
                    <p class="small mb-2"><strong>{{ \App\Models\ClinicalProgressionDecision::decisionLabel($overview['progression']->decision) }}</strong></p>
                    <p class="small text-muted mb-0">{{ $overview['progression']->notes }}</p>
                @endif
                <form method="POST" action="{{ route('clinical.progression.store', $student) }}" class="mt-3">
                    @csrf
                    <div class="mb-2">
                        <select name="semester_id" class="form-select form-select-sm" required>
                            @foreach($semesters as $s)
                                <option value="{{ $s->id }}" {{ (int)($overview['semester_id'] ?? 0) === $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-2">
                        <select name="decision" class="form-select form-select-sm" required>
                            @foreach(\App\Models\ClinicalProgressionDecision::decisionOptions() as $val => $label)
                                <option value="{{ $val }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <textarea name="notes" class="form-control form-control-sm mb-2" rows="2" placeholder="Notes"></textarea>
                    <button type="submit" class="btn btn-primary btn-sm w-100">Save decision</button>
                </form>
            </div>
        </div>

        <div class="card card-landing mb-3">
            <div class="card-header-landing">Assign remediation</div>
            <div class="card-body">
                <form method="POST" action="{{ route('clinical.remediation.store', $student) }}">
                    @csrf
                    <input type="hidden" name="semester_id" value="{{ $overview['semester_id'] ?? $semesters->first()?->id }}">
                    <input type="text" name="title" class="form-control form-control-sm mb-2" placeholder="Title" required>
                    <textarea name="description" class="form-control form-control-sm mb-2" rows="2" required placeholder="Tasks for student"></textarea>
                    <input type="date" name="due_date" class="form-control form-control-sm mb-2">
                    <button type="submit" class="btn btn-warning btn-sm w-100">Assign</button>
                </form>
            </div>
        </div>

        @if($remediationPlans->isNotEmpty())
        <div class="card card-landing">
            <div class="card-header-landing">Remediation plans</div>
            <ul class="list-group list-group-flush small">
                @foreach($remediationPlans as $plan)
                <li class="list-group-item d-flex justify-content-between align-items-start">
                    <div>
                        <strong>{{ $plan->title }}</strong>
                        <div class="text-muted">{{ \App\Models\ClinicalRemediationPlan::statusLabel($plan->status) }}</div>
                    </div>
                    @if($plan->status === \App\Models\ClinicalRemediationPlan::STATUS_OPEN)
                    <form method="POST" action="{{ route('clinical.remediation.complete', $plan) }}">@csrf
                        <button class="btn btn-sm btn-success">Done</button>
                    </form>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
