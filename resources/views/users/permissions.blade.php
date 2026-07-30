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
        @include('partials.help-tip', ['text' => 'Badges show what this user can access. Solid blue badges come from their role and cannot be edited here. Info-coloured badges are extra grants added specifically for this user — use the pencil icon to change exactly which actions are granted, or the trash icon to remove the grant entirely.', 'placement' => 'bottom'])
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
                                    <button type="button" class="btn btn-sm btn-cohas-edit ms-1" title="Change extra grant" aria-label="Change extra grant" data-bs-toggle="modal" data-bs-target="#editGrant{{ $extra->id }}">
                                        <i class="bi bi-pencil-square" aria-hidden="true"></i>
                                    </button>
                                    <form method="POST" action="{{ route('users.permissions.destroy', [$user, $extra]) }}" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        @include('partials.action-delete', ['title' => 'Remove extra grant', 'class' => 'ms-1', 'swalTitle' => 'Remove this extra grant?', 'swalText' => 'Remove the extra '.$moduleLabel.' grant for '.$user->name.'?'])
                                    </form>

                                    <div class="modal fade" id="editGrant{{ $extra->id }}" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <form method="POST" action="{{ route('users.permissions.store', $user) }}">
                                                    @csrf
                                                    <input type="hidden" name="module" value="{{ $moduleKey }}">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Change {{ $moduleLabel }} grant</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p class="text-muted small">Extra access for {{ $user->name }} beyond their role. Unchecking an action removes it; this does not affect access granted by their role.</p>
                                                        <div class="d-flex flex-wrap gap-3">
                                                            @foreach($actions as $act)
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox" name="actions[]" value="{{ $act }}" id="editAct_{{ $extra->id }}_{{ $act }}" {{ in_array($act, $extraActions, true) ? 'checked' : '' }}>
                                                                    <label class="form-check-label" for="editAct_{{ $extra->id }}_{{ $act }}">{{ ucfirst($act) }}</label>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Save changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
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
                <select class="form-select @error('module') is-invalid @enderror" id="module" name="module" required @if(empty($availableModules)) disabled @endif>
                    <option value="">Select</option>
                    @foreach($availableModules as $moduleKey => $moduleLabel)
                        <option value="{{ $moduleKey }}" {{ (string) old('module') === (string) $moduleKey ? 'selected' : '' }}>{{ $moduleLabel }}</option>
                    @endforeach
                </select>
                @if(empty($availableModules))
                    <p class="form-text mb-0">Every module already has an extra grant for this user — use the edit icon above to change one.</p>
                @endif
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
