<?php

namespace App\Http\Controllers;

use App\Mail\TemporaryPasswordMail;
use App\Models\ActivityLog;
use App\Models\Student;
use App\Models\User;
use App\Models\UserModulePermission;
use App\Support\RolePermissions;
use App\Support\StudentSurname;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function rolePermissions(Request $request)
    {
        $query = User::query()->whereIn('role', array_keys(User::staffRoleOptions()));

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($qry) use ($q) {
                $qry->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('staff_id', 'like', "%{$q}%");
            });
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('users.role-permissions', compact('users'));
    }

    public function roleMatrix()
    {
        $modules = RolePermissions::modules();
        $actions = config('permissions.actions', ['view', 'create', 'update', 'delete']);
        $roles = collect(User::staffRoleOptions())->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
        ])->values();
        $matrix = RolePermissions::matrix();

        return view('users.role-matrix', compact('modules', 'actions', 'roles', 'matrix'));
    }

    public function permissions(User $user)
    {
        $modules = RolePermissions::modules();
        $actions = config('permissions.actions', ['view', 'create', 'update', 'delete']);
        $roleGrants = RolePermissions::roleGrants(User::normalizeRoleSlug((string) $user->role));
        $extraGrants = $user->extraModulePermissions()->orderBy('module')->get()->keyBy('module');

        return view('users.permissions', compact('user', 'modules', 'actions', 'roleGrants', 'extraGrants'));
    }

    public function permissionsStore(Request $request, User $user)
    {
        $validated = $request->validate([
            'module' => ['required', 'string', Rule::in(array_keys(RolePermissions::modules()))],
            'actions' => ['required', 'array', 'min:1'],
            'actions.*' => ['string', Rule::in(config('permissions.actions', ['view', 'create', 'update', 'delete']))],
        ]);

        $grant = UserModulePermission::firstOrNew([
            'user_id' => $user->id,
            'module' => $validated['module'],
        ]);
        $grant->actions = array_values(array_unique(array_merge($grant->actions ?? [], $validated['actions'])));
        $grant->granted_by = auth()->id();
        $grant->save();

        ActivityLog::log(
            'user_permission.granted',
            UserModulePermission::class,
            $grant->id,
            "Granted {$validated['module']} (".implode(',', $validated['actions']).") to {$user->name}"
        );

        return redirect()->route('users.permissions', $user)->with('success', 'Permission granted.');
    }

    public function permissionsDestroy(User $user, UserModulePermission $user_module_permission)
    {
        abort_unless($user_module_permission->user_id === $user->id, 404);

        $module = $user_module_permission->module;
        $user_module_permission->delete();

        ActivityLog::log(
            'user_permission.revoked',
            UserModulePermission::class,
            $user->id,
            "Revoked {$module} extra grant from {$user->name}"
        );

        return redirect()->route('users.permissions', $user)->with('success', 'Permission removed.');
    }

    public function index(Request $request)
    {
        $type = $request->query('type', 'student');
        if (! in_array($type, ['student', 'staff'], true)) {
            $type = 'student';
        }

        $query = User::query();
        if ($type === 'student') {
            $query->where('role', 'student')->orderBy('name');
        } else {
            $query->where('role', '!=', 'student')
                ->orderByRaw(User::staffRoleSortSqlCase())
                ->orderBy('surname')
                ->orderBy('name');
        }

        $users = $query->paginate(15)->withQueryString();
        $studentCount = User::where('role', 'student')->count();
        $staffCount = User::where('role', '!=', 'student')->count();

        $loginFilters = [
            'class_group' => $request->query('class_group'),
            'intake_year' => $request->query('intake_year'),
        ];
        $studentsAwaitingLogin = 0;
        $studentsAwaitingList = collect();
        $classGroupOptions = collect();
        $intakeYearOptions = collect();

        if ($type === 'student') {
            $awaitingQuery = $this->studentsEligibleForUserAccountQuery($loginFilters);
            $studentsAwaitingLogin = (clone $awaitingQuery)->count();
            $studentsAwaitingList = (clone $awaitingQuery)->limit(100)->get();
            $classGroupOptions = $this->distinctStudentFieldValues('class_group');
            $intakeYearOptions = $this->distinctStudentFieldValues('intake_year');
        }

        return view('users.index', compact(
            'users',
            'type',
            'studentCount',
            'staffCount',
            'studentsAwaitingLogin',
            'studentsAwaitingList',
            'loginFilters',
            'classGroupOptions',
            'intakeYearOptions',
        ));
    }

    public function create()
    {
        $studentsWithoutUser = $this->studentsEligibleForUserAccount();

        return view('users.create', compact('studentsWithoutUser'));
    }

    public function store(Request $request)
    {
        $role = $request->input('role');
        if ($role === 'student') {
            $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'action' => ['nullable', 'string', 'in:create_login,generate_password'],
            ]);
            $student = Student::findOrFail($request->student_id);
            if ($this->studentAlreadyHasUser($student)) {
                return redirect()->back()->withInput()->withErrors(['student_id' => 'This student already has a user account.']);
            }

            $generatePassword = $request->input('action', 'generate_password') === 'generate_password';
            [$user, $temporaryPassword] = $this->createStudentUser($student, $generatePassword);

            if (Schema::hasTable('activity_log')) {
                ActivityLog::log('user.created', User::class, $user->id, 'student user, email: '.$user->email);
            }

            if (! $generatePassword) {
                return redirect()
                    ->route('users.edit', $user)
                    ->with('success', 'Student login created from the register. Click **Generate password** to set the surname password (one word, lowercase).');
            }

            $surname = $student->singleSurname();

            return redirect()
                ->route('users.index', ['type' => 'student'])
                ->with('success', 'Login created. NACTVET: '.$student->nactvet_reg_no.' · Password: '.$temporaryPassword.' (surname: '.$surname.'). Student must change it on first sign-in.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', 'string', Rule::in(array_keys(User::staffRoleOptions()))],
        ]);
        $temporaryPassword = StudentSurname::portalPassword($validated['surname']);
        $validated['password'] = Hash::make($temporaryPassword);
        $validated['must_change_password'] = true;
        $validated['staff_id'] = self::generateStaffId();
        $validated['profile_completed_at'] = now();
        $validated['role'] = User::normalizeRoleSlug($validated['role']);
        unset($validated['surname']);
        $user = User::create($validated);
        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('user.created', User::class, $user->id, 'staff user, staff_id: '.$user->staff_id);
        }

        return redirect()->route('users.index')->with('success', 'User created. Login: '.$user->staff_id.', temporary password: '.$temporaryPassword.'. The user must change it on first login.');
    }

    /** Create one user per active student who has email and no user yet. */
    public function createAllStudentLogins(Request $request)
    {
        $students = $this->studentsEligibleForUserAccount();

        if ($students->isEmpty()) {
            return redirect()->route('users.index')->with('info', 'No students without a user account.');
        }

        $sendEmail = $request->boolean('send_email');
        $credentials = [];
        $emailed = 0;
        $emailSkipped = 0;

        foreach ($students as $student) {
            [$user, $temporaryPassword] = $this->createStudentUser($student, true);
            $login = $user->loginIdentifier();
            $credentials[] = [
                'name' => $student->full_name,
                'reg_no' => $student->reg_no,
                'login' => $login,
                'email' => $student->email,
                'temporary_password' => $temporaryPassword,
            ];
            if ($sendEmail && $this->sendTemporaryPasswordEmail($user, $temporaryPassword, $login)) {
                $emailed++;
            } elseif ($sendEmail) {
                $emailSkipped++;
            }
        }

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('user.bulk_student_logins', null, null, count($credentials).' student logins created');
        }

        return $this->downloadCredentialsCsv($credentials, 'student-logins-'.now()->format('Y-m-d-His').'.csv');
    }

    /**
     * Issue a new temporary password for one user (shown on screen; optional email).
     */
    public function issueTemporaryPassword(Request $request, User $user)
    {
        $request->validate([
            'send_email' => ['nullable', 'boolean'],
        ]);

        $temporaryPassword = $this->applyTemporaryPassword($user);
        $login = $user->fresh()->loginIdentifier();
        $emailed = false;
        $emailNote = '';
        $passwordNote = $user->isStudent() ? ' Password is one surname only (lowercase).' : '';

        if ($request->boolean('send_email')) {
            if ($this->sendTemporaryPasswordEmail($user, $temporaryPassword, $login)) {
                $emailed = true;
                $emailNote = ' A copy was sent to '.$user->email.'.';
            } else {
                $emailNote = ' Email was not sent (configure MAIL in .env and use a real student email, not @student.cohas.local).';
            }
        }

        $redirectToIndex = $request->input('redirect') === 'index';

        return redirect()
            ->to($redirectToIndex ? route('users.index', ['type' => 'student']) : route('users.edit', $user))
            ->with('success', ($user->isStudent() ? 'Password generated.' : 'New temporary password issued.').$passwordNote.$emailNote)
            ->with('issued_login', $login)
            ->with('issued_temp_password', $temporaryPassword)
            ->with('issued_password_emailed', $emailed)
            ->with('issued_user_name', $user->name);
    }

    /** Create portal login + surname password for one registered student. */
    public function createStudentLogin(Request $request, Student $student)
    {
        if ($this->studentAlreadyHasUser($student)) {
            return redirect()
                ->route('users.index', ['type' => 'student'])
                ->with('error', 'This student already has a portal login.');
        }

        [$user, $temporaryPassword] = $this->createStudentUser($student, true);
        $login = $user->loginIdentifier();

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('user.created', User::class, $user->id, 'student user, email: '.$user->email);
        }

        return redirect()
            ->route('users.index', array_filter([
                'type' => 'student',
                'class_group' => $request->input('class_group'),
                'intake_year' => $request->input('intake_year'),
            ], fn ($v) => $v !== null && $v !== ''))
            ->with('success', 'Login created for '.$student->full_name.'.')
            ->with('issued_login', $login)
            ->with('issued_temp_password', $temporaryPassword)
            ->with('issued_user_name', $student->full_name);
    }

    /**
     * Reset temporary passwords for all student users; download CSV (optional email per student).
     */
    public function issueAllStudentPasswords(Request $request): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        $request->validate([
            'send_email' => ['nullable', 'boolean'],
        ]);

        $users = User::query()->where('role', 'student')->orderBy('name')->get();
        if ($users->isEmpty()) {
            return redirect()->route('users.index')->with('info', 'No student user accounts found.');
        }

        $sendEmail = $request->boolean('send_email');
        $credentials = [];
        $emailed = 0;
        $emailSkipped = 0;

        foreach ($users as $user) {
            $temporaryPassword = $this->applyTemporaryPassword($user->fresh());
            $login = $user->loginIdentifier();
            $credentials[] = [
                'name' => $user->name,
                'reg_no' => $user->nactvet_reg_no ?? '—',
                'login' => $login,
                'email' => $user->email,
                'temporary_password' => $temporaryPassword,
            ];
            if ($sendEmail && $this->sendTemporaryPasswordEmail($user, $temporaryPassword, $login)) {
                $emailed++;
            } elseif ($sendEmail) {
                $emailSkipped++;
            }
        }

        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('user.bulk_temp_passwords', null, null, count($credentials).' student passwords re-issued');
        }

        $filename = 'student-passwords-'.now()->format('Y-m-d-His').'.csv';

        return $this->downloadCredentialsCsv($credentials, $filename);
    }

    public function importForm()
    {
        return view('users.import');
    }

    public function importStore(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:2048'],
        ]);

        $path = $request->file('file')->getRealPath();
        $rows = array_map('str_getcsv', file($path));
        $header = array_map('strtolower', array_map('trim', $rows[0] ?? []));
        $nameCol = $this->findColumn($header, ['name']);
        $surnameCol = $this->findColumn($header, ['surname']);
        $emailCol = $this->findColumn($header, ['email']);
        $roleCol = $this->findColumn($header, ['role']);
        $checkNumberCol = $this->findColumn($header, ['check_number', 'checknumber']);
        $nactvetCol = $this->findColumn($header, ['nactvet_reg_no', 'nactvet']);

        if ($nameCol === null || $surnameCol === null) {
            return redirect()->route('users.import')->with('error', 'CSV must have columns: name, surname. For staff: email, check_number, role. For student: nactvet_reg_no (and student must exist with that reg no and email).');
        }

        $created = 0;
        $skipped = 0;
        $studentCredentials = [];

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $name = trim($row[$nameCol] ?? '');
            $surname = trim($row[$surnameCol] ?? '');
            if ($name === '' || $surname === '') {
                $skipped++;

                continue;
            }
            $rawRole = ($roleCol !== null && isset($row[$roleCol])) ? trim($row[$roleCol]) : 'student';
            $roleKey = strtolower(str_replace([' ', '-'], '_', $rawRole));
            $role = User::normalizeRoleSlug($roleKey);
            if (! array_key_exists($role, User::POSITIONS)) {
                $role = 'student';
            }

            if ($role === 'student') {
                $nactvet = $nactvetCol !== null && isset($row[$nactvetCol]) ? trim($row[$nactvetCol]) : '';
                $email = $emailCol !== null && isset($row[$emailCol]) ? trim($row[$emailCol]) : '';
                if ($nactvet === '') {
                    $skipped++;

                    continue;
                }
                $student = Student::where('nactvet_reg_no', $nactvet)->first();
                if (! $student) {
                    $skipped++;

                    continue;
                }
                $email = $student->email ?: $email;
                if ($this->studentAlreadyHasUser($student)) {
                    $skipped++;

                    continue;
                }
                $this->createStudentUser($student, true);
            } else {
                $email = $emailCol !== null && isset($row[$emailCol]) ? trim($row[$emailCol]) : '';
                if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;

                    continue;
                }
                if (User::where('email', $email)->exists()) {
                    $skipped++;

                    continue;
                }
                $checkNumber = $checkNumberCol !== null && isset($row[$checkNumberCol]) ? trim($row[$checkNumberCol]) : null;
                if ($checkNumber !== null && $checkNumber !== '' && User::where('check_number', $checkNumber)->exists()) {
                    $skipped++;

                    continue;
                }
                User::create([
                    'name' => $name,
                    'surname' => $surname,
                    'email' => $email,
                    'check_number' => $checkNumber ?: null,
                    'staff_id' => self::generateStaffId(),
                    'password' => Hash::make($this->temporaryPasswordFromLastName($surname)),
                    'role' => $role,
                    'must_change_password' => true,
                    'profile_completed_at' => now(),
                ]);
            }
            $created++;
        }

        if ($studentCredentials !== []) {
            if (Schema::hasTable('activity_log')) {
                ActivityLog::log('user.bulk_student_logins', null, null, count($studentCredentials).' student logins from CSV import');
            }

            return $this->downloadCredentialsCsv(
                $studentCredentials,
                'imported-student-logins-'.now()->format('Y-m-d-His').'.csv'
            );
        }

        return redirect()->route('users.index')->with('success', "Bulk upload: {$created} user(s) created, {$skipped} skipped. Staff passwords use surname from CSV (lowercase).");
    }

    /** Generate next staff ID (e.g. STF00001, STF00002). */
    public static function generateStaffId(): string
    {
        $maxNum = User::whereNotNull('staff_id')
            ->where('staff_id', 'like', 'STF%')
            ->get()
            ->max(fn ($u) => preg_match('/^STF(\d+)$/', $u->staff_id ?? '', $m) ? (int) $m[1] : 0);
        $num = ($maxNum ?: 0) + 1;

        return 'STF'.str_pad((string) $num, 5, '0', STR_PAD_LEFT);
    }

    private function findColumn(array $header, array $names): ?int
    {
        foreach ($names as $n) {
            $k = array_search($n, $header);
            if ($k !== false) {
                return (int) $k;
            }
        }

        return null;
    }

    /** @return \Illuminate\Support\Collection<int, Student> */
    private function studentsEligibleForUserAccount(array $filters = [])
    {
        return $this->studentsEligibleForUserAccountQuery($filters)->get();
    }

    /** @param  array{class_group?: string|null, intake_year?: string|null}  $filters */
    private function studentsEligibleForUserAccountQuery(array $filters = [])
    {
        $linkedNactvet = User::query()
            ->where('role', 'student')
            ->whereNotNull('nactvet_reg_no')
            ->where('nactvet_reg_no', '!=', '')
            ->pluck('nactvet_reg_no');

        $query = Student::query()
            ->with('programme')
            ->where('status', 'active')
            ->whereNotNull('nactvet_reg_no')
            ->where('nactvet_reg_no', '!=', '')
            ->whereNotIn('nactvet_reg_no', $linkedNactvet);

        if (! empty($filters['class_group'])) {
            $query->where('class_group', $filters['class_group']);
        }
        if (! empty($filters['intake_year'])) {
            $query->where('intake_year', $filters['intake_year']);
        }

        return $query
            ->orderBy('programme_id')
            ->orderBy('nta_level')
            ->orderByRaw('LOWER(last_name)')
            ->orderByRaw('LOWER(first_name)');
    }

    /** @return \Illuminate\Support\Collection<int, string> */
    private function distinctStudentFieldValues(string $column)
    {
        $linkedNactvet = User::query()
            ->where('role', 'student')
            ->whereNotNull('nactvet_reg_no')
            ->where('nactvet_reg_no', '!=', '')
            ->pluck('nactvet_reg_no');

        return Student::query()
            ->where('status', 'active')
            ->whereNotNull('nactvet_reg_no')
            ->where('nactvet_reg_no', '!=', '')
            ->whereNotIn('nactvet_reg_no', $linkedNactvet)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }

    private function studentAlreadyHasUser(Student $student): bool
    {
        return User::query()
            ->where('role', 'student')
            ->where('nactvet_reg_no', $student->nactvet_reg_no)
            ->exists();
    }

    private function ensureStudentEmail(Student $student): string
    {
        $email = trim((string) $student->email);
        if ($email !== '' && ! User::where('email', $email)->where('role', '!=', 'student')->exists()) {
            if (! User::query()
                ->where('role', 'student')
                ->where('email', $email)
                ->where('nactvet_reg_no', '!=', $student->nactvet_reg_no)
                ->exists()) {
                return $email;
            }
        }

        $domain = 'student.cohas.local';
        $local = Str::slug((string) $student->nactvet_reg_no, '') ?: Str::slug((string) $student->reg_no, '');
        if ($local === '') {
            $local = 'student'.$student->id;
        }
        $candidate = strtolower($local).'@'.$domain;
        $n = 0;
        while (
            Student::query()->where('id', '!=', $student->id)->where('email', $candidate)->exists()
            || User::query()->where('email', $candidate)->exists()
        ) {
            $n++;
            $candidate = strtolower($local).$n.'@'.$domain;
        }
        $student->update(['email' => $candidate]);

        return $candidate;
    }

    /**
     * @return array{0: User, 1: string} Plaintext password (empty when login-only).
     */
    private function createStudentUser(Student $student, bool $generatePassword = true): array
    {
        $email = $this->ensureStudentEmail($student);
        $surname = $student->singleSurname();
        $temporaryPassword = $generatePassword ? $student->portalPassword() : '';

        $user = User::create([
            'name' => $student->full_name,
            'surname' => $surname,
            'email' => $email,
            'nactvet_reg_no' => $student->nactvet_reg_no,
            'password' => Hash::make($generatePassword ? $temporaryPassword : Str::random(40)),
            'role' => 'student',
            'must_change_password' => true,
        ]);

        return [$user, $temporaryPassword];
    }

    private function temporaryPasswordFromLastName(?string ...$nameParts): string
    {
        return StudentSurname::portalPassword(...$nameParts);
    }

    private function temporaryPasswordForStudentUser(User $user): string
    {
        $student = Student::query()
            ->where('nactvet_reg_no', $user->nactvet_reg_no)
            ->first();

        if ($student) {
            return $student->portalPassword();
        }

        return $this->temporaryPasswordFromLastName($user->surname, $user->name);
    }

    private function generateTemporaryPassword(): string
    {
        return Str::password(14, true, true, false, false);
    }

    private function applyTemporaryPassword(User $user): string
    {
        $temporaryPassword = $user->isStudent()
            ? $this->temporaryPasswordForStudentUser($user)
            : $this->generateTemporaryPassword();
        $user->update([
            'password' => Hash::make($temporaryPassword),
            'must_change_password' => true,
        ]);

        return $temporaryPassword;
    }

    private function sendTemporaryPasswordEmail(User $user, string $temporaryPassword, string $loginId): bool
    {
        if (! $user->hasDeliverableEmail()) {
            return false;
        }

        try {
            Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, $loginId));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @param  list<array{name: string, reg_no: string, login: string, email: string, temporary_password: string}>  $rows
     */
    private function downloadCredentialsCsv(array $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['name', 'reg_no', 'login', 'email', 'temporary_password']);
            foreach ($rows as $row) {
                fputcsv($out, [
                    $row['name'],
                    $row['reg_no'],
                    $row['login'],
                    $row['email'],
                    $row['temporary_password'],
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function edit(User $user)
    {
        $linkedStudent = $user->isStudent() ? $user->ensureLinkedToStudentRecord() : null;

        return view('users.edit', compact('user', 'linkedStudent'));
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'check_number' => ['nullable', 'string', 'max:50', 'unique:users,check_number,'.$user->id],
            'role' => ['required', 'string', Rule::in(User::validAssignableRoles())],
        ];
        if ($user->isStudent() || User::normalizeRoleSlug($request->input('role')) === 'student') {
            $rules['nactvet_reg_no'] = ['required', 'string', 'max:50'];
        }
        $validated = $request->validate($rules);
        $validated['role'] = User::normalizeRoleSlug($validated['role']);
        if ($request->filled('password')) {
            $request->validate(['password' => ['confirmed', 'min:8']]);
            $validated['password'] = Hash::make($request->password);
        }
        if ($user->staff_id) {
            unset($validated['check_number'], $validated['nactvet_reg_no']);
        }
        if ($validated['role'] !== 'student') {
            unset($validated['nactvet_reg_no']);
        }
        $user->update($validated);
        if (Schema::hasTable('activity_log')) {
            ActivityLog::log('user.updated', User::class, $user->id, 'email: '.$user->email);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }
}
