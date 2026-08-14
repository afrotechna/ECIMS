@extends('layouts.app')
@section('title', 'My registered modules')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>My registered modules</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-check2-square me-2 opacity-90"></i>My registered modules</h1>
    <p class="page-subtitle-landing mb-0">
        Modules are registered on your behalf by the college once your semester registration is approved. This page is view-only.
    </p>
</div>

@if($block_reason ?? null)
    <div class="alert alert-warning">{{ $block_reason }}</div>
@endif

@if(($semesters ?? collect())->count() > 1)
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-calendar3 me-2"></i>Semester</div>
    <div class="card-body py-3">
        <form method="GET" action="{{ route('my.module-registration') }}" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label for="semester_id" class="form-label">Active semester with approved registration</label>
                <select name="semester_id" id="semester_id" class="form-select">
                    @foreach($semesters as $s)
                        <option value="{{ $s->id }}" {{ ($semester?->id ?? null) === $s->id ? 'selected' : '' }}>{{ $s->label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <button type="submit" class="btn btn-outline-primary"><i class="bi bi-arrow-repeat me-1"></i> Switch semester</button>
            </div>
        </form>
    </div>
</div>
@endif

@if($semester)
@php
    $carryIds = $carry_repeat_ids ?? [];
@endphp

<div class="card card-landing mb-3">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-journal-bookmark me-2"></i>{{ $semester->label }} — your modules</span>
        <span class="badge bg-success">{{ ($enrolled_courses ?? collect())->count() }} registered</span>
    </div>
    <div class="card-body p-0">
        @if(($enrolled_courses ?? collect())->isEmpty())
            <p class="text-muted mb-0 p-4">No modules registered yet for this semester. Your modules are registered by the college once your semester registration is approved — contact the registry office if you believe this is a mistake.</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Module</th>
                            <th class="text-end">Credits</th>
                            <th>Clinical rotation</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($enrolled_courses as $course)
                        <tr>
                            <td class="fw-semibold">{{ $course->code }}</td>
                            <td>{{ $course->name }}</td>
                            <td class="text-end">{{ $course->credits }}</td>
                            <td>
                                @if($course->requires_clinical_rotation)
                                    <span class="badge bg-primary-subtle text-primary">Yes</span>
                                @else
                                    <span class="badge bg-secondary-subtle text-secondary">No</span>
                                @endif
                            </td>
                            <td>
                                @if(in_array((int) $course->id, $carryIds, true))
                                    <span class="badge bg-warning text-dark">Repeating</span>
                                @else
                                    <span class="badge bg-success-subtle text-success">Registered</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="p-3 border-top bg-light">
            <a href="{{ route('my.modules') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-journal-bookmark me-1"></i> Module catalogue
            </a>
        </div>
    </div>
</div>
@endif

@endsection
