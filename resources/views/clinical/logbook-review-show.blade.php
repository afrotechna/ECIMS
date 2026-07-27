@extends('layouts.app')
@section('title', 'Review logbook entry')
@section('content')
@php $student = $entry->student; @endphp
<nav class="student-breadcrumb">
    <a href="{{ route('clinical-logbook.index') }}">Logbook review</a>
    <span class="mx-2">/</span>
    <span>Review</span>
</nav>

@if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0">{{ $student->full_name }}</h1>
        <p class="page-subtitle-landing mb-0">{{ $entry->procedure?->name }} · {{ $entry->performed_on->format('j F Y') }}</p>
    </div>
    <span class="badge fs-6 {{ \App\Models\ClinicalLogbookEntry::statusBadgeClass($entry->status) }}">{{ \App\Models\ClinicalLogbookEntry::statusLabel($entry->status) }}</span>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card card-landing mb-3">
            <div class="card-header-landing">Logbook entry</div>
            <div class="card-body">
                <p><strong>Procedure:</strong> {{ $entry->procedure?->code }} — {{ $entry->procedure?->name }}</p>
                <p><strong>Department:</strong> {{ \App\Support\ClinicalRotationCatalog::departmentLabel($entry->department_code ?? '') }}</p>
                <p><strong>Case reference:</strong> {{ $entry->case_reference ?: '—' }}</p>
                <h2 class="h6 mt-3">Case summary</h2>
                <p>{{ $entry->case_summary }}</p>
                @if($entry->skills_notes)
                    <h2 class="h6">Skills performed</h2>
                    <p>{{ $entry->skills_notes }}</p>
                @endif
            </div>
        </div>

        @if($entry->status === \App\Models\ClinicalLogbookEntry::STATUS_SUBMITTED)
        <div class="card card-landing">
            <div class="card-header-landing">Clinical Instructor decision</div>
            <div class="card-body">
                <form method="POST" action="{{ route('clinical-logbook.approve', $entry) }}" class="mb-4">
                    @csrf
                    <label class="form-label">Feedback (optional)</label>
                    <textarea name="reviewer_feedback" class="form-control mb-2" rows="2" placeholder="Commendation or brief note"></textarea>
                    <button type="submit" class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Approve &amp; sign competency</button>
                </form>
                <form method="POST" action="{{ route('clinical-logbook.reject', $entry) }}">
                    @csrf
                    <label class="form-label">Return for revision <span class="text-danger">*</span></label>
                    <textarea name="reviewer_feedback" class="form-control mb-2 @error('reviewer_feedback') is-invalid @enderror" rows="2" required placeholder="What must the student correct?"></textarea>
                    @error('reviewer_feedback')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="form-check mb-2">
                        <input type="checkbox" class="form-check-input" name="create_remediation" value="1" id="create_remediation" checked>
                        <label class="form-check-label" for="create_remediation">Also create remediation plan</label>
                    </div>
                    <input type="date" name="remediation_due" class="form-control form-control-sm mb-2" value="{{ now()->addWeek()->format('Y-m-d') }}">
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-arrow-return-left me-1"></i>Return to student</button>
                </form>
            </div>
        </div>
        @elseif($entry->reviewer)
        <div class="card card-landing">
            <div class="card-body small">
                Reviewed by {{ $entry->reviewer->name }} on {{ $entry->reviewed_at?->format('j M Y H:i') }}
                @if($entry->reviewer_feedback)<p class="mt-2 mb-0">{{ $entry->reviewer_feedback }}</p>@endif
            </div>
        </div>
        @endif
    </div>
    <div class="col-lg-4">
        <div class="card card-landing mb-3">
            <div class="card-header-landing">Student placement</div>
            <div class="card-body small">
                @if($placementContext['primary'] ?? null)
                    @php $g = $placementContext['primary']; @endphp
                    <p class="mb-1"><strong>Group:</strong> {{ $g->name }}</p>
                    <p class="mb-1"><strong>Hospital:</strong> {{ \App\Support\ClinicalRotationCatalog::hospitalLabel($g->hospital_code) }}</p>
                    <p class="mb-0"><strong>Department:</strong> {{ \App\Support\ClinicalRotationCatalog::departmentLabel($g->department_code) }}</p>
                @else
                    <p class="text-muted mb-0">Not in a rotation group.</p>
                @endif
                <a href="{{ route('clinical-logbook.student', $entry->student) }}" class="btn btn-sm btn-outline-primary mt-2">Full progress</a>
            </div>
        </div>
        @if(! empty($competency))
        <div class="card card-landing mb-3">
            <div class="card-header-landing">Competency</div>
            <ul class="list-group list-group-flush small">
                @foreach($competency as $item)
                <li class="list-group-item d-flex justify-content-between">
                    <span>{{ $item['procedure']->code }}</span>
                    <span>{{ $item['approved'] }}/{{ $item['required'] }} @if($item['met'])✓@endif</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        <div class="card card-landing">
            <div class="card-body small">
                <p class="mb-1">Logbook: <strong>{{ $placementContext['logbook_counts']['approved'] ?? 0 }}</strong> approved / <strong>{{ $placementContext['logbook_counts']['total'] ?? 0 }}</strong> total</p>
            </div>
        </div>
    </div>
</div>
@endsection
