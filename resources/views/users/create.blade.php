@extends('layouts.app')
@section('title', 'Add User')
@section('content')
@php
    $defaultRole = request('role') === 'staff' ? 'staff' : 'student';
@endphp
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('users.index') }}">Users</a>
    <span class="mx-2">/</span>
    <span>Add</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3">
    <div>
        <h1 class="page-title-landing">
            <i class="bi bi-person-plus me-2 opacity-90"></i>Add User
            @include('partials.help-tip', ['text' => 'Student: login = NACTVET no., initial password = one surname (lowercase), changed on first sign-in. Staff: Staff ID is auto-generated, initial password = surname (lowercase).', 'placement' => 'bottom'])
        </h1>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-outline-light btn-sm text-dark border">Back to Users</a>
</div>

<div class="card card-landing">
    <div class="card-header-landing"><i class="bi bi-pencil-square me-2"></i>User details</div>
    <div class="card-body">
        <form action="{{ route('users.store') }}" method="POST" id="userForm">
            @csrf
            <div class="mb-3">
                <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                <select class="form-select" id="role" name="role" required>
                    <option value="student" {{ old('role', $defaultRole) === 'student' ? 'selected' : '' }}>{{ \App\Models\User::POSITIONS['student'] }}</option>
                    @foreach(\App\Models\User::staffRoleOptions() as $value => $label)
                        <option value="{{ $value }}" {{ old('role', $defaultRole) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div id="studentFields" style="display:{{ old('role', $defaultRole) === 'student' ? 'block' : 'none' }};">
                <div class="mb-3">
                    <label for="student_id" class="form-label">Student from register <span class="text-danger">*</span></label>
                    <select class="form-select" id="student_id" name="student_id">
                        <option value="">— Select ({{ $studentsWithoutUser->count() }} without login) —</option>
                        @foreach($studentsWithoutUser as $s)
                            <option
                                value="{{ $s->id }}"
                                data-login="{{ e($s->nactvet_reg_no) }}"
                                data-surname="{{ e($s->singleSurname()) }}"
                                {{ old('student_id') == $s->id ? 'selected' : '' }}
                            >{{ $s->programme->code ?? '—' }} · L{{ $s->nta_level ?? '?' }} — {{ $s->full_name }} — {{ $s->nactvet_reg_no }}</option>
                        @endforeach
                    </select>
                    @error('student_id')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    <div id="studentPasswordPreview" class="alert alert-light border small py-3 mt-2 mb-0 d-none" role="status">
                        <div class="d-flex flex-wrap gap-3">
                            <span>Login: <code id="studentLoginPreview">—</code></span>
                            <span>Surname: <strong id="studentSurnamePreview">—</strong></span>
                            <span>Password: <code id="studentPasswordPreviewValue">—</code></span>
                        </div>
                    </div>
                    @if($studentsWithoutUser->isEmpty())
                        <p class="text-muted small mb-0 mt-2">All registered students already have logins.</p>
                    @endif
                </div>
                <div class="d-flex flex-wrap gap-2 mb-0" id="studentActions">
                    <button type="submit" name="action" value="generate_password" class="btn btn-primary btn-sm">
                        <i class="bi bi-key me-1"></i> Generate password &amp; create login
                    </button>
                    <button type="submit" name="action" value="create_login" class="btn btn-outline-secondary btn-sm">
                        Create login only
                    </button>
                </div>
            </div>
            <div id="staffFields" style="display:{{ old('role', $defaultRole) !== 'student' ? 'block' : 'none' }};">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">First name(s) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Anna Donald">
                        @error('name')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="surname" class="form-label">Surname (second name) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="surname" name="surname" value="{{ old('surname') }}" placeholder="Used as initial password">
                        @error('surname')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}">
                        @error('email')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
            <hr class="my-4" id="formActionsHr">
            <div class="d-flex gap-2" id="formActionsDefault">
                <button type="submit" class="btn btn-primary" id="staffSubmitBtn" style="display:none;"><i class="bi bi-check-lg me-1"></i> Create staff user</button>
                <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.getElementById('role').addEventListener('change', function() {
    var isStudent = this.value === 'student';
    document.getElementById('studentFields').style.display = isStudent ? 'block' : 'none';
    document.getElementById('staffFields').style.display = isStudent ? 'none' : 'block';
    document.getElementById('studentActions').style.display = isStudent ? 'flex' : 'none';
    document.getElementById('staffSubmitBtn').style.display = isStudent ? 'none' : 'inline-block';
    document.getElementById('student_id').required = isStudent;
    document.getElementById('name').required = !isStudent;
    document.getElementById('surname').required = !isStudent;
    document.getElementById('email').required = !isStudent;
});
function updateStudentPasswordPreview() {
    var sel = document.getElementById('student_id');
    var box = document.getElementById('studentPasswordPreview');
    if (!sel || !box) return;
    var opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) {
        box.classList.add('d-none');
        return;
    }
    var surname = (opt.getAttribute('data-surname') || '').trim();
    document.getElementById('studentLoginPreview').textContent = opt.getAttribute('data-login') || '—';
    document.getElementById('studentSurnamePreview').textContent = surname || '—';
    document.getElementById('studentPasswordPreviewValue').textContent = (surname || 'student').toLowerCase();
    box.classList.remove('d-none');
}
document.getElementById('student_id')?.addEventListener('change', updateStudentPasswordPreview);
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('role').dispatchEvent(new Event('change'));
    updateStudentPasswordPreview();
});
</script>
@endpush
@endsection
