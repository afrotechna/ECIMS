@extends('layouts.app')
@section('title', 'Add Creditor')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('creditors.index') }}">Accountancy · Creditors</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Add Creditor</h1>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Creditor details</div>
    <div class="card-body">
        <form action="{{ route('creditors.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="category" class="form-label">Category <span class="text-danger">*</span></label>
                    <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                        <option value="">Choose…</option>
                        @foreach(\App\Models\Creditor::CATEGORIES as $k => $label)
                        <option value="{{ $k }}" {{ old('category') === $k ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="payee_name" class="form-label">Payee <span class="text-danger">*</span></label>
                    <input type="text" class="form-control @error('payee_name') is-invalid @enderror" id="payee_name" name="payee_name" value="{{ old('payee_name') }}" required>
                    @error('payee_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="department_id" class="form-label">Department <span class="text-muted">(optional)</span></label>
                    <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                        <option value="">—</option>
                        @foreach($departments as $dep)
                        <option value="{{ $dep->id }}" {{ (string) old('department_id') === (string) $dep->id ? 'selected' : '' }}>{{ $dep->name }}</option>
                        @endforeach
                    </select>
                    @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description <span class="text-muted">(optional)</span></label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="amount_due" class="form-label">Amount due <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control @error('amount_due') is-invalid @enderror" id="amount_due" name="amount_due" value="{{ old('amount_due') }}" required>
                    @error('amount_due')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="date_incurred" class="form-label">Date incurred <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('date_incurred') is-invalid @enderror" id="date_incurred" name="date_incurred" value="{{ old('date_incurred', now()->toDateString()) }}" required>
                    @error('date_incurred')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="due_date" class="form-label">Due date <span class="text-muted">(optional)</span></label>
                    <input type="date" class="form-control @error('due_date') is-invalid @enderror" id="due_date" name="due_date" value="{{ old('due_date') }}">
                    @error('due_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save Creditor</button>
                <a href="{{ route('creditors.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
