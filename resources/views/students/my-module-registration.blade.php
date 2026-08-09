@extends('layouts.app')
@section('title', 'Register modules')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Register modules</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-check2-square me-2 opacity-90"></i>Register modules for semester</h1>
    <p class="page-subtitle-landing mb-0">
        @if($edit_mode ?? false)
            Choose the modules you will study this semester. If you are repeating only some modules, tick those modules only.
        @else
            Your saved module selection for this semester.
        @endif
    </p>
</div>


@if($block_reason ?? null)
    <div class="alert alert-warning">{{ $block_reason }}</div>
@endif

@if(($repeat_suggestions ?? collect())->isNotEmpty() && ($edit_mode ?? false))
    <div class="alert alert-info">
        <strong>Modules to repeat:</strong> based on failed results, you may need:
        {{ $repeat_suggestions->map(fn ($c) => $c->code)->join(', ') }}.
        These are pre-selected below when not already registered.
    </div>
@endif

@if(($semesters ?? collect())->count() > 1)
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-calendar3 me-2"></i>Semester</div>
    <div class="card-body py-3">
        <form method="GET" action="{{ route('my.module-registration') }}" class="row g-3 align-items-end">
            @if($edit_mode ?? false)
                <input type="hidden" name="edit" value="1">
            @endif
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
    $enrolledIds = $enrolled_ids ?? [];
    $carryIds = $carry_repeat_ids ?? [];
@endphp

{{-- Saved selection summary (default after save) --}}
@if(!($edit_mode ?? false) && ($enrolled_courses ?? collect())->isNotEmpty())
<div class="card card-landing mb-3 border-success">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-check-circle me-2 text-success"></i>{{ $semester->label }} — your modules</span>
        <span class="badge bg-success">{{ $enrolled_courses->count() }} selected</span>
    </div>
    <div class="card-body p-0">
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
        <div class="p-3 border-top bg-light d-flex flex-wrap gap-2">
            @if($can_register ?? false)
                <a href="{{ route('my.module-registration', ['semester_id' => $semester->id, 'edit' => 1]) }}" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-pencil me-1"></i> Change selection
                </a>
            @endif
            <a href="{{ route('my.modules') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-journal-bookmark me-1"></i> Module catalogue
            </a>
        </div>
    </div>
</div>
@endif

{{-- Full selection form (first time or edit mode) --}}
@if(($edit_mode ?? false) || ($enrolled_courses ?? collect())->isEmpty())
<div class="card card-landing mb-3">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-journal-bookmark me-2"></i>{{ $semester->label }}</span>
        @if($can_register ?? false)
            <span class="badge bg-success">Registration open</span>
        @else
            <span class="badge bg-secondary">View only</span>
        @endif
    </div>
    <div class="card-body">
        @if(($available ?? collect())->isEmpty())
            <p class="text-muted mb-0">No modules are configured for your programme and NTA level in this semester. Contact the registry office.</p>
        @else
            @if(($enrolled_courses ?? collect())->isNotEmpty())
                <div class="d-flex flex-wrap justify-content-end align-items-center gap-2 mb-3">
                    <a href="{{ route('my.module-registration', ['semester_id' => $semester->id]) }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-lg me-1"></i> Cancel editing
                    </a>
                </div>
            @else
                <p class="small text-muted">
                    <strong>{{ $available->count() }}</strong> module(s) available.
                    Tick <strong>Repeating / carry</strong> for modules you are retaking.
                </p>
            @endif

            <form method="POST" action="{{ route('my.module-registration.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="semester_id" value="{{ $semester->id }}">

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 module-registration-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width:3rem">Take</th>
                                <th>Code</th>
                                <th>Module</th>
                                <th class="text-end">Credits</th>
                                <th>Clinical rotation</th>
                                <th style="width:8rem">Repeating</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($available as $course)
                            @php
                                $suggestIds = $repeat_suggestion_ids ?? [];
                                $isEnrolled = in_array((int) $course->id, $enrolledIds, true);
                                $checked = $isEnrolled
                                    || (empty($enrolledIds) && in_array((int) $course->id, $suggestIds, true));
                                $carry = in_array((int) $course->id, $carryIds, true)
                                    || in_array((int) $course->id, $suggestIds, true);
                            @endphp
                            <tr class="{{ $isEnrolled ? 'module-row--chosen' : '' }}">
                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input module-select"
                                        name="course_ids[]"
                                        value="{{ $course->id }}"
                                        id="course_{{ $course->id }}"
                                        {{ $checked ? 'checked' : '' }}
                                        {{ ($can_register ?? false) ? '' : 'disabled' }}
                                    >
                                </td>
                                <td>
                                    <label for="course_{{ $course->id }}" class="mb-0 fw-semibold">{{ $course->code }}</label>
                                    @if($isEnrolled)
                                        <span class="badge bg-success-subtle text-success ms-1 small">Chosen</span>
                                    @endif
                                </td>
                                <td>{{ $course->name }}</td>
                                <td class="text-end">{{ $course->credits }}</td>
                                <td>
                                    @if($course->requires_clinical_rotation)
                                        <span class="badge bg-primary-subtle text-primary">Yes</span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">No (classroom)</span>
                                    @endif
                                </td>
                                <td>
                                    <input
                                        type="checkbox"
                                        class="form-check-input carry-repeat"
                                        name="carry_repeat_course_ids[]"
                                        value="{{ $course->id }}"
                                        data-course="{{ $course->id }}"
                                        {{ $carry ? 'checked' : '' }}
                                        {{ ($can_register ?? false) ? '' : 'disabled' }}
                                    >
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @error('course_ids')<div class="text-danger small mt-2">{{ $message }}</div>@enderror

                @if($can_register ?? false)
                    <div class="mt-3 d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save module selection</button>
                        @if(($enrolled_courses ?? collect())->isNotEmpty())
                            <a href="{{ route('my.module-registration', ['semester_id' => $semester->id]) }}" class="btn btn-outline-secondary">Back to summary</a>
                        @else
                            <a href="{{ route('my.modules') }}" class="btn btn-outline-secondary">View full module catalogue</a>
                        @endif
                    </div>
                @endif
            </form>
        @endif
    </div>
</div>
@endif
@endif

@endsection

@push('styles')
<style>
.module-registration-table tr.module-row--chosen {
    opacity: 0.62;
    background-color: rgba(34, 197, 94, 0.06);
}
.module-registration-table tr.module-row--chosen:hover {
    opacity: 0.85;
}
.module-registration-table tr.module-row--chosen td {
    color: #64748b;
}
.module-registration-table tr.module-row--chosen .fw-semibold,
.module-registration-table tr.module-row--chosen label {
    color: #475569;
}
</style>
@endpush

@push('scripts')
<script>
document.querySelectorAll('.carry-repeat').forEach(function (carryBox) {
    carryBox.addEventListener('change', function () {
        if (!this.checked) return;
        var courseId = this.dataset.course;
        var mod = document.getElementById('course_' + courseId);
        if (mod) mod.checked = true;
    });
});
document.querySelectorAll('.module-select').forEach(function (modBox) {
    modBox.addEventListener('change', function () {
        var row = this.closest('tr');
        if (row) {
            row.classList.toggle('module-row--chosen', this.checked);
        }
        if (this.checked) return;
        var carry = document.querySelector('.carry-repeat[data-course="' + this.value + '"]');
        if (carry) carry.checked = false;
    });
});
</script>
@endpush
