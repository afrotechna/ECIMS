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
            @php
                $isTanzanian = old('nationality', $user->nationality) === 'Tanzanian';
                $isPermanent = old('employment_type', $user->employment_type) === 'permanent';
            @endphp
            <form action="{{ route('profile.complete.store') }}" method="POST">
                @csrf
                <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-person me-1"></i> Personal details</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="name" class="form-label">First name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label for="middle_name" class="form-label">Middle name</label>
                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}">
                    </div>
                    <div class="col-md-4">
                        <label for="surname" class="form-label">Surname <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('surname') is-invalid @enderror" id="surname" name="surname" value="{{ old('surname', $user->surname) }}" required>
                        @error('surname')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                    <div class="col-md-6">
                        <label for="sex_choice" class="form-label">Sex <span class="text-danger">*</span></label>
                        <select class="form-select @error('sex') is-invalid @enderror" id="sex_choice" name="sex" required>
                            <option value="">— Select —</option>
                            @foreach(\App\Models\User::SEX_OPTIONS as $value => $label)
                                <option value="{{ $value }}" {{ old('sex', $user->sex) === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('sex')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="nationality_choice" class="form-label">Nationality <span class="text-danger">*</span></label>
                        @include('partials.select-with-other', [
                            'idPrefix' => 'nationality',
                            'name' => 'nationality',
                            'options' => \App\Models\User::NATIONALITIES,
                            'currentValue' => old('nationality', $user->nationality),
                            'required' => true,
                            'otherPlaceholder' => 'Specify nationality',
                        ])
                    </div>
                </div>

                <div id="tanzania_address_section" class="{{ $isTanzanian ? '' : 'd-none' }}">
                    <hr class="my-4">
                    <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-geo-alt me-1"></i> Address</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="region" class="form-label">Region</label>
                            <select class="form-select @error('region') is-invalid @enderror" id="region" name="region">
                                <option value="">— Select region —</option>
                                @foreach(array_keys($tanzaniaLocations) as $regionName)
                                    <option value="{{ $regionName }}" {{ old('region', $user->region) === $regionName ? 'selected' : '' }}>{{ $regionName }}</option>
                                @endforeach
                            </select>
                            @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="district" class="form-label">District</label>
                            <select class="form-select @error('district') is-invalid @enderror" id="district" name="district">
                                <option value="">— Select region first —</option>
                            </select>
                            @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="ward" class="form-label">Ward</label>
                            <input type="text" class="form-control @error('ward') is-invalid @enderror" id="ward" name="ward" value="{{ old('ward', $user->ward) }}">
                            @error('ward')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label for="street" class="form-label">Street</label>
                            <input type="text" class="form-control @error('street') is-invalid @enderror" id="street" name="street" value="{{ old('street', $user->street) }}">
                            @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                @push('scripts')
                <script>
                (function () {
                    var tanzaniaLocations = @json($tanzaniaLocations);
                    var currentDistrict = @json(old('district', $user->district));
                    var nationalityHidden = document.getElementById('nationality_hidden');
                    var addressSection = document.getElementById('tanzania_address_section');
                    var regionSelect = document.getElementById('region');
                    var districtSelect = document.getElementById('district');

                    function populateDistricts(regionName, selectedDistrict) {
                        if (!districtSelect) return;
                        var districts = tanzaniaLocations[regionName] || [];
                        districtSelect.innerHTML = '';
                        var placeholder = document.createElement('option');
                        placeholder.value = '';
                        placeholder.textContent = districts.length ? '— Select district —' : '— Select region first —';
                        districtSelect.appendChild(placeholder);
                        districts.forEach(function (districtName) {
                            var option = document.createElement('option');
                            option.value = districtName;
                            option.textContent = districtName;
                            if (districtName === selectedDistrict) option.selected = true;
                            districtSelect.appendChild(option);
                        });
                    }

                    if (regionSelect) {
                        populateDistricts(regionSelect.value, currentDistrict);
                        regionSelect.addEventListener('change', function () {
                            populateDistricts(regionSelect.value, null);
                        });
                    }

                    if (nationalityHidden && addressSection) {
                        nationalityHidden.addEventListener('change', function () {
                            var isTanzanian = nationalityHidden.value === 'Tanzanian';
                            addressSection.classList.toggle('d-none', !isTanzanian);
                            if (!isTanzanian) {
                                addressSection.querySelectorAll('input, select').forEach(function (field) {
                                    field.value = '';
                                });
                            }
                        });
                    }
                })();
                </script>
                @endpush

                <hr class="my-4">

                <h6 class="text-uppercase text-muted small mb-3"><i class="bi bi-mortarboard me-1"></i> Qualification &amp; registration</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="qualification_choice" class="form-label">Qualification / profession</label>
                        @include('partials.select-with-other', [
                            'idPrefix' => 'qualification',
                            'name' => 'qualification',
                            'options' => \App\Models\User::QUALIFICATIONS,
                            'currentValue' => old('qualification', $user->qualification),
                            'otherPlaceholder' => 'Specify qualification',
                        ])
                    </div>
                    <div class="col-md-6">
                        <label for="education_level_choice" class="form-label">Level of education</label>
                        @include('partials.select-with-other', [
                            'idPrefix' => 'education_level',
                            'name' => 'education_level',
                            'options' => \App\Models\User::EDUCATION_LEVELS,
                            'currentValue' => old('education_level', $user->education_level),
                            'otherPlaceholder' => 'Specify education level',
                        ])
                    </div>
                    <div class="col-md-6">
                        <label for="license_number" class="form-label">Registration / license number <span class="text-muted small">(if you have one)</span></label>
                        <input type="text" class="form-control @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number', $user->license_number) }}">
                        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="license_board_choice" class="form-label">Issuing board / council</label>
                        @include('partials.select-with-other', [
                            'idPrefix' => 'license_board',
                            'name' => 'license_board',
                            'options' => \App\Models\User::LICENSE_BOARDS,
                            'currentValue' => old('license_board', $user->license_board),
                            'otherPlaceholder' => 'Specify issuing board',
                        ])
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
                    <div class="col-md-6 {{ $isPermanent ? '' : 'd-none' }}" id="check_number_wrap">
                        <label for="check_number" class="form-label">Check number <span class="text-muted small">(permanent staff)</span></label>
                        <input type="text" class="form-control @error('check_number') is-invalid @enderror" id="check_number" name="check_number" value="{{ old('check_number', $user->check_number) }}">
                        @error('check_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                @push('scripts')
                <script>
                (function () {
                    var employmentType = document.getElementById('employment_type');
                    var checkNumberWrap = document.getElementById('check_number_wrap');
                    if (!employmentType || !checkNumberWrap) return;

                    var checkNumberInput = document.getElementById('check_number');

                    function sync(isUserAction) {
                        var isPermanent = employmentType.value === 'permanent';
                        checkNumberWrap.classList.toggle('d-none', !isPermanent);
                        if (isUserAction && !isPermanent && checkNumberInput) {
                            checkNumberInput.value = '';
                        }
                    }

                    employmentType.addEventListener('change', function () { sync(true); });
                    sync(false);
                })();
                </script>
                @endpush

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
