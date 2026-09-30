@extends('layouts.app')
@section('title', 'Cash Accounts')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accountancy</span>
    <span class="mx-2">/</span>
    <span>Cash Accounts</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-bank me-2 opacity-90"></i>Cash Accounts</h1>
        <p class="page-subtitle-landing mb-0">Institution deposit / recurrent accounts. Grand total: <strong>{{ number_format($grandTotal) }}</strong> TZS.</p>
    </div>
    @canModule('accountancy', 'create')
    <a href="{{ route('cash-accounts.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Account</a>
    @endcanModule
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-list-ul me-2"></i>Accounts</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th class="text-end">Opening balance</th>
                        <th class="text-end">Current balance</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $a)
                    <tr>
                        <td><strong>{{ $a->name }}</strong></td>
                        <td>{{ $a->code ?? '—' }}</td>
                        <td class="text-end">{{ number_format($a->opening_balance) }}</td>
                        <td class="text-end fw-semibold">{{ number_format($a->balance()) }}</td>
                        <td>@if($a->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                        <td class="text-end">
                            @canModule('accountancy', 'update')
                            @include('partials.action-edit', ['href' => route('cash-accounts.edit', $a), 'iconOnly' => true])
                            @endcanModule
                            @canModule('accountancy', 'delete')
                            <form action="{{ route('cash-accounts.destroy', $a) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete cash account?', 'swalText' => 'Only possible if it has no transactions.'])
                            </form>
                            @endcanModule
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center text-muted py-5">No cash accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
