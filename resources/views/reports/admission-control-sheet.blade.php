@extends('layouts.app')
@section('title', 'Students Admission Control Sheet')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="mx-2">/</span>
    <span>Admission Control Sheet</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-clipboard2-data me-2 opacity-90"></i>Students Admission Control Sheet</h1>
        <p class="page-subtitle-landing mb-0">Expected tuition and fees follow the <strong>selected semester</strong>'s fee structure. Tuition paid = sum of payment lines allocated to <strong>tuition</strong>. NACTVET and Form IV registration numbers are the same (one column).</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('reports.admission-control-sheet.export', request()->query()) }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-download me-1"></i>Export control sheet (CSV)</a>
        <button type="button" class="btn btn-outline-primary btn-sm" onclick="window.print();"><i class="bi bi-printer me-1"></i>Print</button>
    </div>
</div>

<div class="alert alert-light border small mb-3">
    <strong>Fee policy (reference):</strong> <strong>Semester I:</strong> tuition fee, NHIF, NACTVET QA; new students submit certificates. <strong>Semester II:</strong> complete remaining tuition (continuous vs repeat/transfer rates). <strong>Gloves</strong> and <strong>ream (A4)</strong> are required in both semesters (bring to college; not bank fees). Skip college NHIF if the student has personal NHIF (tick on student record).
</div>

<div class="card card-landing mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small mb-0">NTA level <span class="text-muted">(separate sheets)</span></label>
                <select name="nta_level" class="form-select form-select-sm">
                    <option value="">All levels</option>
                    <option value="4" {{ request('nta_level') == '4' ? 'selected' : '' }}>NTA Level 4 only</option>
                    <option value="5" {{ request('nta_level') == '5' ? 'selected' : '' }}>NTA Level 5 only</option>
                    <option value="6" {{ request('nta_level') == '6' ? 'selected' : '' }}>NTA Level 6 only</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Semester <span class="text-danger">*</span></label>
                <select name="semester" class="form-select form-select-sm" required>
                    <option value="1" {{ (string) request('semester', '1') === '1' ? 'selected' : '' }}>Semester I</option>
                    <option value="2" {{ (string) request('semester') === '2' ? 'selected' : '' }}>Semester II</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Intake year</label>
                <select name="intake_year" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($intakeYears ?? [] as $y)
                    <option value="{{ $y }}" {{ request('intake_year') == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small mb-0">Programme</label>
                <select name="programme_id" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($programmes as $p)
                    <option value="{{ $p->id }}" {{ request('programme_id') == $p->id ? 'selected' : '' }}>{{ $p->code }} - {{ $p->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small mb-0">Academic year (fees)</label>
                <input type="number" name="academic_year" class="form-control form-control-sm" value="{{ $academicYear }}" min="2020" max="2030">
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
                        <th>EXPECTED TUITION (TZS)<br><small class="text-muted fw-normal">Sem {{ $semesterNumber ?? 1 }}</small></th>
                        <th>TUITION PAID (TZS)</th>
                        <th>TUITION CONTROL NUMBER</th>
                        <th>WHEN WILL COMPLETE TUITION FEE?<br><small class="text-muted">(Specify date & Submit commitment letter)</small></th>
                        <th>NHIF FEE<br><small class="text-muted fw-normal">College</small></th>
                        <th>NHIF PAID (TZS)</th>
                        <th>NHIF STATUS</th>
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
                    @forelse($rows as $r)
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
                    @empty
                    <tr><td colspan="28" class="text-center text-muted py-5">No students match the filter. Adjust NTA level, semester, intake year or programme.</td></tr>
                    @endforelse
                    @if(isset($sheetTotals) && $rows->isNotEmpty())
                    <tr class="table-secondary fw-semibold">
                        <td colspan="10" class="text-end">TOTAL (this page / filter)</td>
                        <td class="text-end">{{ number_format($sheetTotals['expected_tuition']) }}</td>
                        <td class="text-end">{{ number_format($sheetTotals['tuition_paid']) }}</td>
                        <td colspan="16"></td>
                    </tr>
                    @endif
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
