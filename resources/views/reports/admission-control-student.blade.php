@extends('layouts.app')
@section('title', 'Admission control sheet — '.$student->full_name)
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    @if(auth()->user()->canAccessFinance())
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="mx-2">/</span>
    @endif
    <span>Admission control (one student)</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-clipboard2-data me-2 opacity-90"></i>Admission control sheet</h1>
        <p class="page-subtitle-landing mb-0">
            <strong>{{ $student->full_name }}</strong>
            · System reg: {{ $student->reg_no }}
            @if($student->official_registry_no)
                · Official registry: <code>{{ $student->official_registry_no }}</code>
            @endif
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print();"><i class="bi bi-printer me-1"></i>Print</button>
        <a href="{{ route('students.show', $student) }}" class="btn btn-light btn-sm text-dark">Student profile</a>
    </div>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Semester (fee column)</label>
                <select name="semester" class="form-select form-select-sm">
                    <option value="1" {{ (string) request('semester', (string) $semesterNumber) === '1' ? 'selected' : '' }}>Semester I</option>
                    <option value="2" {{ (string) request('semester') === '2' ? 'selected' : '' }}>Semester II</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Academic year (fees)</label>
                <input type="number" name="academic_year" class="form-control form-control-sm" value="{{ request('academic_year', $academicYear) }}" min="2020" max="2035">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Apply</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle mb-0 admission-control-sheet-table">
                <thead class="table-light">
                    <tr>
                        <th>S/N</th>
                        <th>NAME (FIRST, MIDDLE & SURNAME)</th>
                        <th>GENDER</th>
                        <th>NACTVET / FORM IV REG. NO.</th>
                        <th>NTA LEVEL</th>
                        <th>PROGRAMME OF STUDY</th>
                        <th>YEAR OF STUDY</th>
                        <th>REPORTING STATUS</th>
                        <th>REPORTING DATE</th>
                        <th>TUITION FEE STATUS</th>
                        <th>EXPECTED TUITION (TZS)<br><small class="text-muted fw-normal">Sem {{ $semesterNumber }}</small></th>
                        <th>TUITION PAID (TZS)</th>
                        <th>TUITION CONTROL NUMBER</th>
                        <th>WHEN WILL COMPLETE TUITION FEE?</th>
                        <th>NHIF FEE</th>
                        <th>NHIF PAID (TZS)</th>
                        <th>NHIF STATUS</th>
                        <th>NHIF CONTROL NUMBER</th>
                        <th>NACTVET QA FEE (TZS)</th>
                        <th>NACTVET QA PAID (TZS)</th>
                        <th>NACTVET QA STATUS</th>
                        <th>NACTVET QA CONTROL NUMBER</th>
                        <th>JOINING INSTRUCTION NON ACADEMIC REQUIREMENTS SUBMITTED</th>
                        <th>CERTIFICATES SUBMITTED</th>
                        <th>HOSTEL ALLOCATION (BLOCK NO.)</th>
                        <th>CLASS</th>
                        <th>CLASS PROPERTY RECEIVED</th>
                        <th>CHAIR NUMBER</th>
                        <th>TABLE NUMBER</th>
                    </tr>
                </thead>
                <tbody>
                    @php $r = $row; @endphp
                    <tr>
                        <td>{{ $r['sn'] }}</td>
                        <td>{{ $r['name'] }}</td>
                        <td>{{ $r['gender'] }}</td>
                        <td><code class="small">{{ $r['registration_number'] }}</code></td>
                        <td>{{ $r['nta_level'] }}</td>
                        <td>{{ $r['programme'] }}</td>
                        <td>{{ $r['year_of_study'] }}</td>
                        <td>{{ $r['reporting_status'] }}</td>
                        <td>{{ $r['reporting_date'] }}</td>
                        <td>{{ $r['tuition_fee_status'] }}</td>
                        <td class="text-end">@if(is_numeric($r['expected_tuition'])){{ number_format($r['expected_tuition']) }}@endif</td>
                        <td class="text-end">@if(is_numeric($r['tuition_paid'])){{ number_format($r['tuition_paid']) }}@endif</td>
                        <td>{{ $r['tuition_control_number'] ?? $r['payment_reference'] }}</td>
                        <td>{{ $r['when_complete_tuition'] ?? '' }}</td>
                        <td class="text-end">
                            @if($r['nhif_fee'] === '—')
                                —
                            @elseif(is_numeric($r['nhif_fee']))
                                {{ number_format($r['nhif_fee']) }}
                            @endif
                        </td>
                        <td class="text-end">
                            @if(is_numeric($r['nhif_paid'] ?? null))
                                {{ number_format($r['nhif_paid']) }}
                            @endif
                        </td>
                        <td>{{ $r['nhif_status'] ?? '' }}</td>
                        <td>{{ $r['nhif_payment_ref'] ?? $r['nhif_ref'] ?? '' }}</td>
                        <td class="text-end">
                            @if(is_numeric($r['nactvet_qa_fee']))
                                {{ number_format($r['nactvet_qa_fee']) }}
                            @endif
                        </td>
                        <td class="text-end">
                            @if(is_numeric($r['nactvet_qa_paid'] ?? null))
                                {{ number_format($r['nactvet_qa_paid']) }}
                            @endif
                        </td>
                        <td>{{ $r['nactvet_qa_status'] ?? '' }}</td>
                        <td>{{ $r['nactvet_qa_payment_ref'] ?? '' }}</td>
                        <td>{{ $r['joining_instructions_submitted'] ?? '' }}</td>
                        <td>{{ $r['academic_requirements'] ?? '' }}</td>
                        <td>{{ $r['hostel_allocation'] }}</td>
                        <td>{{ $r['class_group'] ?? '' }}</td>
                        <td>{{ $r['class_property_received'] ?? '' }}</td>
                        <td>{{ $r['chair_number'] ?? '' }}</td>
                        <td>{{ $r['table_number'] ?? '' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .student-breadcrumb, .page-header-landing .btn, .card-landing.mb-3 form, .alert, .nav, .topbar, .profile-dropdown, footer { display: none !important; }
    .admission-control-sheet-table { font-size: 0.65rem; }
    .admission-control-sheet-table th, .admission-control-sheet-table td { padding: 2px 4px; }
}
</style>
@endsection
