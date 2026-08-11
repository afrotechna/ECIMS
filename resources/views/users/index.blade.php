@extends('layouts.app')
@section('title', 'Users')
@section('content')
@php
    $isAdmin = auth()->user()->isAdmin();
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <span>Users</span>
</nav>


@if(session('issued_temp_password'))
<div class="alert alert-warning border shadow-sm mb-3" data-swal-notice>
    <strong>Temporary password</strong> for {{ session('issued_user_name') }} — copy now (shown once):<br>
    Login: <code>{{ session('issued_login') }}</code><br>
    Password: <code class="user-select-all">{{ session('issued_temp_password') }}</code>
    @if(session('issued_password_emailed'))
    <br><span class="text-success">Also sent by email.</span>
    @endif
    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-primary" onclick="cohasCopyText('{{ addslashes(session('issued_login').' / '.session('issued_temp_password')) }}', this)">
            <i class="bi bi-clipboard me-1"></i>Copy login &amp; password
        </button>
    </div>
</div>
@endif

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-person-badge me-2 opacity-90"></i>Users</h1>
        <p class="page-subtitle-landing mb-0">
            @if($type === 'student')
                Portal logins for registered students. Password = one surname (lowercase).
            @else
                Staff and administrator accounts.
            @endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('users.create') }}" class="btn btn-light btn-sm text-dark"><i class="bi bi-person-plus me-1"></i> Add user</a>
        <a href="{{ route('users.import') }}" class="btn btn-outline-light btn-sm text-dark border"><i class="bi bi-upload me-1"></i> Bulk CSV</a>
    </div>
</div>

@php
    $userTypeItems = [
        [
            'href' => route('users.index', ['type' => 'student']),
            'active' => $type === 'student',
            'icon' => 'bi-mortarboard-fill',
            'accent' => 'cohas-segment-nav__item--students',
            'title' => 'Student logins',
            'subtitle' => number_format($studentCount).' account'.($studentCount === 1 ? '' : 's'),
        ],
        [
            'href' => route('users.index', ['type' => 'staff']),
            'active' => $type === 'staff',
            'icon' => 'bi-briefcase-fill',
            'accent' => 'cohas-segment-nav__item--staff',
            'title' => 'Staff logins',
            'subtitle' => number_format($staffCount).' account'.($staffCount === 1 ? '' : 's'),
        ],
    ];
@endphp

@include('partials.segment-nav', [
    'ariaLabel' => 'User type',
    'eyebrow' => 'Account type',
    'hint' => 'Students or staff',
    'columns' => 2,
    'items' => $userTypeItems,
])

