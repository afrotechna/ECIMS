@extends('layouts.app')

@section('title', 'Complete your profile')

@section('content')
<div class="page-header mb-4">
    <h1 class="page-title mb-1"><i class="bi bi-person-lines-fill me-2 text-primary"></i>{{ ($readOnly ?? false) ? 'My profile' : 'Complete your profile' }}</h1>
</div>
<div class="card card-modern mx-auto" style="max-width: 960px;">
    <div class="card-body">
        @if(session('info'))<div class="alert alert-info">{{ session('info') }}</div>@endif
        @if(session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif
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
                $rawPhone = old('phone', $user->phone) ?? '';
                $phoneLocal = old('phone_local', preg_replace('/^(\+255|0)/', '', $rawPhone));
                $hasLicenseBoard = old('license_board', $user->license_board) !== null && old('license_board', $user->license_board) !== '';
            @endphp

            @if($readOnly)
            <div class="alert alert-secondary d-flex align-items-start gap-2">
                <i class="bi bi-lock-fill mt-1"></i>
                <div>
                    <strong>Profile editing is locked.</strong>
                    Your details are shown below for reference only. Contact the system administrator if something needs to be corrected.
                </div>
            </div>
            @endif

            <form action="{{ route('profile.complete.store') }}" method="POST">
                @csrf

                <div class="accordion" id="profileAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="profileHeadingPersonal">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#profileCollapsePersonal" aria-expanded="true" aria-controls="profileCollapsePersonal">
                                <i class="bi bi-person me-2"></i> Personal details
                            </button>
                        </h2>
                        <div id="profileCollapsePersonal" class="accordion-collapse collapse show" aria-labelledby="profileHeadingPersonal">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label for="name" class="form-label">First name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required @if($readOnly) disabled @endif>
                                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-4">
                                        <label for="middle_name" class="form-label">Middle name</label>
                                        <input type="text" class="form-control" id="middle_name" name="middle_name" value="{{ old('middle_name', $user->middle_name) }}" @if($readOnly) disabled @endif>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="surname" class="form-label">Surname <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('surname') is-invalid @enderror" id="surname" name="surname" value="{{ old('surname', $user->surname) }}" required @if($readOnly) disabled @endif>
                                        @error('surname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required @if($readOnly) disabled @endif>
                                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone_local" class="form-label">Phone <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text d-flex align-items-center gap-1">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 480" width="20" height="15" aria-hidden="true" style="flex: none;">
                                                    <defs>
                                                        <clipPath id="tz-flag-clip">
                                                            <path fill-opacity=".7" d="M10 0h160v120H10z"/>
                                                        </clipPath>
                                                    </defs>
                                                    <g fill-rule="evenodd" stroke-width="1pt" clip-path="url(#tz-flag-clip)" transform="matrix(4 0 0 4 -40 0)">
                                                        <path fill="#09f" d="M0 0h180v120H0z"/>
                                                        <path fill="#090" d="M0 0h180L0 120z"/>
                                                        <path fill="#000001" d="M0 120h40l140-95V0h-40L0 95z"/>
                                                        <path fill="#ff0" d="M0 91.5 137.2 0h13.5L0 100.5zM29.3 120 180 19.5v9L42.8 120z"/>
                                                    </g>
                                                </svg>
                                                +255
                                            </span>
                                            <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone_local" placeholder="7XXXXXXXX" maxlength="9" inputmode="numeric" value="{{ $phoneLocal }}" @if($readOnly) disabled @endif>
                                        </div>
                                        <input type="hidden" name="phone" id="phone_hidden" value="{{ old('phone', $user->phone) }}">
                                        @error('phone')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sex_choice" class="form-label">Sex <span class="text-danger">*</span></label>
                                        <select class="form-select @error('sex') is-invalid @enderror" id="sex_choice" name="sex" required @if($readOnly) disabled @endif>
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
                                            'disabled' => $readOnly,
                                            'otherPlaceholder' => 'Specify nationality',
                                        ])
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item {{ $isTanzanian ? '' : 'd-none' }}" id="tanzania_address_section">
                        <h2 class="accordion-header" id="profileHeadingAddress">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#profileCollapseAddress" aria-expanded="true" aria-controls="profileCollapseAddress">
                                <i class="bi bi-geo-alt me-2"></i> Address
                            </button>
                        </h2>
                        <div id="profileCollapseAddress" class="accordion-collapse collapse show" aria-labelledby="profileHeadingAddress">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="region" class="form-label">Region</label>
                                        <select class="form-select @error('region') is-invalid @enderror" id="region" name="region" @if($readOnly) disabled @endif>
                                            <option value="">— Select region —</option>
                                            @foreach(array_keys($tanzaniaLocations) as $regionName)
                                                <option value="{{ $regionName }}" {{ old('region', $user->region) === $regionName ? 'selected' : '' }}>{{ $regionName }}</option>
                                            @endforeach
                                        </select>
                                        @error('region')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="district" class="form-label">District</label>
                                        <select class="form-select @error('district') is-invalid @enderror" id="district" name="district" @if($readOnly) disabled @endif>
                                            <option value="">— Select region first —</option>
                                        </select>
                                        @error('district')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="ward" class="form-label">Ward</label>
                                        <select class="form-select @error('ward') is-invalid @enderror" id="ward" name="ward" @if($readOnly) disabled @endif>
                                            <option value="">— Select district first —</option>
                                        </select>
                                        @error('ward')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="street" class="form-label">Street</label>
                                        <input type="text" class="form-control @error('street') is-invalid @enderror" id="street" name="street" value="{{ old('street', $user->street) }}" @if($readOnly) disabled @endif>
                                        @error('street')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header" id="profileHeadingQualification">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#profileCollapseQualification" aria-expanded="true" aria-controls="profileCollapseQualification">
                                <i class="bi bi-mortarboard me-2"></i> Qualification &amp; registration
                            </button>
                        </h2>
                        <div id="profileCollapseQualification" class="accordion-collapse collapse show" aria-labelledby="profileHeadingQualification">
                            <div class="accordion-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="qualification_choice" class="form-label">Qualification / profession</label>
                                        @include('partials.select-with-other', [
                                            'idPrefix' => 'qualification',
                                            'name' => 'qualification',
                                            'options' => \App\Models\User::QUALIFICATIONS,
                                            'currentValue' => old('qualification', $user->qualification),
                                            'disabled' => $readOnly,
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
                                            'disabled' => $readOnly,
                                            'otherPlaceholder' => 'Specify education level',
                                        ])
                                    </div>
                                    <div class="col-md-6">
                                        <label for="license_board_choice" class="form-label">Issuing board / council</label>
                                        @include('partials.select-with-other', [
                                            'idPrefix' => 'license_board',
                                            'name' => 'license_board',
                                            'options' => \App\Models\User::LICENSE_BOARDS,
                                            'currentValue' => old('license_board', $user->license_board),
                                            'disabled' => $readOnly,
                                            'otherPlaceholder' => 'Specify issuing board',
                                        ])
                                    </div>
                                    <div class="col-md-6 {{ $hasLicenseBoard ? '' : 'd-none' }}" id="license_number_wrap">
                                        <label for="license_number" class="form-label">Registration / license number</label>
                                        <input type="text" class="form-control @error('license_number') is-invalid @enderror" id="license_number" name="license_number" value="{{ old('license_number', $user->license_number) }}" @if($readOnly) disabled @endif>
                                        @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="employment_type" class="form-label">Hali ya ajira / employment status</label>
                                        <select class="form-select @error('employment_type') is-invalid @enderror" id="employment_type" name="employment_type" @if($readOnly) disabled @endif>
                                            <option value="">— Select —</option>
                                            @foreach(\App\Models\User::EMPLOYMENT_TYPES as $value => $label)
                                                <option value="{{ $value }}" {{ old('employment_type', $user->employment_type) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        @error('employment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                    <div class="col-md-6 {{ $isPermanent ? '' : 'd-none' }}" id="check_number_wrap">
                                        <label for="check_number" class="form-label">Check number <span class="text-muted small">(permanent staff)</span></label>
                                        <input type="text" class="form-control @error('check_number') is-invalid @enderror" id="check_number" name="check_number" value="{{ old('check_number', $user->check_number) }}" @if($readOnly) disabled @endif>
                                        @error('check_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($user->isTutorStaff())
                        @php $selectedProgrammeIds = old('programme_ids', $user->programmes->pluck('id')->all()); @endphp
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="profileHeadingDepartment">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#profileCollapseDepartment" aria-expanded="true" aria-controls="profileCollapseDepartment">
                                    <i class="bi bi-diagram-3 me-2"></i> Department(s)
                                </button>
                            </h2>
                            <div id="profileCollapseDepartment" class="accordion-collapse collapse show" aria-labelledby="profileHeadingDepartment">
                                <div class="accordion-body">
                                    <p class="small text-muted mb-2">Choose the department(s) you teach in (up to 3).</p>
                                    <div class="row g-2 @error('programme_ids') is-invalid @enderror">
                                        @foreach($programmes as $programme)
                                            <div class="col-md-4">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="programme_ids[]" value="{{ $programme->id }}" id="programme_{{ $programme->id }}" {{ in_array($programme->id, $selectedProgrammeIds) ? 'checked' : '' }} @if($readOnly) disabled @endif>
                                                    <label class="form-check-label" for="programme_{{ $programme->id }}">{{ $programme->code }} — {{ $programme->name }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @error('programme_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                    @error('programme_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @unless($readOnly)
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary btn-modern">Save and continue</button>
                </div>
                @endunless
            </form>

            @push('scripts')
            <script>
            (function () {
                var phoneLocal = document.getElementById('phone_local');
                var phoneHidden = document.getElementById('phone_hidden');
                if (!phoneLocal || !phoneHidden) return;

                function syncPhone() {
                    var digits = phoneLocal.value.replace(/\D/g, '').replace(/^0+/, '').slice(0, 9);
                    if (digits !== phoneLocal.value) phoneLocal.value = digits;
                    phoneHidden.value = digits ? '+255' + digits : '';
                }

                phoneLocal.addEventListener('input', syncPhone);
                syncPhone();
            })();
            </script>
            @endpush

            @push('scripts')
            <script>
            (function () {
                var tanzaniaLocations = @json($tanzaniaLocations);
                var tanzaniaWards = @json($tanzaniaWards);
                var currentDistrict = @json(old('district', $user->district));
                var currentWard = @json(old('ward', $user->ward));
                var nationalityHidden = document.getElementById('nationality_hidden');
                var addressSection = document.getElementById('tanzania_address_section');
                var regionSelect = document.getElementById('region');
                var districtSelect = document.getElementById('district');
                var wardSelect = document.getElementById('ward');

                function populateSelect(select, options, selectedValue, emptyLabel, filledLabel) {
                    if (!select) return;
                    select.innerHTML = '';
                    var placeholder = document.createElement('option');
                    placeholder.value = '';
                    placeholder.textContent = options.length ? filledLabel : emptyLabel;
                    select.appendChild(placeholder);
                    options.forEach(function (value) {
                        var option = document.createElement('option');
                        option.value = value;
                        option.textContent = value;
                        if (value === selectedValue) option.selected = true;
                        select.appendChild(option);
                    });
                }

                function populateDistricts(regionName, selectedDistrict) {
                    populateSelect(districtSelect, tanzaniaLocations[regionName] || [], selectedDistrict, '— Select region first —', '— Select district —');
                }

                function populateWards(districtName, selectedWard) {
                    populateSelect(wardSelect, tanzaniaWards[districtName] || [], selectedWard, '— Select district first —', '— Select ward —');
                }

                if (regionSelect) {
                    populateDistricts(regionSelect.value, currentDistrict);
                    regionSelect.addEventListener('change', function () {
                        populateDistricts(regionSelect.value, null);
                        populateWards(districtSelect ? districtSelect.value : '', null);
                    });
                }

                if (districtSelect) {
                    populateWards(districtSelect.value, currentWard);
                    districtSelect.addEventListener('change', function () {
                        populateWards(districtSelect.value, null);
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

            @push('scripts')
            <script>
            (function () {
                var licenseBoardHidden = document.getElementById('license_board_hidden');
                var licenseNumberWrap = document.getElementById('license_number_wrap');
                if (!licenseBoardHidden || !licenseNumberWrap) return;

                var licenseNumberInput = document.getElementById('license_number');

                licenseBoardHidden.addEventListener('change', function () {
                    var hasBoard = licenseBoardHidden.value !== '';
                    licenseNumberWrap.classList.toggle('d-none', !hasBoard);
                    if (!hasBoard && licenseNumberInput) {
                        licenseNumberInput.value = '';
                    }
                });
            })();
            </script>
            @endpush
        @endif
    </div>
</div>
@endsection
