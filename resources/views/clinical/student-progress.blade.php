@extends('layouts.app')
@section('title', 'Clinical progress — '.$student->full_name)
@push('styles')
<style>
    .competency-accordion .courses-fold-trigger { cursor: pointer; user-select: none; }
    .competency-accordion .courses-fold-icon { transition: transform 0.2s ease; display: inline-block; }
    .competency-accordion .courses-fold-trigger.collapsed .courses-fold-icon { transform: rotate(-90deg); }
    .competency-dept-card { border: 1px solid #e2e8f0; border-radius: .5rem; margin-bottom: .6rem; overflow: hidden; }
    .competency-dept-card:last-child { margin-bottom: 0; }
    .competency-dept-header { padding: .55rem .9rem; background: #f8fafc; font-size: .875rem; font-weight: 600; }
    .competency-search-empty { display: none; }
</style>
@endpush
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('clinical-logbook.index') }}">Logbook review</a>
    <span class="mx-2">/</span>
    <a href="{{ route('students.show', $student) }}">{{ $student->full_name }}</a>
    <span class="mx-2">/</span>
    <span>Clinical progress</span>
</nav>


<div class="page-header-landing d-flex flex-wrap justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing mb-0">Clinical progress</h1>
        <p class="page-subtitle-landing mb-0">{{ $student->full_name }} · {{ $student->programme?->name }} · NTA {{ $student->nta_level }}</p>
    </div>
    <a href="{{ route('clinical.print.student', $student) }}" class="btn btn-outline-light btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Print logbook</a>
</div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card card-landing mb-3">
            <div class="card-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
                <span>Competency checklist</span>
                @if(!empty($overview['competency']))
                    @php
                        $competencyTotal = count($overview['competency']);
                        $competencyMet = collect($overview['competency'])->where('met', true)->count();
                    @endphp
                    <span class="badge {{ $competencyMet === $competencyTotal ? 'bg-success' : 'bg-light text-dark' }}">{{ $competencyMet }}/{{ $competencyTotal }} met</span>
                @endif
            </div>
            @if(empty($overview['competency']))
                <div class="card-body p-0">
                    <p class="p-3 text-muted mb-0 small">Set minimum counts on procedures in the catalogue to track competency.</p>
                </div>
            @else
                @php
                    $competencyGroups = collect($overview['competency'])
                        ->groupBy(fn ($item) => $item['procedure']->department_code ?: 'other')
                        ->sortKeys();
                @endphp
                <div class="card-body">
                    <input
                        type="search"
                        class="form-control form-control-sm mb-3"
                        id="competencySearch"
                        placeholder="Search procedures by name or code…"
                        aria-label="Search procedures"
                    >
                    <p class="competency-search-empty text-muted small mb-3" id="competencyNoMatch">No procedures match your search.</p>
                    <div class="competency-accordion" id="competencyAccordion">
                        @foreach($competencyGroups as $deptCode => $items)
                        @php
                            $deptLabel = \App\Support\ClinicalRotationCatalog::departmentLabel($deptCode);
                            $deptMet = $items->where('met', true)->count();
                            $deptTotal = $items->count();
                            $deptCollapseId = 'competency-dept-'.\Illuminate\Support\Str::slug($deptCode);
                            $deptOpen = $loop->first;
                        @endphp
                        <div class="competency-dept-card" data-competency-dept>
                            <div
                                class="competency-dept-header d-flex justify-content-between align-items-center gap-2 courses-fold-trigger {{ $deptOpen ? '' : 'collapsed' }}"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $deptCollapseId }}"
                                aria-expanded="{{ $deptOpen ? 'true' : 'false' }}"
                                role="button"
                                tabindex="0"
                            >
                                <span>{{ $deptLabel }}</span>
                                <span class="d-flex align-items-center gap-2">
                                    <span class="badge {{ $deptMet === $deptTotal ? 'bg-success' : 'bg-light text-dark' }}">{{ $deptMet }}/{{ $deptTotal }}</span>
                                    <i class="bi bi-chevron-down courses-fold-icon"></i>
                                </span>
                            </div>
                            <div id="{{ $deptCollapseId }}" class="collapse {{ $deptOpen ? 'show' : '' }}">
                                <table class="table table-sm mb-0">
                                    <thead class="table-light"><tr><th>Procedure</th><th>Required</th><th>Approved</th><th></th></tr></thead>
                                    <tbody>
                                        @foreach($items as $item)
                                        <tr data-competency-row data-competency-search="{{ strtolower($item['procedure']->code.' '.$item['procedure']->name) }}">
                                            <td>{{ $item['procedure']->code }} — {{ $item['procedure']->name }}</td>
                                            <td>{{ $item['required'] }}</td>
                                            <td>{{ $item['approved'] }}</td>
                                            <td>@if($item['met'])<span class="badge bg-success">Met</span>@else<span class="badge bg-warning text-dark">Incomplete</span>@endif</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @push('scripts')
                <script>
                (function () {
                    var input = document.getElementById('competencySearch');
                    if (!input) return;
                    var noMatch = document.getElementById('competencyNoMatch');
                    input.addEventListener('input', function () {
                        var term = this.value.trim().toLowerCase();
                        var anyVisible = false;
                        document.querySelectorAll('[data-competency-dept]').forEach(function (dept) {
                            var deptHasMatch = false;
                            dept.querySelectorAll('[data-competency-row]').forEach(function (row) {
                                var match = term === '' || row.getAttribute('data-competency-search').includes(term);
                                row.classList.toggle('d-none', !match);
                                if (match) deptHasMatch = true;
                            });
                            dept.classList.toggle('d-none', !deptHasMatch);
                            if (deptHasMatch) anyVisible = true;
                            if (term !== '' && deptHasMatch) {
                                var trigger = dept.querySelector('.courses-fold-trigger');
                                var target = document.querySelector(trigger.getAttribute('data-bs-target'));
                                if (target && !target.classList.contains('show')) {
                                    new bootstrap.Collapse(target, { toggle: true });
                                }
                            }
                        });
                        noMatch.style.display = (term !== '' && !anyVisible) ? 'block' : 'none';
                    });
                })();
                </script>
                @endpush
            @endif
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
