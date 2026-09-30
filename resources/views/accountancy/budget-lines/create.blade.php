@extends('layouts.app')
@section('title', 'Add Budget Line')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('budget-lines.index') }}">Accountancy · Department Budgets</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add Budget Line</h1>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Budget line details</div>
    <div class="card-body">
        <form action="{{ route('budget-lines.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                    <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id" required>
                        <option value="">Choose…</option>
                        @foreach($departments as $dep)
                        <option value="{{ $dep->id }}" {{ (string) old('department_id') === (string) $dep->id ? 'selected' : '' }}>{{ $dep->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="financial_year" class="form-label">Financial year <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('financial_year') is-invalid @enderror" id="financial_year" name="financial_year" value="{{ old('financial_year') }}" placeholder="2026/2027" pattern="\d{4}/\d{4}" required>
                    @error('financial_year')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="item_description" class="form-label">Item <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('item_description') is-invalid @enderror" id="item_description" name="item_description" value="{{ old('item_description') }}" required>
                    @error('item_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="annual_budget" class="form-label">Annual budget <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0" class="form-control @error('annual_budget') is-invalid @enderror" id="annual_budget" name="annual_budget" value="{{ old('annual_budget', 0) }}" required>
                    @error('annual_budget')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Budget Line</button>
                <a href="{{ route('budget-lines.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
