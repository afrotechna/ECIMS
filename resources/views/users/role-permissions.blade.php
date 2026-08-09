@extends('layouts.app')
@section('title', 'Role permissions')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('users.index', ['type' => 'staff']) }}">Users</a>
    <span class="mx-2">/</span>
    <span>Role permissions</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-shield-lock me-2 opacity-90"></i>Role permissions</h1>
        <p class="page-subtitle-landing mb-0">Select a staff member to view or grant module access.</p>
    </div>
    <a href="{{ route('users.role-matrix') }}" class="btn btn-outline-light btn-sm text-white border">Role defaults</a>
</div>

<div class="card card-landing mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('users.role-permissions') }}" class="row g-2 align-items-end">
            <div class="col-md-6">
                <label class="form-label">Search</label>
                <input type="text" class="form-control" name="search" value="{{ request('search') }}" placeholder="Name, email, or staff ID">
            </div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>Search</button></div>
        </form>
    </div>
</div>

<div class="card card-landing">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4">Name</th>
                        <th scope="col">Position</th>
                        <th scope="col">Email</th>
                        <th scope="col" class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="staff-user-avatar" aria-hidden="true">{{ $u->initials }}</div>
                                <div class="min-w-0">
                                    <div class="staff-user-surname fw-semibold text-dark">{{ $u->staffSurname() ?: $u->name }}</div>
                                    <div class="staff-user-given text-muted">{{ $u->staffGivenNames() }}</div>
                                </div>
                            </div>
                        </td>
                        <td><span class="staff-role-pill">{{ \App\Models\User::roleLabel($u->role) }}</span></td>
                        <td class="small text-secondary text-truncate" style="max-width: 12rem;">{{ $u->email ?: '—' }}</td>
                        <td class="text-end text-nowrap pe-4">
                            <a href="{{ route('users.permissions', $u) }}" class="btn btn-sm btn-cohas-edit" title="View permissions" aria-label="View permissions"><i class="bi bi-shield-lock"></i></a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted py-5">No staff users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-light border-0 py-3 d-flex justify-content-center">
        {{ $users->links() }}
    </div>
    @endif
</div>
@endsection
