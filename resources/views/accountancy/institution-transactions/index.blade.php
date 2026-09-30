@extends('layouts.app')
@section('title', 'Payments & Deposits Ledger')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accountancy</span>
    <span class="mx-2">/</span>
    <span>Ledger</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-journal-text me-2 opacity-90"></i>Payments &amp; Deposits Ledger</h1>
        <p class="page-subtitle-landing mb-0">Institutional cash in/out — salaries, supplier payments, deposits.</p>
    </div>
    @canModule('accountancy', 'create')
    <a href="{{ route('institution-transactions.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Record Transaction</a>
    @endcanModule
</div>

<form method="GET" class="card card-landing mb-3">
    <div class="card-body d-flex flex-wrap gap-2 align-items-end">
        <div>
            <label class="form-label small mb-1">Cash account</label>
            <select name="cash_account_id" class="form-select form-select-sm" style="width:220px">
                <option value="">All accounts</option>
                @foreach($accounts as $acc)
                <option value="{{ $acc->id }}" {{ (int) $cashAccountId === $acc->id ? 'selected' : '' }}>{{ $acc->name }} — {{ number_format($acc->balance()) }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-sm btn-primary"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="{{ route('institution-transactions.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
    </div>
</form>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Account</th>
                        <th>Direction</th>
                        <th class="text-end">Amount</th>
                        <th>Category</th>
                        <th>Description</th>
                        <th>Recorded by</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $t)
                    <tr class="{{ $t->voided ? 'text-muted text-decoration-line-through' : '' }}">
                        <td>{{ $t->txn_date->format('d M Y') }}</td>
                        <td>{{ $t->cashAccount->name ?? '—' }}</td>
                        <td>
                            @if($t->direction === 'in')
                                <span class="badge bg-success">In</span>
                            @else
                                <span class="badge bg-danger">Out</span>
                            @endif
                            @if($t->voided)<span class="badge bg-secondary ms-1">Voided</span>@endif
                        </td>
                        <td class="text-end">{{ number_format($t->amount) }}</td>
                        <td>{{ $t->category ?? '—' }}</td>
                        <td class="small">{{ $t->description }}
                            @if($t->creditor)<br><span class="text-muted">Creditor: {{ $t->creditor->payee_name }}</span>@endif
                            @if($t->budgetLine)<br><span class="text-muted">Budget: {{ $t->budgetLine->item_description }}</span>@endif
                        </td>
                        <td>{{ $t->recordedBy->name ?? '—' }}</td>
                        <td class="text-end">
                            @if(! $t->voided)
                            @canModule('accountancy', 'delete')
                            <form action="{{ route('institution-transactions.void', $t) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="button" class="btn btn-sm btn-cohas-delete" data-swal-confirm data-swal-title="Void this transaction?" data-swal-text="This reverses its effect on the account balance." data-swal-icon="warning">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            </form>
                            @endcanModule
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted py-5">No transactions recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($transactions->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
