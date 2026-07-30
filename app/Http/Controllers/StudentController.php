<?php

namespace App\Http\Controllers;

use App\Models\LedgerEntry;
use App\Models\Programme;
use App\Models\Student;
use App\Support\AcademicSession;
use App\Support\StudentRegistrationNumber;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with('programme')
            ->orderByRaw('LOWER(last_name)')
            ->orderByRaw('LOWER(first_name)')
            ->orderByRaw('LOWER(COALESCE(middle_name, \'\'))');

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('reg_no', 'like', "%{$q}%")
                    ->orWhere('nactvet_reg_no', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('last_name', 'like', "%{$q}%");
            });
        }
        if ($request->filled('programme_id')) {
            $query->where('programme_id', $request->programme_id);
        }
        if ($request->filled('intake_year')) {
            $query->where('intake_year', $request->intake_year);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('nta_level') && in_array((int) $request->nta_level, [4, 5, 6], true)) {
            $query->where('nta_level', (int) $request->nta_level);
        }

        $students = $query->paginate(15)->withQueryString();
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        $intakeYears = Student::distinct()->pluck('intake_year')->filter()->sort()->values();
        $emailsWithUser = \App\Models\User::whereNotNull('email')->pluck('email')->toArray();
        $activeNtaLevel = $request->filled('nta_level') && in_array((int) $request->nta_level, [4, 5, 6], true)
            ? (int) $request->nta_level
            : null;
        $activeProgrammeId = $request->filled('programme_id') ? (int) $request->programme_id : null;

        $ntaNavCountQuery = Student::query()
            ->when($activeProgrammeId, fn ($q) => $q->where('programme_id', $activeProgrammeId));

        $ntaLevelCounts = (clone $ntaNavCountQuery)
            ->whereIn('nta_level', [4, 5, 6])
            ->selectRaw('nta_level, COUNT(*) as total')
            ->groupBy('nta_level')
            ->pluck('total', 'nta_level');

        $programmeNavCountQuery = Student::query()
            ->when($activeNtaLevel, fn ($q) => $q->where('nta_level', $activeNtaLevel));

        $programmeFilterCounts = (clone $programmeNavCountQuery)
            ->selectRaw('programme_id, COUNT(*) as total')
            ->groupBy('programme_id')
            ->pluck('total', 'programme_id');

        $allStudentsCount = (clone $programmeNavCountQuery)->count();

        return view('students.index', compact(
            'students',
            'programmes',
            'intakeYears',
            'emailsWithUser',
            'activeNtaLevel',
            'activeProgrammeId',
            'ntaLevelCounts',
            'programmeFilterCounts',
            'allStudentsCount',
        ));
    }

    public function create()
    {
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();

        return view('students.create', compact('programmes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nactvet_reg_no' => ['required', 'string', 'max:50', 'unique:students,nactvet_reg_no', $this->nactvetRegNoRule()],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'programme_id' => ['required', 'exists:programmes,id'],
            'intake_year' => ['required', 'integer', 'min:2020', 'max:2030'],
            'nta_level' => ['nullable', 'integer', 'in:4,5,6'],
            'student_type' => ['nullable', 'string', 'in:regular,transferred'],
            'transfer_date' => ['nullable', 'date'],
            'previous_institution' => ['nullable', 'string', 'max:255'],
            'previous_programme_id' => ['nullable', 'exists:programmes,id'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
            'academic_standing' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:M,F'],
            'has_personal_nhif' => ['sometimes', 'boolean'],
            'semester_two_fee_band' => ['nullable', 'string', 'in:continuous,repeat_transfer'],
        ]);
        $validated['student_type'] = $validated['student_type'] ?? 'regular';
        $validated['has_personal_nhif'] = $request->boolean('has_personal_nhif');
        if (empty($validated['semester_two_fee_band'])) {
            $validated['semester_two_fee_band'] = null;
        }
        $validated['form_four_index'] = $validated['nactvet_reg_no'];

        $programme = Programme::findOrFail($validated['programme_id']);
        $validated['reg_no'] = $this->generateRegNo($validated['intake_year'], $programme->code);
        $validated['status'] = 'active';

        Student::create($validated);

        return redirect()->route('students.index')->with('success', 'Student registered successfully. Reg no: '.$validated['reg_no']);
    }

    public function show(Student $student)
    {
        if (auth()->user()->isStudent() && (! auth()->user()->student || auth()->user()->student->id !== $student->id)) {
            abort(403, 'You can only view your own profile.');
        }
        $student->load([
            'programme',
            'previousProgramme',
            'semesterRegistrations.semester',
            'studentDocuments',
            'moduleEnrollments.course',
            'moduleEnrollments.semester',
        ]);

        $guardianUser = \App\Models\User::where('linked_student_id', $student->id)->where('role', 'guardian')->first();

        return view('students.show', compact('student', 'guardianUser'));
    }

    public function edit(Student $student)
    {
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();
        $suppliesYear = AcademicSession::currentStartYear();

        return view('students.edit', compact('student', 'programmes', 'suppliesYear'));
    }

    public function ledger(Student $student, \Illuminate\Http\Request $request, \App\Services\StudentFinancialStatementService $statements)
    {
        if (auth()->user()->isStudent()) {
            if (! auth()->user()->student || auth()->user()->student->id !== $student->id) {
                abort(403, 'You can only view your own financial statement.');
            }
            $student->load('programme');
            $availableYears = $statements->availableAcademicYears($student);
            $academicYear = $request->integer('academic_year') ?: $statements->defaultAcademicYear($student);
            $statement = $statements->build($student, $academicYear);

            return view('students.financial-statement', compact('student', 'statement', 'availableYears', 'academicYear'));
        }

        if (! auth()->user()->canAccessFinance()) {
            abort(403, 'Fee ledger is for finance staff (bursar/accountant) or administrator only.');
        }
        $student->load('programme');
        $entries = $student->ledgerEntries()->orderBy('created_at')->orderBy('id')->paginate(20);

        return view('students.ledger', compact('student', 'entries'));
    }

    public function ledgerCharge(Request $request, Student $student)
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);
        $amount = (int) round($validated['amount']);
        $debits = (float) $student->ledgerEntries()->where('type', 'debit')->sum('amount');
        $credits = (float) $student->ledgerEntries()->where('type', 'credit')->sum('amount');
        $balanceAfter = ($debits + $amount) - $credits;
        LedgerEntry::create([
            'student_id' => $student->id,
            'type' => 'debit',
            'amount' => $amount,
            'description' => $validated['description'] ?? 'Fee / charge',
            'reference_type' => 'charge',
            'reference_id' => null,
            'balance_after' => (int) round($balanceAfter),
        ]);

        return redirect()->route('students.ledger', $student)->with('success', 'Charge recorded: '.number_format($amount).' TZS');
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'nactvet_reg_no' => ['required', 'string', 'max:50', 'unique:students,nactvet_reg_no,'.$student->id, $this->nactvetRegNoRule()],
            'first_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'date_of_birth' => ['nullable', 'date'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'biometric_id' => ['nullable', 'string', 'max:30', 'unique:students,biometric_id,'.$student->id],
            'programme_id' => ['required', 'exists:programmes,id'],
            'intake_year' => ['required', 'integer', 'min:2020', 'max:2030'],
            'nta_level' => ['nullable', 'integer', 'in:4,5,6'],
            'student_type' => ['nullable', 'string', 'in:regular,transferred'],
            'transfer_date' => ['nullable', 'date'],
            'previous_institution' => ['nullable', 'string', 'max:255'],
            'previous_programme_id' => ['nullable', 'exists:programmes,id'],
            'status' => ['required', 'string', 'in:active,deactivated,withdrawn,graduated'],
            'graduated_at' => ['nullable', 'date'],
            'reporting_status' => ['nullable', 'string', 'in:reported,not_reported,postponed,absconded'],
            'reporting_date' => ['nullable', 'date'],
            'tuition_completion_pledge_date' => ['nullable', 'date'],
            'nhif_status' => ['nullable', 'string', 'in:PAID,NOT PAID'],
            'tuition_payment_ref' => ['nullable', 'string', 'max:100'],
            'nhif_payment_ref' => ['nullable', 'string', 'max:100'],
            'nactvet_qa_status' => ['nullable', 'string', 'in:PAID,NOT PAID'],
            'nactvet_qa_payment_ref' => ['nullable', 'string', 'max:100'],
            'joining_instructions_submitted' => ['nullable', 'string', 'max:10'],
            'submitted_certificates' => ['nullable', 'array'],
            'submitted_certificates.*' => ['string', Rule::in(array_keys(Student::CERTIFICATE_OPTIONS))],
            'academic_requirements' => ['nullable', 'string', 'max:255'],
            'class_property_received' => ['nullable', 'string', 'max:10'],
            'chair_number' => ['nullable', 'string', 'max:30'],
            'table_number' => ['nullable', 'string', 'max:30'],
            'guardian_name' => ['nullable', 'string', 'max:150'],
            'guardian_phone' => ['nullable', 'string', 'max:30'],
            'guardian_relationship' => ['nullable', 'string', 'max:50'],
            'academic_standing' => ['nullable', 'string', 'max:30'],
            'gender' => ['nullable', 'in:M,F'],
            'class_group' => ['nullable', 'string', 'max:80'],
            'tuition_status_override' => ['nullable', 'string', 'in:loan_beneficiary,completed,not_paid,partial,paid'],
            'has_personal_nhif' => ['sometimes', 'boolean'],
            'semester_two_fee_band' => ['nullable', 'string', 'in:continuous,repeat_transfer'],
        ]);

        if (isset($validated['tuition_status_override']) && $validated['tuition_status_override'] === '') {
            $validated['tuition_status_override'] = null;
        }
        $validated['has_personal_nhif'] = $request->boolean('has_personal_nhif');
        if (isset($validated['semester_two_fee_band']) && $validated['semester_two_fee_band'] === '') {
            $validated['semester_two_fee_band'] = null;
        }
        if (($validated['status'] ?? null) !== 'graduated') {
            $validated['graduated_at'] = null;
        } elseif (empty($validated['graduated_at'])) {
            $validated['graduated_at'] = $student->graduated_at?->toDateString() ?? now()->toDateString();
        }
        $validated['form_four_index'] = $validated['nactvet_reg_no'];

        $validated['physical_supplies_ack'] = Student::mergePhysicalSuppliesFromInput(
            $student->physical_supplies_ack ?? [],
            $request->input('supplies', [])
        );
        $validated['submitted_certificates'] = array_values($request->input('submitted_certificates', []));

        $student->update($validated);

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
    }

    public function importForm()
    {
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();

        return view('students.import', compact('programmes'));
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'default_programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'default_intake_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'default_nta_level' => ['nullable', 'in:4,5,6'],
        ]);
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return redirect()->route('students.import')->with('error', 'Could not read file.');
        }
        try {
            $headerRow = fgetcsv($handle);
            if ($headerRow === false || empty($headerRow)) {
                fclose($handle);

                return redirect()->route('students.import')->with('error', 'CSV file is empty or could not read header.');
            }
            if (isset($headerRow[0])) {
                $headerRow[0] = ltrim((string) $headerRow[0], "\xEF\xBB\xBF");
            }
            $header = array_map('trim', $headerRow);
            $idx = $this->studentImportResolveColumnIndexes($header);
            if ($err = $this->studentImportValidateFileLayout($idx, $request)) {
                fclose($handle);

                return redirect()->route('students.import')->with('error', $err);
            }
            $defaultProgramme = $request->filled('default_programme_id')
                ? Programme::where('is_active', true)->find($request->integer('default_programme_id'))
                : null;
            if ($request->filled('default_programme_id') && ! $defaultProgramme) {
                fclose($handle);

                return redirect()->route('students.import')->with('error', 'Default programme is missing or inactive.');
            }
            $defaultIntakeYear = $request->filled('default_intake_year') ? $request->integer('default_intake_year') : null;
            $defaultNtaLevel = $request->filled('default_nta_level') ? $request->integer('default_nta_level') : null;

            $created = 0;
            $errors = [];
            $programmesByCode = Programme::where('is_active', true)->get()->keyBy('code');
            while (($rawRow = fgetcsv($handle)) !== false) {
                $trimmed = array_map('trim', is_array($rawRow) ? $rawRow : []);
                $padded = array_pad(array_slice($trimmed, 0, count($header)), count($header), '');
                $nactvet = $this->studentImportCell($padded, $idx['nactvet_reg_no']);
                $firstName = $this->studentImportCell($padded, $idx['first_name']);
                $lastName = $this->studentImportCell($padded, $idx['last_name']);
                $fullName = $this->studentImportCell($padded, $idx['full_name']);
                if ($firstName === '' && $lastName === '' && $fullName !== '') {
                    [$firstName, $lastName] = $this->splitImportCandidateName($fullName);
                }
                $progCode = strtoupper($this->studentImportCell($padded, $idx['programme_code']));
                if ($progCode === '' && $defaultProgramme) {
                    $progCode = strtoupper($defaultProgramme->code);
                }
                $intakeYear = 0;
                $intakeCell = $this->studentImportCell($padded, $idx['intake_year']);
                if ($intakeCell !== '' && is_numeric($intakeCell)) {
                    $intakeYear = (int) $intakeCell;
                }
                if ($intakeYear <= 0 && $defaultIntakeYear) {
                    $intakeYear = $defaultIntakeYear;
                }
                if ($intakeYear <= 0) {
                    $intakeYear = $this->guessIntakeYearFromRegistration($nactvet);
                }
                $ntaCell = $this->studentImportCell($padded, $idx['nta_level']);
                if ($nactvet === '' || $firstName === '' || $lastName === '' || $progCode === '') {
                    $errors[] = 'Row skipped (empty registration, name, or programme)';

                    continue;
                }
                $nta = $this->resolveImportNtaLevel($ntaCell, 'Row for '.$nactvet, $defaultNtaLevel);
                if ($nta['error'] !== null) {
                    $errors[] = $nta['error'];

                    continue;
                }
                $ntaLevel = $nta['level'];
                if (! $this->isValidStudentImportNactvetOrNacteRegNo($nactvet)) {
                    $errors[] = 'Invalid registration format: '.$nactvet.' ('.StudentRegistrationNumber::validationMessage().')';

                    continue;
                }
                if (Student::where('nactvet_reg_no', $nactvet)->exists()) {
                    $errors[] = 'Registration '.$nactvet.' already exists.';

                    continue;
                }
                $programme = $programmesByCode->get($progCode);
                if (! $programme) {
                    $errors[] = 'Unknown programme code: '.$progCode;

                    continue;
                }
                $email = $this->studentImportCell($padded, $idx['email']);
                $phone = $this->studentImportCell($padded, $idx['phone']);
                $regNo = $this->generateRegNo($intakeYear, $programme->code);
                Student::create([
                    'reg_no' => $regNo,
                    'nactvet_reg_no' => $nactvet,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email !== '' ? $email : null,
                    'phone' => $phone !== '' ? $phone : null,
                    'programme_id' => $programme->id,
                    'intake_year' => $intakeYear,
                    'nta_level' => $ntaLevel,
                    'status' => 'active',
                ]);
                $created++;
            }
            fclose($handle);
            $msg = $created.' student(s) imported.';
            if (count($errors) > 0) {
                $msg .= ' '.count($errors).' issue(s).';
            }

            return redirect()->route('students.index')->with($created > 0 ? 'success' : 'error', $msg);
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return redirect()->route('students.import')->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    public function importAdmittedForm()
    {
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();

        return view('students.import-admitted', compact('programmes'));
    }

    /**
     * Minimal intake upload (NACTVET / TAMISEMI lists): name, index number, sex; remaining fields filled later in the registration wizard.
     */
    public function importAdmittedStore(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
            'default_programme_id' => ['nullable', 'integer', 'exists:programmes,id'],
            'default_intake_year' => ['nullable', 'integer', 'min:1990', 'max:2100'],
            'default_nta_level' => ['nullable', 'in:4,5,6'],
        ]);
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            return redirect()->route('students.import-admitted')->with('error', 'Could not read file.');
        }
        try {
            $headerRow = fgetcsv($handle);
            if ($headerRow === false || empty($headerRow)) {
                fclose($handle);

                return redirect()->route('students.import-admitted')->with('error', 'CSV file is empty or could not read header.');
            }
            if (isset($headerRow[0])) {
                $headerRow[0] = ltrim((string) $headerRow[0], "\xEF\xBB\xBF");
            }
            $header = array_map('trim', $headerRow);
            $idx = $this->studentImportResolveColumnIndexes($header);
            if ($err = $this->studentImportValidateFileLayout($idx, $request)) {
                fclose($handle);

                return redirect()->route('students.import-admitted')->with('error', $err);
            }
            $defaultProgramme = $request->filled('default_programme_id')
                ? Programme::where('is_active', true)->find($request->integer('default_programme_id'))
                : null;
            if ($request->filled('default_programme_id') && ! $defaultProgramme) {
                fclose($handle);

                return redirect()->route('students.import-admitted')->with('error', 'Default programme is missing or inactive.');
            }
            $defaultIntakeYear = $request->filled('default_intake_year') ? $request->integer('default_intake_year') : null;
            $defaultNtaLevel = $request->filled('default_nta_level') ? $request->integer('default_nta_level') : null;

            $created = 0;
            $errors = [];
            $programmesByCode = Programme::where('is_active', true)->get()->keyBy('code');
            while (($rawRow = fgetcsv($handle)) !== false) {
                $trimmed = array_map('trim', is_array($rawRow) ? $rawRow : []);
                $padded = array_pad(array_slice($trimmed, 0, count($header)), count($header), '');
                $nactvet = $this->studentImportCell($padded, $idx['nactvet_reg_no']);
                $firstName = $this->studentImportCell($padded, $idx['first_name']);
                $lastName = $this->studentImportCell($padded, $idx['last_name']);
                $fullName = $this->studentImportCell($padded, $idx['full_name']);
                if ($firstName === '' && $lastName === '' && $fullName !== '') {
                    [$firstName, $lastName] = $this->splitImportCandidateName($fullName);
                }
                $middleName = $this->studentImportCell($padded, $idx['middle_name']);
                $progCode = strtoupper($this->studentImportCell($padded, $idx['programme_code']));
                if ($progCode === '' && $defaultProgramme) {
                    $progCode = strtoupper($defaultProgramme->code);
                }
                $intakeYear = 0;
                $intakeCell = $this->studentImportCell($padded, $idx['intake_year']);
                if ($intakeCell !== '' && is_numeric($intakeCell)) {
                    $intakeYear = (int) $intakeCell;
                }
                if ($intakeYear <= 0 && $defaultIntakeYear) {
                    $intakeYear = $defaultIntakeYear;
                }
                if ($intakeYear <= 0) {
                    $intakeYear = $this->guessIntakeYearFromRegistration($nactvet);
                }
                $genderRaw = strtoupper(substr($this->studentImportCell($padded, $idx['gender']), 0, 1));
                $gender = in_array($genderRaw, ['M', 'F'], true) ? $genderRaw : '';
                $admissionSource = strtolower(trim($this->studentImportCell($padded, $idx['admission_source'])));
                if ($admissionSource === '') {
                    $admissionSource = 'nactvet';
                }
                if (! in_array($admissionSource, ['nactvet', 'tamisemi'], true)) {
                    $admissionSource = 'nactvet';
                }
                if ($nactvet === '' || $firstName === '' || $lastName === '' || $progCode === '') {
                    $errors[] = 'Row skipped (empty registration, name, or programme)';

                    continue;
                }
                $ntaCell = $this->studentImportCell($padded, $idx['nta_level']);
                $nta = $this->resolveImportNtaLevel($ntaCell, 'Row for '.$nactvet, $defaultNtaLevel);
                if ($nta['error'] !== null) {
                    $errors[] = $nta['error'];

                    continue;
                }
                $ntaLevel = $nta['level'];
                if (strlen($nactvet) < 4 || strlen($nactvet) > 80) {
                    $errors[] = 'Invalid index / registration no. length: '.$nactvet;

                    continue;
                }
                if ($admissionSource === 'nactvet' && ! $this->isValidStudentImportNactvetOrNacteRegNo($nactvet)) {
                    $errors[] = 'Invalid NACTVET/NACTE format: '.$nactvet;

                    continue;
                }
                if (Student::where('nactvet_reg_no', $nactvet)->exists()) {
                    $errors[] = 'Index '.$nactvet.' already exists.';

                    continue;
                }
                $programme = $programmesByCode->get($progCode);
                if (! $programme) {
                    $errors[] = 'Unknown programme code: '.$progCode;

                    continue;
                }
                $regNo = $this->generateRegNo($intakeYear, $programme->code);
                Student::create([
                    'reg_no' => $regNo,
                    'nactvet_reg_no' => $nactvet,
                    'form_four_index' => $nactvet,
                    'first_name' => $firstName,
                    'middle_name' => $middleName !== '' ? $middleName : null,
                    'last_name' => $lastName,
                    'gender' => $gender !== '' ? $gender : null,
                    'programme_id' => $programme->id,
                    'intake_year' => $intakeYear,
                    'nta_level' => $ntaLevel,
                    'admission_source' => $admissionSource,
                    'status' => 'active',
                ]);
                $created++;
            }
            fclose($handle);
            $msg = $created.' admitted student(s) imported.';
            if (count($errors) > 0) {
                $msg .= ' '.count($errors).' issue(s).';
            }

            return redirect()->route('students.index')->with($created > 0 ? 'success' : 'error', $msg);
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            return redirect()->route('students.import-admitted')->with('error', 'Import failed: '.$e->getMessage());
        }
    }

    private function generateRegNo(int $intakeYear, string $programmeCode): string
    {
        $prefix = 'MCHAS-'.$intakeYear.'-'.strtoupper($programmeCode);
        $last = Student::where('reg_no', 'like', $prefix.'%')->orderBy('id', 'desc')->first();
        $seq = $last ? (int) substr($last->reg_no, strlen($prefix)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{error: string|null, level: int|null}
     */
    private function resolveImportNtaLevel(string $raw, string $rowLabel, ?int $defaultLevel = null): array
    {
        $t = trim($raw);
        if ($t === '') {
            if ($defaultLevel !== null && in_array($defaultLevel, [4, 5, 6], true)) {
                return ['error' => null, 'level' => $defaultLevel];
            }

            return ['error' => $rowLabel.': nta_level is required (4, 5, or 6), or choose a default NTA level on the form.', 'level' => null];
        }
        if (! is_numeric($t)) {
            return ['error' => $rowLabel.': nta_level must be a number (4, 5, or 6).', 'level' => null];
        }
        $f = (float) $t;
        if (abs($f - round($f)) > 1e-9) {
            return ['error' => $rowLabel.': nta_level must be a whole number (4, 5, or 6).', 'level' => null];
        }
        $n = (int) round($f);
        if (! in_array($n, [4, 5, 6], true)) {
            return ['error' => $rowLabel.': nta_level must be 4, 5, or 6 (got '.$t.').', 'level' => null];
        }

        return ['error' => null, 'level' => $n];
    }

    private function studentImportNormalizeHeaderLabel(string $h): string
    {
        $h = str_replace(["\xC2\xA0"], ' ', $h);
        $h = str_replace(['(', ')'], ' ', $h);
        $h = str_replace('/', ' ', $h);
        $h = trim(preg_replace('/\s+/', ' ', $h));

        return strtolower($h);
    }

    /**
     * @return array<string, list<string>>
     */
    private function studentImportColumnAliasMap(): array
    {
        return [
            'nactvet_reg_no' => [
                'nactvet_reg_no', 'nactvet reg no', 'nactvet registration',
                'nacte registration', 'nacte reg', 'nacte reg no',
                'nactvet registration number', 'registration number',
                'reg no', 'regno',
            ],
            'full_name' => [
                'candidat', 'candidate', 'candidate name', 'student name',
                'full name', 'names', 'name',
            ],
            'first_name' => ['first name', 'firstname', 'given name', 'fname'],
            'last_name' => ['last name', 'lastname', 'surname', 'family name', 'lname'],
            'middle_name' => ['middle name', 'middlename', 'other name'],
            'programme_code' => [
                'programme_code', 'programme code', 'programme', 'program code',
                'prog code', 'course code',
            ],
            'intake_year' => ['intake_year', 'intake year', 'academic year'],
            'nta_level' => ['nta_level', 'nta level'],
            'email' => ['email', 'e mail'],
            'phone' => ['phone', 'mobile', 'tel', 'telephone', 'msisdn'],
            'gender' => ['sex', 'sex m f', 'gender'],
            'admission_source' => ['admission_source', 'admission source', 'source'],
        ];
    }

    /**
     * @return array<string, int|null>
     */
    private function studentImportResolveColumnIndexes(array $headerCells): array
    {
        $map = $this->studentImportColumnAliasMap();
        $resolved = [];
        foreach (array_keys($map) as $key) {
            $resolved[$key] = null;
        }
        foreach ($headerCells as $i => $raw) {
            $label = $this->studentImportNormalizeHeaderLabel((string) $raw);
            if ($label === '') {
                continue;
            }
            foreach ($map as $canonical => $aliases) {
                if ($resolved[$canonical] !== null) {
                    continue;
                }
                if (in_array($label, $aliases, true)) {
                    $resolved[$canonical] = $i;
                    break;
                }
            }
        }

        return $resolved;
    }

    private function studentImportValidateFileLayout(array $idx, Request $request): ?string
    {
        if ($idx['nactvet_reg_no'] === null) {
            return 'Missing NACTVET/NACTE registration column. Expected a header such as "NACTE REGISTRATION" or nactvet_reg_no.';
        }
        $hasName = ($idx['full_name'] !== null)
            || ($idx['first_name'] !== null && $idx['last_name'] !== null);
        if (! $hasName) {
            return 'Missing candidate name. Add a CANDIDAT/candidate column, or separate first_name and last_name columns.';
        }
        $progOk = $idx['programme_code'] !== null || $request->filled('default_programme_id');
        if (! $progOk) {
            return 'Missing programme in the file. Select a default programme on the form, or add a programme_code column.';
        }
        $ntaOk = $idx['nta_level'] !== null || $request->filled('default_nta_level');
        if (! $ntaOk) {
            return 'Missing NTA level in the file. Select a default NTA level (4, 5, or 6) on the form, or add an nta_level column.';
        }

        return null;
    }

    /**
     * @param  list<string>  $values
     */
    private function studentImportCell(array $values, ?int $index): string
    {
        if ($index === null || ! array_key_exists($index, $values)) {
            return '';
        }

        return trim((string) $values[$index]);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitImportCandidateName(string $name): array
    {
        $name = trim(preg_replace('/\s+/', ' ', $name));
        if ($name === '') {
            return ['', ''];
        }
        if (str_contains($name, ',')) {
            $parts = array_map('trim', explode(',', $name, 2));
            $surname = $parts[0] ?? '';
            $given = $parts[1] ?? '';

            return [$given !== '' ? $given : $surname, $surname !== '' ? $surname : $given];
        }
        $parts = preg_split('/\s+/', $name) ?: [];
        if (count($parts) === 1) {
            return [$parts[0], $parts[0]];
        }
        $firstName = array_shift($parts);

        return [$firstName, implode(' ', $parts)];
    }

    private function guessIntakeYearFromRegistration(string $regNo): int
    {
        if (preg_match('/\/(\d{4})\s*$/', $regNo, $m)) {
            $y = (int) $m[1];
            if ($y >= 1990 && $y <= 2100) {
                return $y;
            }
        }

        return (int) date('Y');
    }

    private function isValidStudentImportNactvetOrNacteRegNo(string $n): bool
    {
        return StudentRegistrationNumber::isValid($n);
    }

    /** @return \Closure */
    private function nactvetRegNoRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if (! StudentRegistrationNumber::isValid((string) $value)) {
                $fail(StudentRegistrationNumber::validationMessage());
            }
        };
    }

    public function bulkSms(Request $request, \App\Services\Sms\TwilioSmsSender $sms)
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:200'],
            'ids.*' => ['integer'],
            'body' => ['required', 'string', 'max:500'],
        ]);

        $students = Student::query()->whereIn('id', $validated['ids'])->get();
        $sent = 0;
        $failed = 0;

        foreach ($students as $student) {
            $log = $sms->send($student->phone ?? '', $validated['body'], [
                'student_id' => $student->id,
                'recipient_role' => 'student',
                'template' => 'bulk_manual',
                'sent_by' => auth()->id(),
            ]);
            $log->status === 'sent' ? $sent++ : $failed++;
        }

        $msg = "{$sent} SMS sent.";
        if ($failed > 0) {
            $msg .= " {$failed} failed or skipped — see message log.";
        }

        return back()->with($failed > 0 ? 'warning' : 'success', $msg);
    }
}
