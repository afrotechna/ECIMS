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
                <form method="POST" id="clinicalDecisionForm" action="{{ route('clinical-logbook.approve', $entry) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="clinicalDecisionSelect" class="form-label">Decision</label>
                        <select id="clinicalDecisionSelect" class="form-select">
                            <option value="approve" {{ old('_decision', 'approve') === 'approve' ? 'selected' : '' }}>Approve &amp; sign competency</option>
                            <option value="reject" {{ old('_decision') === 'reject' ? 'selected' : '' }}>Return for revision</option>
                        </select>
                    </div>

                    <div class="mb-2">
                        <label for="clinicalFeedbackField" class="form-label" id="clinicalFeedbackLabel">Feedback (optional)</label>
                        <textarea name="reviewer_feedback" id="clinicalFeedbackField" class="form-control @error('reviewer_feedback') is-invalid @enderror" rows="3" placeholder="Commendation or brief note">{{ old('reviewer_feedback') }}</textarea>
                        @error('reviewer_feedback')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div id="remediationFields" class="d-none mb-2">
                        <div class="form-check mb-2">
                            <input type="checkbox" class="form-check-input" name="create_remediation" value="1" id="create_remediation" checked>
                            <label class="form-check-label" for="create_remediation">Also create remediation plan</label>
                        </div>
                        <input type="date" name="remediation_due" class="form-control form-control-sm" value="{{ now()->addWeek()->format('Y-m-d') }}">
                    </div>

                    <button type="submit" id="clinicalDecisionSubmit" class="btn btn-success"><i class="bi bi-check-lg me-1" id="clinicalDecisionIcon"></i><span id="clinicalDecisionLabel">Approve &amp; sign competency</span></button>
                </form>
            </div>
        </div>
        @push('scripts')
        <script>
        (function () {
            var select = document.getElementById('clinicalDecisionSelect');
            var form = document.getElementById('clinicalDecisionForm');
            var textarea = document.getElementById('clinicalFeedbackField');
            var feedbackLabel = document.getElementById('clinicalFeedbackLabel');
            var remediation = document.getElementById('remediationFields');
            var submitBtn = document.getElementById('clinicalDecisionSubmit');
            var submitIcon = document.getElementById('clinicalDecisionIcon');
            var submitLabel = document.getElementById('clinicalDecisionLabel');
            var routes = {
                approve: @json(route('clinical-logbook.approve', $entry)),
                reject: @json(route('clinical-logbook.reject', $entry))
            };

            function applyDecision(value) {
                form.action = routes[value];
                if (value === 'reject') {
                    textarea.setAttribute('required', 'required');
                    textarea.placeholder = 'What must the student correct?';
                    feedbackLabel.innerHTML = 'Feedback (required) <span class="text-danger">*</span>';
                    remediation.classList.remove('d-none');
                    submitBtn.classList.remove('btn-success');
                    submitBtn.classList.add('btn-outline-danger');
                    submitIcon.className = 'bi bi-arrow-return-left me-1';
                    submitLabel.textContent = 'Return to student';
                } else {
                    textarea.removeAttribute('required');
                    textarea.placeholder = 'Commendation or brief note';
                    feedbackLabel.textContent = 'Feedback (optional)';
                    remediation.classList.add('d-none');
                    submitBtn.classList.remove('btn-outline-danger');
                    submitBtn.classList.add('btn-success');
                    submitIcon.className = 'bi bi-check-lg me-1';
                    submitLabel.textContent = 'Approve & sign competency';
                }
            }

            select.addEventListener('change', function () { applyDecision(this.value); });
            applyDecision(select.value);
        })();
        </script>
        @endpush
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
