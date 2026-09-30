@extends('layouts.app')
@section('title', 'Record Transaction')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('institution-transactions.index') }}">Accountancy · Ledger</a>
    <span class="mx-2">/</span>
    <span>Record</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-plus-lg me-2 opacity-90"></i>Record Transaction</h1>
    <p class="page-subtitle-landing mb-0">General institutional cash in/out — not tied to a creditor payment.</p>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>Transaction details</div>
    <div class="card-body">
        <form action="{{ route('institution-transactions.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                <div class="col-md-4">
                    <label for="cash_account_id" class="form-label">Cash account <span class="text-danger">*</span></label>
                    <select class="form-select @error('cash_account_id') is-invalid @enderror" id="cash_account_id" name="cash_account_id" required>
                        <option value="">Choose…</option>
                        @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ (string) old('cash_account_id') === (string) $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                        @endforeach
                    </select>
                    @error('cash_account_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="direction" class="form-label">Direction <span class="text-danger">*</span></label>
                    <select class="form-select @error('direction') is-invalid @enderror" id="direction" name="direction" required>
                        <option value="in" {{ old('direction') === 'in' ? 'selected' : '' }}>Deposit (in)</option>
                        <option value="out" {{ old('direction', 'out') === 'out' ? 'selected' : '' }}>Expenditure (out)</option>
                    </select>
                    @error('direction')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" min="0.01" class="form-control @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount') }}" required>
                    @error('amount')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="txn_date" class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control @error('txn_date') is-invalid @enderror" id="txn_date" name="txn_date" value="{{ old('txn_date', now()->toDateString()) }}" required>
                    @error('txn_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Category <span class="text-muted">(optional)</span></label>
                    <input type="text" class="form-control @error('category') is-invalid @enderror" id="category" name="category" value="{{ old('category') }}" placeholder="e.g. Salaries, Staff travel">
                    @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-4">
                    <label for="department_id" class="form-label">Department <span class="text-muted">(optional)</span></label>
                    <select class="form-select" id="department_id" name="department_id">
                        <option value="">—</option>
                        @foreach($departments as $dep)
                        <option value="{{ $dep->id }}" {{ (string) old('department_id') === (string) $dep->id ? 'selected' : '' }}>{{ $dep->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="budget_line_id" class="form-label">Budget line <span class="text-muted">(optional)</span></label>
                    <select class="form-select" id="budget_line_id" name="budget_line_id">
                        <option value="">—</option>
                        @foreach($budgetLines as $line)
                        <option value="{{ $line->id }}" {{ (string) old('budget_line_id') === (string) $line->id ? 'selected' : '' }}>{{ $line->financial_year }} — {{ $line->department->name ?? '' }} — {{ $line->item_description }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="creditor_id" class="form-label">Creditor <span class="text-muted">(optional — use Creditors → Record payment instead if paying one off)</span></label>
                    <select class="form-select" id="creditor_id" name="creditor_id">
                        <option value="">—</option>
                        @foreach($creditors as $cr)
                        <option value="{{ $cr->id }}" {{ (string) old('creditor_id') === (string) $cr->id ? 'selected' : '' }}>{{ $cr->payee_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="2">{{ old('description') }}</textarea>
                    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Record Transaction</button>
                <a href="{{ route('institution-transactions.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
