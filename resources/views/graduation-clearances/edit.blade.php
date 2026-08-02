@extends('layouts.app')
@section('title', 'Edit graduation clearance')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('graduation-clearances.index') }}">Graduation clearances</a>
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>
<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-clipboard-check me-2 opacity-90"></i>Edit clearance</h1>
    <p class="page-subtitle-landing mb-0">{{ $graduation_clearance->student->full_name ?? '' }}</p>
</div>
<div class="card card-landing">
    <div class="card-body">
        <form action="{{ route('graduation-clearances.update', $graduation_clearance) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Library cleared</label>
                    <select class="form-select" name="library_cleared">
                        <option value="no" {{ $graduation_clearance->library_cleared === 'no' ? 'selected' : '' }}>No</option>
                        <option value="yes" {{ $graduation_clearance->library_cleared === 'yes' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Finance cleared</label>
                    <select class="form-select" name="finance_cleared">
                        <option value="no" {{ $graduation_clearance->finance_cleared === 'no' ? 'selected' : '' }}>No</option>
                        <option value="yes" {{ $graduation_clearance->finance_cleared === 'yes' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Accommodation cleared</label>
                    <select class="form-select" name="accommodation_cleared">
                        <option value="no" {{ $graduation_clearance->accommodation_cleared === 'no' ? 'selected' : '' }}>No</option>
                        <option value="yes" {{ $graduation_clearance->accommodation_cleared === 'yes' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Academic cleared</label>
                    <select class="form-select" name="academic_cleared">
                        <option value="no" {{ $graduation_clearance->academic_cleared === 'no' ? 'selected' : '' }}>No</option>
                        <option value="yes" {{ $graduation_clearance->academic_cleared === 'yes' ? 'selected' : '' }}>Yes</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2">{{ old('notes', $graduation_clearance->notes) }}</textarea>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('graduation-clearances.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
