@extends('layouts.app')
@section('title', 'User permissions')
@section('content')
<nav class="student-breadcrumb">
    <a href="{{ route('dashboard') }}">Dashboard</a>
    <span class="mx-2">/</span>
    <a href="{{ route('users.role-permissions') }}">Role permissions</a>
    <span class="mx-2">/</span>
    <span>{{ $user->name }}</span>
</nav>

<div class="page-header-landing d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h1 class="page-title-landing"><i class="bi bi-person-badge me-2 opacity-90"></i>{{ $user->name }}</h1>
        <p class="page-subtitle-landing mb-0">{{ \App\Models\User::roleLabel($user->role) }} @if($user->email)· {{ $user->email }}@endif</p>
    </div>
    <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-light btn-sm text-white border">Edit user</a>
</div>

<div class="card card-landing mb-3">
    <div class="card-header-landing">
        <i class="bi bi-shield-check me-2"></i>Granted modules
        @include('partials.help-tip', ['text' => 'Badges show what this user can access. Solid badges come from their role. Outlined badges with a remove icon are extra grants added specifically for this user.', 'placement' => 'bottom'])
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col" class="ps-4">Module</th>
                        <th scope="col">Access</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($modules as $moduleKey => $moduleLabel)
                        @php
                            $roleActions = $roleGrants[$moduleKey] ?? ($roleGrants['*'] ?? []);
                            $extra = $extraGrants[$moduleKey] ?? null;
                            $extraActions = $extra->actions ?? [];
                            $hasAny = $roleActions !== [] || $extraActions !== [];
                        @endphp
                        <tr>
                            <td class="ps-4">{{ $moduleLabel }}</td>
                            <td>
                                @if(! $hasAny)
                                    <span class="text-muted small">—</span>
                                @else
                                    @foreach($actions as $act)
                                        @if(in_array('*', $roleActions, true) || in_array($act, $roleActions, true))
                                            <span class="badge bg-primary me-1" title="From role">{{ ucfirst($act) }}</span>
                                        @elseif(in_array('*', $extraActions, true) || in_array($act, $extraActions, true))
                                            <span class="badge bg-info text-dark me-1" title="Extra grant">{{ ucfirst($act) }}</span>
                                        @endif
                                    @endforeach
                                @endif
                                @if($extra)
                                    <form method="POST" action="{{ route('users.permissions.destroy', [$user, $extra]) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        @include('partials.action-delete', ['title' => 'Remove extra grant', 'class' => 'ms-1', 'swalTitle' => 'Remove this extra grant?', 'swalText' => 'Remove the extra '.$moduleLabel.' grant for '.$user->name.'?'])
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card card-landing" style="max-width: 560px;">
    <div class="card-header-landing"><i class="bi bi-plus-lg me-2"></i>Grant extra permission</div>
    <div class="card-body">
        <form method="POST" action="{{ route('users.permissions.store', $user) }}">
            @csrf
            <div class="mb-3">
                <label for="module" class="form-label">Module <span class="text-danger">*</span></label>
                <select class="form-select @error('module') is-invalid @enderror" id="module" name="module" required>
                    <option value="">Select</option>
                    @foreach($modules as $moduleKey => $moduleLabel)
                        <option value="{{ $moduleKey }}" {{ (string) old('module') === (string) $moduleKey ? 'selected' : '' }}>{{ $moduleLabel }}</option>
                    @endforeach
                </select>
                @error('module')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="mb-3">
                <label class="form-label">Actions <span class="text-danger">*</span></label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($actions as $act)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="actions[]" value="{{ $act }}" id="act_{{ $act }}" {{ in_array($act, old('actions', []), true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="act_{{ $act }}">{{ ucfirst($act) }}</label>
                        </div>
                    @endforeach
                </div>
                @error('actions')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary">Grant</button>
        </form>
    </div>
</div>
@endsection
