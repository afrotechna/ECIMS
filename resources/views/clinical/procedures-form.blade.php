@extends('layouts.app')
@section('title', $procedure->exists ? 'Edit procedure' : 'Add procedure')
@section('content')
<div class="page-header-landing">
    <h1 class="page-title-landing mb-0">{{ $procedure->exists ? 'Edit procedure' : 'Add clinical procedure' }}</h1>
</div>
<div class="card card-landing">
    <div class="card-body">
        <form method="POST" action="{{ $procedure->exists ? route('clinical-procedures.update', $procedure) : route('clinical-procedures.store') }}">
            @csrf
            @if($procedure->exists) @method('PUT') @endif
            <div class="row g-3">
                <div class="col-md-2">
                    <label class="form-label">NTA level</label>
                    <select name="nta_level" class="form-select" required>
                        @foreach([4,5,6] as $l)
                            <option value="{{ $l }}" {{ (int) old('nta_level', $procedure->nta_level) === $l ? 'selected' : '' }}>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Code</label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $procedure->code) }}" required maxlength="32">
                </div>
                <div class="col-md-7">
                    <label class="form-label">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $procedure->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <select name="department_code" class="form-select">
                        <option value="">Any</option>
                        @foreach($departmentLabels as $code => $label)
                            <option value="{{ $code }}" {{ old('department_code', $procedure->department_code) === $code ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Programme (optional)</label>
                    <select name="programme_id" class="form-select">
                        <option value="">All programmes</option>
                        @foreach($programmes as $prog)
                            <option value="{{ $prog->id }}" {{ (int) old('programme_id', $procedure->programme_id) === $prog->id ? 'selected' : '' }}>{{ $prog->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $procedure->description) }}</textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label">Modes of assessment</label>
                    <input type="text" name="assessment_modes" class="form-control" value="{{ old('assessment_modes', $procedure->assessment_modes) }}" placeholder="e.g. Practical, OSCE, Checklist, Written test">
                    <div class="form-text">Comma-separated — as in the practicum guide.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Practicum section</label>
                    <input type="text" name="practicum_section" class="form-control" value="{{ old('practicum_section', $procedure->practicum_section) }}" placeholder="e.g. Patient Care posting">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Min required (optional)</label>
                    <input type="number" name="min_required_count" class="form-control" min="0" value="{{ old('min_required_count', $procedure->min_required_count) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Sort order</label>
                    <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $procedure->sort_order) }}">
                </div>
                <div class="col-12">
                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active" {{ old('is_active', $procedure->is_active) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <hr>
            <button type="submit" class="btn btn-primary">Save</button>
            <a href="{{ route('clinical-procedures.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