@if($type === 'student' && ($studentsAwaitingLogin ?? 0) > 0)
<div class="card card-landing mb-3 border-info">
    <div class="card-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
        <span><i class="bi bi-person-plus me-2"></i> Students without portal login ({{ $studentsAwaitingLogin }})</span>
        <form action="{{ route('users.create-all-student-logins') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="send_email" value="0">
            <button type="button" class="btn btn-primary btn-sm" data-swal-confirm data-swal-title="Create logins for all students?" data-swal-text="Creates logins for ALL students without an account (all programmes). Password = surname (lowercase). CSV will download."><i class="bi bi-people me-1"></i> Create all + CSV</button>
        </form>
    </div>
    <div class="card-body py-2">
        <form method="GET" action="{{ route('users.index') }}" class="row g-2 align-items-end mb-3">
            <input type="hidden" name="type" value="student">
            <div class="col-auto">
                <label class="form-label small mb-0">Class group</label>
                <select name="class_group" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($classGroupOptions ?? [] as $cg)
                        <option value="{{ $cg }}" @selected(($loginFilters['class_group'] ?? '') === $cg)>{{ $cg }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Intake year</label>
                <select name="intake_year" class="form-select form-select-sm">
                    <option value="">All</option>
                    @foreach($intakeYearOptions ?? [] as $iy)
                        <option value="{{ $iy }}" @selected(($loginFilters['intake_year'] ?? '') === (string) $iy)>{{ $iy }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                @if(!empty($loginFilters['class_group']) || !empty($loginFilters['intake_year']))
                    <a href="{{ route('users.index', ['type' => 'student']) }}" class="btn btn-sm btn-link">Clear</a>
                @endif
            </div>
        </form>
        <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>NACTVET</th>
                        <th>Class</th>
                        @if($isAdmin)
                        <th class="text-end">Action</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($studentsAwaitingList as $s)
                    <tr>
                        <td class="fw-medium">{{ $s->full_name }}</td>
                        <td><code class="small">{{ $s->nactvet_reg_no }}</code></td>
                        <td class="small text-muted">{{ $s->class_group ?: '—' }}</td>
                        @if($isAdmin)
                        <td class="text-end">
                            <form action="{{ route('users.create-student-login', $s) }}" method="POST" class="d-inline">
                                @csrf
                                @if(!empty($loginFilters['class_group']))
                                    <input type="hidden" name="class_group" value="{{ $loginFilters['class_group'] }}">
                                @endif
                                @if(!empty($loginFilters['intake_year']))
                                    <input type="hidden" name="intake_year" value="{{ $loginFilters['intake_year'] }}">
                                @endif
                                <button type="button" class="btn btn-sm btn-primary" data-swal-confirm data-swal-title="Create login for {{ $s->full_name }}?" data-swal-text="Password will be surname (lowercase)."><i class="bi bi-key me-1"></i> Create login</button>
                            </form>
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($studentsAwaitingLogin > ($studentsAwaitingList->count()))
            <p class="small text-muted mb-0 mt-2">Showing first {{ $studentsAwaitingList->count() }} of {{ $studentsAwaitingLogin }}.</p>
        @endif
    </div>
</div>
@endif

<div class="card card-landing">
    <div class="card-header-landing d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
        <span>
            <i class="bi bi-list-ul me-2"></i>
            @if($type === 'student') Student accounts @else Staff accounts @endif
        </span>
        @if($users->total() > 0)
            <span class="badge bg-light text-dark fw-normal border">{{ number_format($users->total()) }} total</span>
        @endif
    </div>

    @if($type === 'student' && $isAdmin)
    <div class="users-actions-bar">
        <form action="{{ route('users.create-all-student-logins') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="send_email" value="0">
            <button type="button" class="btn btn-primary btn-sm" data-swal-confirm data-swal-title="Create logins for all students?" data-swal-text="Creates logins for all students without an account. Password = one surname (lowercase). CSV will download."><i class="bi bi-people me-1"></i> Create logins + CSV</button>
        </form>
        <form action="{{ route('users.issue-all-student-passwords') }}" method="POST" class="d-inline">
            @csrf
            <input type="hidden" name="send_email" value="0">
            <button type="button" class="btn btn-outline-warning btn-sm" data-swal-confirm data-swal-title="Reset all student passwords?" data-swal-text="Resets all student passwords to surname (lowercase). CSV will download."><i class="bi bi-key me-1"></i> Re-issue all passwords</button>
        </form>
        <a href="{{ route('users.create') }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-plus me-1"></i> One student</a>
    </div>
    @endif

    <div class="card-body p-0">
        <div class="table-responsive">
            @if($type === 'staff')
            <table class="table table-hover align-middle mb-0 table-staff-modern">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4">Name</th>
                        <th scope="col">Login ID</th>
                        <th scope="col">Position</th>
                        <th scope="col">Email</th>
                        @if($isAdmin)
                        <th scope="col" class="text-end pe-4">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="staff-user-avatar" aria-hidden="true">{{ $u->initials }}</div>
                                <div class="min-w-0">
                                    <div class="staff-user-surname fw-semibold text-dark">{{ $u->staffSurname() ?: '—' }}</div>
                                    <div class="staff-user-given text-muted">{{ $u->staffGivenNames() ?: '—' }}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="staff-login-pill font-monospace">{{ $u->loginIdentifier() }}</span>
                        </td>
                        <td>
                            <span class="staff-role-pill">{{ \App\Models\User::roleLabel($u->role) }}</span>
                        </td>
                        <td class="small text-secondary text-truncate" style="max-width: 12rem;">{{ $u->email ?: '—' }}</td>
                        @if($isAdmin)
                        <td class="text-end text-nowrap pe-4">
                            <a href="{{ route('users.permissions', $u) }}" class="btn btn-sm btn-cohas-edit me-1" title="View permissions" aria-label="View permissions"><i class="bi bi-shield-lock"></i></a>
                            <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-cohas-edit" title="Edit staff account" aria-label="Edit"><i class="bi bi-pencil-square"></i></a>
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 5 : 4 }}" class="text-center py-5">
                            <div class="staff-empty-state mx-auto">
                                <i class="bi bi-briefcase display-6 text-muted opacity-50"></i>
                                <p class="mt-3 mb-0 fw-medium">No staff accounts yet</p>
                                <p class="small text-muted mb-3">Add leadership, tutors, and support staff.</p>
                                <a href="{{ route('users.create') }}?role=staff" class="btn btn-sm btn-primary"><i class="bi bi-person-plus me-1"></i> Add staff user</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @else
            <table class="table table-hover align-middle mb-0 table-users-landing">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Login</th>
                        <th scope="col">Email</th>
                        @if($isAdmin)
                        <th scope="col" class="text-end">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                    <tr>
                        <td class="fw-medium">{{ $u->name }}</td>
                        <td><code class="small">{{ $u->loginIdentifier() }}</code></td>
                        <td class="small text-muted">{{ $u->email }}</td>
                        @if($isAdmin)
                        <td class="text-end text-nowrap">
                            <form action="{{ route('users.issue-temporary-password', $u) }}" method="POST" class="d-inline">
                                @csrf
                                <input type="hidden" name="redirect" value="index">
                                <button type="button" class="btn btn-sm btn-warning" title="Generate password" aria-label="Generate password" data-swal-confirm data-swal-title="Generate password from surname?" data-swal-text="The old password will stop working."><i class="bi bi-key"></i></button>
                            </form>
                            @include('partials.action-edit', ['href' => route('users.edit', $u), 'iconOnly' => true])
                        </td>
                        @endif
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $isAdmin ? 4 : 3 }}" class="text-center text-muted py-5">
                            <p class="mb-2">No student portal accounts yet.</p>
                            <p class="small mb-2">If students are already on the register, use <strong>Create logins + CSV</strong> above or the list above this table.</p>
                            <a href="{{ route('users.create') }}" class="btn btn-sm btn-primary">Add one student login</a>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
            @endif
        </div>
    </div>
    @if($users->hasPages())
    <div class="card-footer bg-light border-0 py-3 d-flex justify-content-center">{{ $users->links() }}</div>
    @endif
