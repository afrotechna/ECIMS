@extends('layouts.app')
@section('title', 'Clinical training framework')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('clinical-rotations.index') }}">Clinical rotation</a>
    <span class="mx-2">/</span>
    <span>Process framework</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing mb-0"><i class="bi bi-diagram-3 me-2 opacity-90"></i>Clinical training process framework</h1>
    <p class="page-subtitle-landing mb-0">Musoma COHAS · Clinical Medicine (CMT) · NTA Level {{ $practicum_level ?? 4 }} practicum: <strong>{{ $practicum_source ?? 'CMT Practicum Guide' }}</strong></p>
</div>

<div class="btn-group mb-3" role="group" aria-label="NTA level">
    @foreach([4, 5, 6] as $lv)
        <a href="{{ route('clinical.framework', ['level' => $lv]) }}" class="btn btn-sm {{ ($practicum_level ?? 4) === $lv ? 'btn-primary' : 'btn-outline-primary' }}">NTA Level {{ $lv }}</a>
    @endforeach
</div>

<div class="card card-landing mb-3">
    <div class="card-header-landing">Clinical flow (8 stages)</div>
    <div class="card-body">
        <ol class="mb-0">
            <li class="mb-2"><strong>Student attends clinical placement</strong> — rotation areas per NTA level practicum guide (medicine, surgery, O&amp;G, paediatrics, community health, etc.).</li>
            <li class="mb-2"><strong>Clinical skills / procedures performed</strong> — under supervision at posting site per practicum guide.</li>
            <li class="mb-2"><strong>Clinical Instructor supervises and signs competency</strong> — validates logbook entries in this system.</li>
            <li class="mb-2"><strong>Case logbook / procedure records updated</strong> — each checklist/procedure in the guide must be assessed and signed off ({{ $procedure_count ?? 0 }} assessable items at NTA {{ $practicum_level ?? 4 }}).</li>
            <li class="mb-2"><strong>Academic staff reviews progress periodically</strong> — coordinator monitors attendance and logbook completion.</li>
            <li class="mb-2"><strong>Evaluation teachers assess competency</strong> — linked to CA, OSCE/OSPE, and end-of-semester results.</li>
            <li class="mb-2"><strong>Feedback and remediation</strong> — returned entries; repeat practice where not yet competent.</li>
            <li class="mb-0"><strong>Final competency / progression decision</strong> — academic committee when requirements are met.</li>
        </ol>
    </div>
</div>

@if(!empty($semester_modules ?? []))
<div class="card card-landing mb-3">
    <div class="card-header-landing">NTA 4 Semester II modules (clinical)</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead class="table-light"><tr><th>Code</th><th>Module</th></tr></thead>
            <tbody>
                @foreach($semester_modules as $code => $title)
                <tr><td class="font-monospace">{{ $code }}</td><td>{{ $title }}</td></tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(!empty($practicum_by_department ?? []))
<div class="card card-landing mb-3">
    <div class="card-header-landing">NTA 4 logbook catalogue by posting area</div>
    <div class="card-body">
        @if(!empty($assessment_methods ?? []))
        <p class="small mb-2"><strong>Assessment methods:</strong> {{ implode(' · ', $assessment_methods) }}</p>
        @endif
        @foreach($practicum_by_department as $deptCode => $items)
        <h6 class="text-uppercase text-muted small mt-3 mb-2">{{ ($rotation_area_labels ?? [])[$deptCode] ?? $deptCode }}</h6>
        <div class="table-responsive mb-2">
            <table class="table table-sm small mb-0">
                <thead class="table-light"><tr><th>Code</th><th>Procedure</th><th>Assessment</th><th>Min.</th></tr></thead>
                <tbody>
                    @foreach($items as $item)
                    <tr>
                        <td class="fw-semibold">{{ $item['code'] }}</td>
                        <td>{{ $item['name'] }}</td>
                        <td>{{ $item['assessment_modes'] ?? '—' }}</td>
                        <td>{{ $item['min_required_count'] }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endforeach
        <p class="small text-muted mt-3 mb-0">Sync catalogue: <code>php artisan clinical:sync-cmt4-practicum</code></p>
    </div>
</div>
@endif

<div class="row g-3">
    @foreach([
        ['Clinical Medicine Student', 'Attends placement; records procedures from practicum guide; submits logbook; completes remediation.'],
        ['Clinical Instructor', 'Supervises ward activity; approves or returns logbook entries; records weekly attendance.'],
        ['Academic Office / Coordinator', 'Plans rotations; assigns hospitals; monitors progress; escalates remediation.'],
        ['Evaluation Teachers / Examiners', 'Summative assessment (CA, exams, OSCE/OSPE); progression recommendations.'],
    ] as [$role, $duty])
    <div class="col-md-6">
        <div class="card card-landing h-100">
            <div class="card-header-landing">{{ $role }}</div>
            <div class="card-body"><p class="mb-0 small">{{ $duty }}</p></div>
        </div>
    </div>
    @endforeach
</div>

<div class="mt-3 d-flex flex-wrap gap-2">
    <a href="{{ route('clinical-logbook.index') }}" class="btn btn-primary btn-sm">Open logbook review</a>
    <a href="{{ route('clinical-procedures.index', ['nta_level' => 4]) }}" class="btn btn-outline-primary btn-sm">Procedures catalogue (NTA 4)</a>
    <a href="{{ route('clinical-rotations.index') }}" class="btn btn-outline-secondary btn-sm">Rotation rounds</a>
</div>
@endsection
