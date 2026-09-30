@extends('layouts.app')
@section('title', 'Departments')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="bi bi-house me-1"></i>Dashboard</a>
    <span class="mx-2">/</span>
    <span>Accountancy</span>
    <span class="mx-2">/</span>
    <span>Departments</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-diagram-3 me-2 opacity-90"></i>Departments</h1>
        <p class="page-subtitle-landing mb-0">Cost centres used by budget lines and creditor records.</p>
    </div>
    @canModule('accountancy', 'create')
    <a href="{{ route('departments.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Department</a>
    @endcanModule
</div>

@php
    $bulkDelete = [
        'bulkModule' => 'accountancy',
        'bulkAction' => route('departments.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteDepartments',
        'bulkTableId' => 'departmentsTable',
        'bulkItemCount' => $departments->count(),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Departments</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="departmentsTable">
                <thead>
                    <tr>
                        @include('partials.bulk-delete.th', $bulkDelete)
                        <th>Name</th>
                        <th>Code</th>
                        <th class="text-end">Budget lines</th>
                        <th class="text-end">Creditors</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $d)
                    <tr>
                        @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $d->id]))
                        <td><strong>{{ $d->name }}</strong></td>
                        <td>{{ $d->code ?? '—' }}</td>
                        <td class="text-end">{{ $d->budget_lines_count }}</td>
                        <td class="text-end">{{ $d->creditors_count }}</td>
                        <td>@if($d->is_active)<span class="badge bg-success">Active</span>@else<span class="badge bg-secondary">Inactive</span>@endif</td>
                        <td class="text-end">
                            @canModule('accountancy', 'update')
                            @include('partials.action-edit', ['href' => route('departments.edit', $d), 'iconOnly' => true])
                            @endcanModule
                            @canModule('accountancy', 'delete')
                            <form action="{{ route('departments.destroy', $d) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete department?', 'swalText' => 'Only possible if it has no budget lines or creditors.'])
                            </form>
                            @endcanModule
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted py-5">No departments yet. <a href="{{ route('departments.create') }}">Add one</a>.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($departments->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $departments->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
