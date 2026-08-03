@extends('layouts.app')
@section('title', 'Student Conduct & Permits')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Student Conduct &amp; Permits</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing">Student Conduct &amp; Permits</h1>
    <p class="page-subtitle-landing mb-0">Discipline, sanctions, and student permits (medical, emergency).</p>
</div>
<div class="d-flex flex-wrap gap-2 align-items-center mb-3">
    <a href="{{ route('conduct-records.create') }}" class="btn btn-primary btn-sm">Add record</a>
    <form method="GET" class="d-flex gap-2 align-items-center">
        <select name="student_id" class="form-select form-select-sm" style="width:auto">
            <option value="">All students</option>
            @foreach($students as $s)
                <option value="{{ $s->id }}" {{ request('student_id') == $s->id ? 'selected' : '' }}>{{ $s->full_name }}</option>
            @endforeach
        </select>
        <select name="type" class="form-select form-select-sm" style="width:auto">
            <option value="">All types</option>
            @foreach(\App\Models\ConductRecord::TYPES as $val => $label)
                <option value="{{ $val }}" {{ request('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Filter</button>
    </form>
</div>
<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead><tr><th>Date</th><th>Student</th><th>Type</th><th>Sanction</th><th>Evidence</th></tr></thead>
            <tbody>
                @forelse($records as $r)
                <tr>
                    <td>{{ $r->date->format('d/m/Y') }}</td>
                    <td>{{ $r->student->full_name ?? '' }}</td>
                    <td>{{ \App\Models\ConductRecord::TYPES[$r->type] ?? $r->type }}</td>
                    <td>{{ Str::limit($r->sanction, 40) }}</td>
                    <td>
                        @if($r->medical_form_path)
                        <a href="{{ route('conduct-records.medical-form', $r) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-medical me-1"></i>Medical form</a>
                        @else
                        <span class="text-muted">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted py-5">No conduct records.</td></tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@if($records->hasPages())<div class="mt-3">{{ $records->links() }}</div>@endif
@endsection
