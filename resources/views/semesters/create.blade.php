@extends('layouts.app')

@section('title', 'Add Semester')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('semesters.index') }}">Semesters</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add Semester</h1>
    <p class="page-subtitle-landing mb-0">Academic year and Semester I or II, plus key dates.</p>
</div>


<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Semester details</div>
    <div class="card-body">
        <form action="{{ route('semesters.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="academic_year" class="form-label">Academic year</label>
                    <select class="form-select @error('academic_year') is-invalid @enderror" id="academic_year" name="academic_year" required data-no-search>
                        @foreach($academicYearOptions as $start => $label)
                            <option value="{{ $start }}" {{ (string) old('academic_year', \App\Support\AcademicSession::defaultStartYear()) === (string) $start ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('academic_year')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="number" class="form-label">Semester</label>
                    <select class="form-select @error('number') is-invalid @enderror" id="number" name="number" required>
                        @foreach($periodOptions as $value => $text)
                            <option value="{{ $value }}" {{ (string) old('number', '1') === (string) $value ? 'selected' : '' }}>{{ $text }}</option>
                        @endforeach
                    </select>
                    @error('number')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="start_date" class="form-label">Start date</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="{{ old('start_date') }}">
                </div>
                <div class="col-md-6">
                    <label for="end_date" class="form-label">End date</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="{{ old('end_date') }}">
                </div>
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_active">Active</label>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Semester</button>
                <a href="{{ route('semesters.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
