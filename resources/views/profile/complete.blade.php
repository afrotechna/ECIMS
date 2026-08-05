@extends('layouts.app')

@section('title', 'Complete your profile')

@section('content')
<div class="page-header mb-4">
    <h1 class="page-title mb-1"><i class="bi bi-person-lines-fill me-2 text-primary"></i>Complete your profile</h1>
</div>
<div class="card card-modern" style="max-width: 720px;">
    <div class="card-body">
        @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
        @if($user->isStudent() && $student)
            <form action="{{ route('profile.complete.store') }}" method="POST">
                @csrf

                <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-person me-1"></i> Personal details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="first_name" class="form-label">First name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('first_name') is-invalid @enderror" id="first_name" name="first_name" value="{{ old('first_name', $student->first_name) }}" required>
                        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="middle_name" class="form-label">Middle name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $student->middle_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="last_name" class="form-label">Last name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('last_name') is-invalid @enderror" id="last_name" name="last_name" value="{{ old('last_name', $student->last_name) }}" required>
                        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Your phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $student->phone) }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="date_of_birth" class="form-label">Date of birth <span class="text-danger">*</span></label>
                        <input type="date" class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" value="{{ old('date_of_birth', $student->date_of_birth?->format('Y-m-d')) }}" required>
                        @error('date_of_birth')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="gender" class="form-label">Gender <span class="text-danger">*</span></label>
                        <select class="form-select @error('gender') is-invalid @enderror" id="gender" name="gender" required>
                            <option value="">— Select —</option>
                            <option value="M" {{ old('gender', $student->gender) === 'M' ? 'selected' : '' }}>Male</option>
                            <option value="F" {{ old('gender', $student->gender) === 'F' ? 'selected' : '' }}>Female</option>
                        </select>
                        @error('gender')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Programme</label>
                        <input type="text" class="form-control bg-light" value="{{ $student->programme->name ?? '—' }}" readonly>
                        <div class="form-text">Set by the college; contact admissions if incorrect.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">NACTVET registration no.</label>
                        <input type="text" class="form-control bg-light font-monospace" value="{{ $student->registrationNumberDisplay() }}" readonly>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-people me-1"></i> Parent or guardian</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="guardian_name" class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('guardian_name') is-invalid @enderror" id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $student->guardian_name) }}" required>
                        @error('guardian_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="guardian_phone" class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('guardian_phone') is-invalid @enderror" id="guardian_phone" name="guardian_phone" value="{{ old('guardian_phone', $student->guardian_phone) }}" required>
                        @error('guardian_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="guardian_relationship_choice" class="form-label">Relationship <span class="text-danger">*</span></label>
                        @include('students.partials.guardian-relationship-field', ['currentValue' => old('guardian_relationship', $student->guardian_relationship), 'required' => true])
                    </div>
                </div>

                <div class="alert alert-light border mt-4 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i>
                    Semester registration and fee recording are done by <strong>admissions / accounts staff</strong> after your payment is confirmed. You can view status under <strong>My registrations</strong>.
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-modern">Save and continue to dashboard</button>
                </div>
            </form>
        @elseif($user->isStudent())
            <div class="alert alert-warning">
                Your login is not linked to a student record. Please contact the admissions office.
                @if($user->nactvet_reg_no)
                <br><span class="small">Login ID on file: <code>{{ $user->nactvet_reg_no }}</code> — admissions must match this to your NACTVET number on the register.</span>
                @endif
            </div>
        @else
            <form action="{{ route('profile.complete.store') }}" method="POST">
                @csrf
                <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-person me-1"></i> Personal details</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="surname" class="form-label">Surname</label>
                        <input type="text" class="form-control" id="surname" name="surname" value="{{ old('surname', $user->surname) }}">
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $user->phone) }}" required>
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-mortarboard me-1"></i> Qualification &amp; registration</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="qualification" class="form-label">Qualification / profession</label>
                        <select class="form-select @error('qualification') is-invalid @enderror" id="qualification" name="qualification">
                            <option value="">— Select —</option>
                            @foreach(\App\Models\User::QUALIFICATIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('qualification', $user->qualification) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('qualification')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="education_level" class="form-label">Level of education</label>
                        <select class="form-select @error('education_level') is-invalid @enderror" id="education_level" name="education_level">
                            <option value="">— Select —</option>
                            @foreach(\App\Models\User::EDUCATION_LEVELS as $value => $label)
                                <option value="{{ $value }}" {{ old('education_level', $user->education_level) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('education_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="license_number" class="form-label">Registration / license number <span class="text-muted small">(if you have one)</span></label>
                        <input type="text" class="form-control @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number', $user->license_number) }}">
                        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="license_board" class="form-label">Issuing board / council</label>
                        <select class="form-select @error('license_board') is-invalid @enderror" id="license_board" name="license_board">
                            <option value="">— Select —</option>
                            @foreach(\App\Models\User::LICENSE_BOARDS as $value => $label)
                                <option value="{{ $value }}" {{ old('license_board', $user->license_board) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('license_board')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="employment_type" class="form-label">Hali ya ajira / employment status</label>
                        <select class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type">
                            <option value="">— Select —</option>
                            @foreach(\App\Models\User::EMPLOYMENT_TYPES as $value => $label)
                                <option value="{{ $value }}" {{ old('employment_type', $user->employment_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @if($user->isTutorStaff())
                    @php $selectedProgrammeIds = old('programme_ids', $user->programmes->pluck('id')->all()); @endphp
                    <hr class="my-4">
                    <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-diagram-3 me-1"></i> Department(s)</h6>
                    <p class="small text-muted mb-2">Choose the department(s) you teach in (up to 3).</p>
                    <div class="row g-2 @error('programme_ids') is-invalid @enderror">
                        @foreach($programmes as $programme)
                            <div class="col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="programme_ids[]" value="{{ $programme->id }}" id="programme_{{ $programme->id }}" {{ in_array($programme->id, $selectedProgrammeIds) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="programme_{{ $programme->id }}">{{ $programme->code }} — {{ $programme->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('programme_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    @error('programme_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                @endif

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-modern">Save and continue</button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection
