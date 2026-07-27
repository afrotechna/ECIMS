@extends('layouts.app')
@section('title', 'Certificate collection')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Certificate collection</span>
</nav>
<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-patch-check me-2 opacity-90"></i>Certificate collection</h1>
        <p class="page-subtitle-landing mb-0">Graduated students collecting their printed certificate.</p>
    </div>
    @canModule('certificate_collection', 'create')
    <a href="{{ route('certificate-collections.create') }}" class="btn btn-cta"><i class="bi bi-plus-lg me-1"></i>Record collection</a>
    @endcanModule
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('certificate-collections.index') }}" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Academic year</label>
                <select name="academic_year" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($academicYears as $y)
                        <option value="{{ $y }}" {{ (string) request('academic_year') === (string) $y ? 'selected' : '' }}>{{ \App\Support\AcademicSession::label((int) $y) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Programme</label>
                <select name="programme_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All</option>
                    @foreach($programmes as $p)
                        <option value="{{ $p->id }}" {{ (string) request('programme_id') === (string) $p->id ? 'selected' : '' }}>{{ $p->code }}</option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Student</th><th>Sex</th><th>Certificate No.</th><th>Academic Year</th><th>Phone</th><th>Recorded by</th></tr></thead>
            <tbody>
                @forelse($collections as $c)
                <tr>
                    <td>{{ $c->collected_on?->format('d M Y') }}</td>
                    <td><a href="{{ route('students.show', $c->student) }}">{{ $c->student->reg_no ?? '' }} — {{ $c->student->full_name ?? '' }}</a></td>
                    <td>{{ $c->student->gender ?? '' }}</td>
                    <td>{{ $c->certificate_number }}</td>
                    <td>{{ $c->academic_year }}/{{ $c->academic_year + 1 }}</td>
                    <td>{{ $c->phone_number }}</td>
                    <td>{{ $c->recordedBy->name ?? '' }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="text-center text-muted py-5">No certificate collections recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if($collections->hasPages())<div class="mt-3">{{ $collections->links() }}</div>@endif
@endsection
