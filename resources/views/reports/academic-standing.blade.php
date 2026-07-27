@extends('layouts.app')
@section('title', 'Academic Standing')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('reports.index') }}">Reports</a>
    <span class="mx-2">/</span>
    <span>Academic standing</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-award me-2 opacity-90"></i>Academic standing</h1>
    <p class="page-subtitle-landing mb-0">Filter by standing: Good standing, Probation, Repeat year, Excluded.</p>
</div>
<div class="card card-landing mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-0">Standing</label>
                <select name="standing" class="form-select form-select-sm">
                    <option value="">All active students</option>
                    @foreach(\App\Models\Student::ACADEMIC_STANDINGS as $val => $label)
                        <option value="{{ $val }}" {{ request('standing') === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary btn-sm">Filter</button></div>
        </form>
    </div>
</div>
<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead><tr><th>Reg No</th><th>Name</th><th>Programme</th><th>Academic standing</th><th></th></tr></thead>
                <tbody>
                    @forelse($students as $s)
                    <tr>
                        <td>{{ $s->reg_no }}</td>
                        <td>{{ $s->full_name }}</td>
                        <td>{{ $s->programme->code ?? '-' }}</td>
                        <td>{{ $s->academic_standing ? (\App\Models\Student::ACADEMIC_STANDINGS[$s->academic_standing] ?? $s->academic_standing) : '—' }}</td>
                        <td>@include('partials.action-edit', ['href' => route('students.edit', $s), 'iconOnly' => true])</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted py-5">No students match the filter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
