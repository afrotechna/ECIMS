@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('users.index') }}">Users</a>
    <span class="mx-2">/</span>
    <span>Edit</span>
</nav>

<div class="page-header-landing">
    <h1 class="page-title-landing"><i class="bi bi-pencil-square me-2 opacity-90"></i>Edit User</h1>
    <p class="page-subtitle-landing mb-0">Login: <code>{{ $user->loginIdentifier() }}</code></p>
</div>

@if(session('issued_temp_password'))
<div class="alert alert-warning border" data-swal-notice>
    <strong>New temporary password</strong> (copy now — it will not be shown again):<br>
    Login: <code>{{ session('issued_login') }}</code><br>
    Password: <code class="user-select-all">{{ session('issued_temp_password') }}</code>
    @if(session('issued_password_emailed'))
    <br><span class="text-success">Sent to {{ $user->email }}.</span>
    @endif
    <div class="mt-2">
        <button type="button" class="btn btn-sm btn-primary" onclick="cohasCopyText('{{ addslashes(session('issued_login').' / '.session('issued_temp_password')) }}', this)">
            <i class="bi bi-clipboard me-1"></i>Copy login &amp; password
        </button>
    </div>
</div>
@endif

<div class="card card-landing mb-3">
    <div class="card-header-landing d-flex align-items-center gap-2">
        <i class="bi bi-key me-2"></i>Temporary password
        @include('partials.help-tip', ['text' => $user->isStudent() ? 'Passwords are stored securely and cannot be retrieved. Generate password sets one surname only (last word, lowercase); the student must change it on first sign-in.' : 'Passwords are stored securely and cannot be retrieved. This issues a new random temporary password if the user lost theirs.', 'placement' => 'bottom'])
    </div>
    <div class="card-body">
        <form action="{{ route('users.issue-temporary-password', $user) }}" method="POST" class="row g-2 align-items-end">
            @csrf
            <div class="col-auto">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="send_email" value="1" id="send_email" {{ $user->hasDeliverableEmail() ? '' : 'disabled' }}>
                    <label class="form-check-label" for="send_email">
                        Email to {{ $user->email }}
                        @unless($user->hasDeliverableEmail())
                        <span class="text-warning">(placeholder address — use CSV or update email)</span>
                        @endunless
                    </label>
                </div>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-warning btn-sm" data-swal-confirm data-swal-title="{{ $user->isStudent() ? 'Generate password from surname?' : 'Issue a new temporary password?' }}" data-swal-text="{{ $user->isStudent() ? 'Password will be one word, lowercase, from the surname. The old password will stop working.' : 'The old password will stop working.' }}">
                    <i class="bi bi-key me-1"></i> {{ $user->isStudent() ? 'Generate password' : 'Issue temporary password' }}
                </button>
            </div>
        </form>
    </div>
</div>

@if($user->isStudent())
<div class="card card-landing mb-3">
    <div class="card-header-landing"><i class="bi bi-link-45deg me-2"></i>Student register link</div>
    <div class="card-body">
        @if($linkedStudent ?? null)
            <p class="mb-1 text-success"><i class="bi bi-check-circle me-1"></i> Linked to <strong>{{ $linkedStudent->full_name }}</strong> ({{ $linkedStudent->nactvet_reg_no }})</p>
            <p class="small text-muted mb-0">Programme: {{ $linkedStudent->programme->name ?? '—' }}</p>
        @else
            <div class="alert alert-warning mb-2">No matching student on the register. Set <strong>NACTVET registration no.</strong> below to exactly match the student record.</div>
        @endif
    </div>
</div>
@endif

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-person-badge me-2"></i>User details</div>
    <div class="card-body">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')
            @if($user->staff_id)
                <div class="mb-3">
                    <label class="form-label">Staff ID (login)</label>
                    <input type="text" class="form-control bg-light" value="{{ $user->staff_id }}" readonly>
                    <small class="text-muted">Tutor/Staff login with this ID. Cannot be changed.</small>
                </div>
                @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="name" class="form-label">{{ $user->isStudent() ? 'Name' : 'First name(s)' }}</label>
                    <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="col-md-6">
                    <label for="surname" class="form-label">{{ $user->isStudent() ? 'Surname' : 'Surname (second name)' }}</label>
                    <input type="text" class="form-control" id="surname" name="surname" value="{{ old('surname', $user->surname) }}">
                </div>
                <div class="col-md-6"><label for="email" class="form-label">Email</label><input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>@error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                @if($user->isStudent() || old('role', $user->role) === 'student')
                <div class="col-md-6">
                    <label for="nactvet_reg_no" class="form-label">NACTVET registration no. (login)</label>
                    <input type="text" class="form-control font-monospace @error('nactvet_reg_no') is-invalid @enderror" id="nactvet_reg_no" name="nactvet_reg_no" value="{{ old('nactvet_reg_no', $user->nactvet_reg_no) }}" required>
                    @error('nactvet_reg_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <small class="text-muted">Must match the student register exactly.</small>
                </div>
                @endif
                @if(!$user->staff_id)
                <div class="col-md-6"><label for="check_number" class="form-label">Check number</label><input type="text" class="form-control @error('check_number') is-invalid @enderror" id="check_number" name="check_number" value="{{ old('check_number', $user->check_number) }}" placeholder="Legacy staff login">@error('check_number')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                @endif
                <div class="col-md-6"><label for="password" class="form-label">New password (leave blank to keep)</label><input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">@error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror</div>
                <div class="col-md-6"><label for="password_confirmation" class="form-label">Confirm new password</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation"></div>
                <div class="col-md-6">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                        @foreach(\App\Models\User::allAssignableRoleOptions() as $value => $label)
                            <option value="{{ $value }}" {{ old('role', \App\Models\User::normalizeRoleSlug($user->role ?? 'student')) === $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <hr class="my-4">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
