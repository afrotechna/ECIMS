@extends('layouts.app')
@section('title', 'Add Conduct / Permit Record')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('conduct-records.index') }}">Student Conduct &amp; Permits</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-shield-exclamation me-2 opacity-90"></i>Add Conduct / Permit Record</h1>
</div>
<div class="card card-landing">
    <div class="card-body">
        <form action="{{ route('conduct-records.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="student_id" class="form-label">Student <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_id" name="student_id" required>
                        <option value="">Select student</option>
                        @foreach($students as $s)
                            <option value="{{ $s->id }}" {{ old('student_id', $studentId) == $s->id ? 'selected' : '' }}>{{ $s->reg_no }} — {{ $s->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="date" name="date" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="col-md-3">
                    <label for="type" class="form-label">Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="type" name="type" required>
                        @foreach(\App\Models\ConductRecord::TYPES as $val => $label)
                            <option value="{{ $val }}" {{ old('type') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="sanction" class="form-label">Sanction</label>
                    <input type="text" class="form-control" id="sanction" name="sanction" value="{{ old('sanction') }}">
                </div>
                <div class="col-md-6">
                    <label for="effective_until" class="form-label">Effective until</label>
                    <input type="date" class="form-control" id="effective_until" name="effective_until" value="{{ old('effective_until') }}">
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3">{{ old('description') }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Save</button>
                    <a href="{{ route('conduct-records.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
