@extends('layouts.app')

@section('title', 'Programmes')

@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Programmes</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-journal-bookmark-fill me-2 opacity-90"></i>Programmes</h1>
    </div>
    @canModule('programmes', 'create')
    <a href="{{ route('programmes.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-plus-lg me-1"></i> Add Programme</a>
    @endcanModule
</div>

@php
    $bulkDelete = [
        'bulkModule' => 'programmes',
        'bulkAction' => route('programmes.bulk-destroy'),
        'bulkFormId' => 'bulkDeleteProgrammes',
        'bulkTableId' => 'programmesTable',
        'bulkItemCount' => $programmes->count(),
    ];
@endphp
<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span><i class="bi bi-list-ul me-2"></i>Programmes</span>
        @include('partials.bulk-delete.toolbar', $bulkDelete)
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="programmesTable">
                @php
                    $showProgrammeActions = auth()->user()->canModule('programmes', 'update') || auth()->user()->canModule('programmes', 'delete');
                @endphp
                <thead>
                    <tr>
                        @include('partials.bulk-delete.th', $bulkDelete)
                        <th>Code</th>
                        <th>Name</th>
                        <th>Level</th>
                        <th>Duration</th>
                        <th>Status</th>
                        @if($showProgrammeActions)
                        <th class="text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($programmes as $p)
                    <tr>
                        @include('partials.bulk-delete.td', array_merge($bulkDelete, ['bulkRowId' => $p->id]))
                        <td><strong>{{ $p->code }}</strong></td>
                        <td>{{ $p->name }}</td>
                        <td>{{ $p->level }}</td>
                        <td>{{ $p->duration_years }} year(s)</td>
                        <td>
                            @if($p->is_active)
                                <span class="badge bg-success">Active</span>
                            @else
                                <span class="badge bg-secondary">Inactive</span>
                            @endif
                        </td>
                        @if($showProgrammeActions)
                        <td class="text-end">
                            @canModule('programmes', 'update')
                            @include('partials.action-edit', ['href' => route('programmes.edit', $p), 'class' => 'me-1', 'iconOnly' => true])
                            @endcanModule
                            @canModule('programmes', 'delete')
                            <form action="{{ route('programmes.destroy', $p) }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                @include('partials.action-delete', ['swalTitle' => 'Delete programme?', 'swalText' => 'This cannot be undone.'])
                            </form>
                            @endcanModule
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $showProgrammeActions ? 7 : 6 }}" class="text-center text-muted py-5">
                            No programmes yet.
                            @canModule('programmes', 'create')
                            <a href="{{ route('programmes.create') }}">Add one</a>
                            @endcanModule
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($programmes->hasPages())
    <div class="card-footer bg-light border-0 py-2">{{ $programmes->links() }}</div>
    @endif
</div>
@include('partials.bulk-delete.scripts')
@endsection