</div>


@if($type === 'staff')
@push('styles')
<style>
.table-staff-modern thead th {
    font-size: .68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #64748b;
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%) !important;
    border-bottom: 2px solid #e2e8f0;
    padding-top: .85rem;
    padding-bottom: .85rem;
}
.table-staff-modern tbody tr {
    transition: background-color .12s ease;
}
.table-staff-modern tbody tr:hover {
    background-color: #f8fafc;
}
.staff-user-avatar {
    width: 2.5rem;
    height: 2.5rem;
    border-radius: .75rem;
    background: linear-gradient(135deg, #0d6efd 0%, #0a58ca 100%);
    color: #fff;
    font-size: .8rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 2px 6px rgba(13, 110, 253, .25);
}
.staff-user-surname { font-size: .95rem; line-height: 1.25; }
.staff-user-given { font-size: .8rem; line-height: 1.2; }
.staff-login-pill {
    display: inline-block;
    font-size: .8rem;
    padding: .25rem .55rem;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: .4rem;
    color: #334155;
}
.staff-role-pill {
    display: inline-block;
    font-size: .72rem;
    font-weight: 500;
    line-height: 1.3;
    max-width: 14rem;
    padding: .35rem .65rem;
    border-radius: 2rem;
    background: #eff6ff;
    color: #1e40af;
    border: 1px solid #bfdbfe;
}
.staff-empty-state { max-width: 20rem; }
</style>
@endpush
@endif
@endsection
