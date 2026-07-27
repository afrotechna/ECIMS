@extends('layouts.app')
@section('title', 'Transcript — Print')
@section('content')
<div class="d-print-none mb-3">
    <button type="button" class="btn btn-primary" onclick="window.print();"><i class="bi bi-printer me-1"></i>Print transcript</button>
    <a href="{{ route('results.transcript.show', $student) }}" class="btn btn-outline-secondary">Back</a>
</div>
<div class="card card-landing mb-4">
    <div class="card-header-landing">Academic Transcript</div>
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
    <div class="card-header-landing">{{ $sem->label }}</div>
    @if($sum && ($sum->gpa !== null || $sum->academic_remarks))
    <div class="px-2 py-2 small border-bottom" style="background:#f8fafc;">
        GPA: <strong>{{ $sum->gpa !== null ? number_format((float) $sum->gpa, 4) : '—' }}</strong>
        · Remarks: <strong>{{ $sum->academic_remarks ?: '—' }}</strong>
    </div>
    @endif
    <div class="card-body p-0">
        <table class="table table-bordered table-sm mb-0" style="font-size: 11px">
            <thead>
                <tr>
                    <th>Code</th><th>Module</th>
                    <th class="text-end">WRI</th><th class="text-end">WRII</th><th class="text-end">AS1</th><th class="text-end">AS2</th><th class="text-end">Skills</th>
                    <th class="text-end">CA</th><th>Elig.</th><th class="text-end">Exam</th><th class="text-end">Total</th><th>Grd</th>
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
                    <td class="text-end">{{ $c->has_practical ? ($r->ca_practical !== null ? number_format($r->ca_practical, 1) : '—') : '—' }}</td>
                    <td class="text-end">{{ $r->ca_mark !== null ? number_format($r->ca_mark, 1) : '—' }}</td>
                    <td>{{ $r->caModuleRemark() ?: '—' }}</td>
                    <td class="text-end">{{ $r->exam_mark !== null ? number_format($r->exam_mark, 1) : '—' }}</td>
                    <td class="text-end">{{ $r->total_mark !== null ? number_format($r->total_mark, 1) : '—' }}</td>
                    <td>{{ $r->grade ?? '-' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@empty
<p class="text-muted">No results recorded yet.</p>
@endforelse
@push('styles')
<style media="print">.sidebar-wrap, .topbar, .main-content .d-print-none, .student-breadcrumb, .btn { display: none !important; } .main-wrap { margin-left: 0 !important; } body { background: #fff; }</style>
@endpush
@endsection
