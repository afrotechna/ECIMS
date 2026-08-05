<?php

namespace App\Models;

use App\Models\Concerns\HasSoftDeleteAudit;
use App\Support\RolePermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements CanResetPasswordContract
{
    use CanResetPassword, HasFactory, Notifiable, SoftDeletes, HasSoftDeleteAudit;

    protected ?\Illuminate\Support\Collection $extraModulePermissionsCache = null;

    protected $fillable = [
        'name',
        'middle_name',
        'surname',
        'email',
        'password',
        'role',
        'linked_student_id',
        'check_number',
        'nactvet_reg_no',
        'staff_id',
        'must_change_password',
        'profile_completed_at',
        'phone',
        'profile_photo_path',
        'qualification',
        'education_level',
        'license_number',
        'license_board',
        'employment_type',
        'sex',
        'nationality',
        'region',
        'district',
        'ward',
        'street',
    ];

    /** Qualification / profession options shown on the staff profile form. */
    public const QUALIFICATIONS = [
        'clinical_officer' => 'Clinical Officer',
        'assistant_clinical_officer' => 'Assistant Clinical Officer',
        'medical_doctor' => 'Medical Doctor',
        'registered_nurse' => 'Registered Nurse',
        'enrolled_nurse' => 'Enrolled Nurse',
        'medical_lab_scientist' => 'Medical Laboratory Scientist / Technologist',
        'radiographer' => 'Radiographer',
        'pharmacist' => 'Pharmacist',
        'public_health_officer' => 'Public Health Officer',
        'environmental_health_officer' => 'Environmental Health Officer',
        'administrator_non_clinical' => 'Administrator / Non-clinical',
        'other' => 'Other',
    ];

    /** Highest level of education completed. */
    public const EDUCATION_LEVELS = [
        'certificate' => 'Certificate',
        'diploma' => 'Diploma',
        'bachelor' => "Bachelor's Degree",
        'master' => "Master's Degree",
        'doctorate' => 'Doctorate (PhD)',
        'other' => 'Other',
    ];

    /** Professional regulatory bodies staff may be registered with. */
    public const LICENSE_BOARDS = [
        'mct' => 'Medical Council of Tanganyika (MCT)',
        'tnmc' => 'Tanganyika Nursing and Midwifery Council (TNMC)',
        'pct' => 'Pharmacy Council of Tanzania (PCT)',
        'hltc' => 'Health Laboratory Technologist Council (HLTC)',
        'ahpc' => 'Allied Health Professionals Council (AHPC)',
        'other' => 'Other',
    ];

    /** Hali ya ajira / employment status. */
    public const EMPLOYMENT_TYPES = [
        'permanent' => 'Permanent',
        'part_time' => 'Part-time',
    ];

    /** Sex options shown on the staff profile form. */
    public const SEX_OPTIONS = [
        'M' => 'Male',
        'F' => 'Female',
    ];

    /** Nationality options; anything else is captured via the "Other" specify field. */
    public const NATIONALITIES = [
        'Tanzanian' => 'Tanzanian',
    ];

    /**
     * Position labels (stored in users.role). Student is excluded from staff assignment dropdowns.
     */
    public const POSITIONS = [
        'principal' => 'Principal',
        'vice_principal_afp' => 'Vice Principal — Administrative, Financial & Planning (AFP)',
        'vice_principal_arc' => 'Vice Principal — Academic, Research & Consultancy (ARC)',
        'procurement_officer' => 'Procurement Officer',
        'accountant' => 'Accountant',
        'secretary' => 'College Secretary',
        'accommodation_matron' => 'Matron',
        'hod_cmt' => 'Head of Department — Clinical Medicine (CMT)',
        'hod_mlt' => 'Head of Department — Medical Laboratory (MLT)',
        'admission_officer' => 'Admission Officer',
        'examination_officer' => 'Examination Officer',
        'qa_officer' => 'Quality Assurance Officer',
        'tutor_staff' => 'Other Academic (Tutor)',
        'clinical_instructor' => 'Clinical Instructor (Preceptor)',
        'student' => 'Student',
        'administrator' => 'Administrator',
    ];

    /** Ordered keys for “Add user” staff role dropdown (student handled separately). */
    public const STAFF_ROLE_ORDER = [
        'principal',
        'vice_principal_afp',
        'procurement_officer',
        'accountant',
        'secretary',
        'accommodation_matron',
        'vice_principal_arc',
        'hod_cmt',
        'hod_mlt',
        'admission_officer',
        'examination_officer',
        'qa_officer',
        'tutor_staff',
        'clinical_instructor',
        'administrator',
    ];

    /** Staff list table: sort by leadership / position rank, then surname, then first name. */
    public const STAFF_POSITION_SORT_ORDER = [
        'administrator',
        'principal',
        'vice_principal_afp',
        'vice_principal_arc',
        'hod_cmt',
        'hod_mlt',
        'admission_officer',
        'examination_officer',
        'procurement_officer',
        'accountant',
        'secretary',
        'accommodation_matron',
        'qa_officer',
        'tutor_staff',
        'clinical_instructor',
    ];

    /** Roles that may use curriculum / exams / results / academics menus (middleware tutor_or_admin). */
    protected const ACADEMIC_ROLES = [
        'administrator',
        'principal',
        'vice_principal_arc',
        'hod_cmt',
        'hod_mlt',
        'admission_officer',
        'examination_officer',
        'qa_officer',
        'tutor_staff',
        'clinical_instructor',
    ];

    /** Roles that may use finance / fees / cash reports (middleware bursar_or_admin). */
    protected const FINANCE_ROLES = [
        'administrator',
        'principal',
        'vice_principal_afp',
        'procurement_officer',
        'accountant',
        'secretary',
    ];

    /** Roles that may use System menu (users, activity log, export). */
    protected const SYSTEM_ADMIN_ROLES = [
        'administrator',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'profile_completed_at' => 'datetime',
        ];
    }

    public static function staffRoleOptions(): array
    {
        $out = [];
        foreach (self::STAFF_ROLE_ORDER as $key) {
            if (isset(self::POSITIONS[$key])) {
                $out[$key] = self::POSITIONS[$key];
            }
        }

        return $out;
    }

    public static function roleLabel(?string $role): string
    {
        if ($role === null || $role === '') {
            return '—';
        }
        $normalized = self::normalizeRoleSlug($role);

        return self::POSITIONS[$normalized] ?? ucfirst(str_replace('_', ' ', $role));
    }

    /** Maps legacy slugs after DB migration; keeps imports tolerant. */
    public static function normalizeRoleSlug(string $role): string
    {
        return match ($role) {
            'admin' => 'administrator',
            'account_bursar' => 'accountant',
            'procurement' => 'procurement_officer',
            'college_secretary' => 'secretary',
            'matron', 'hostel_matron' => 'accommodation_matron',
            default => $role,
        };
    }

    public static function validAssignableRoles(): array
    {
        return array_merge(['student'], array_keys(self::staffRoleOptions()));
    }

    /** Ordered options for user edit (student first, then leadership order). */
    public static function allAssignableRoleOptions(): array
    {
        return ['student' => self::POSITIONS['student']] + self::staffRoleOptions();
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    public function isGuardian(): bool
    {
        return $this->role === 'guardian';
    }

    public function isTutorStaff(): bool
    {
        return $this->role === 'tutor_staff';
    }

    /** Programmes (departments) a tutor is assigned to teach in. */
    public function programmes(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Programme::class);
    }

    public function linkedStudent()
    {
        return $this->belongsTo(Student::class, 'linked_student_id');
    }

    /** ICT / system administrator — users, audit log, data export. */
    public function isAdmin(): bool
    {
        return in_array(self::normalizeRoleSlug((string) $this->role), self::SYSTEM_ADMIN_ROLES, true);
    }

    /** Head of Department roles are scoped to one programme's data only. */
    public const HOD_PROGRAMME_CODES = [
        'hod_cmt' => 'CMT',
        'hod_mlt' => 'MLT',
    ];

    public function hodProgrammeCode(): ?string
    {
        return self::HOD_PROGRAMME_CODES[$this->role] ?? null;
    }

    /** Programme id this HOD is restricted to, or null for roles that see every programme. */
    public function hodProgrammeId(): ?int
    {
        $code = $this->hodProgrammeCode();

        return $code ? Programme::where('code', $code)->value('id') : null;
    }

    /** Roles that actually handle physical printing for e-Office documents. */
    protected const OFFICE_DOCUMENT_PRINTER_ROLES = [
        'secretary',
        'admission_officer',
        'accountant',
    ];

    /** Whether this role prints e-Office documents, vs. just reading them on screen (e.g. Principal). */
    public function canPrintOfficeDocuments(): bool
    {
        return $this->isAdmin() || in_array(self::normalizeRoleSlug((string) $this->role), self::OFFICE_DOCUMENT_PRINTER_ROLES, true);
    }

    /** Module permission: view | create | update | delete (config/permissions.php), plus any extra per-user grants. */
    public function canModule(string $module, string $action = 'view'): bool
    {
        if ($this->isStudent() || $this->isGuardian()) {
            return false;
        }

        if (RolePermissions::allows(self::normalizeRoleSlug((string) $this->role), $module, $action)) {
            return true;
        }

        $grant = $this->loadedExtraModulePermissions()->get($module);
        if (! $grant) {
            return false;
        }

        $actions = $grant->actions ?? [];

        return in_array('*', $actions, true) || in_array($action, $actions, true);
    }

    public function extraModulePermissions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserModulePermission::class);
    }

    public function sentOfficeDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OfficeDocument::class, 'sender_id');
    }

    public function receivedOfficeDocuments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(OfficeDocument::class, 'recipient_id');
    }

    /** Memoized per-request lookup of extra grants, keyed by module. */
    protected function loadedExtraModulePermissions(): \Illuminate\Support\Collection
    {
        if ($this->extraModulePermissionsCache === null) {
            $this->extraModulePermissionsCache = $this->extraModulePermissions()->get()->keyBy('module');
        }

        return $this->extraModulePermissionsCache;
    }

    /** Academic portfolio: any academic module with view access. */
    public function canAccessAcademics(): bool
    {
        if ($this->isStudent()) {
            return false;
        }

        return RolePermissions::canAny(
            self::normalizeRoleSlug((string) $this->role),
            'view',
            config('permissions.academic_modules', [])
        );
    }

    /** Finance portfolio: fees, payments, or financial reports (view). */
    public function canAccessFinance(): bool
    {
        if ($this->isStudent()) {
            return false;
        }

        return RolePermissions::canAny(
            self::normalizeRoleSlug((string) $this->role),
            'view',
            config('permissions.finance_modules', [])
        );
    }

    /** @return list<string> */
    public function permittedModules(string $action = 'view'): array
    {
        return RolePermissions::modulesWith(self::normalizeRoleSlug((string) $this->role), $action);
    }

    /** Student register row (matched by NACTVET registration number). */
    public function student()
    {
        return $this->hasOne(Student::class, 'nactvet_reg_no', 'nactvet_reg_no');
    }

    /**
     * Resolve the student register row and repair user.nactvet_reg_no / email when possible.
     */
    public function ensureLinkedToStudentRecord(): ?Student
    {
        if (! $this->isStudent()) {
            return null;
        }

        if ($this->relationLoaded('student') && $this->getRelation('student')) {
            return $this->getRelation('student');
        }

        $student = null;
        $nactvet = trim((string) $this->nactvet_reg_no);

        if ($nactvet !== '') {
            $student = Student::query()
                ->where('nactvet_reg_no', $nactvet)
                ->orWhereRaw('LOWER(TRIM(nactvet_reg_no)) = ?', [strtolower($nactvet)])
                ->first();
        }

        if (! $student) {
            $email = trim((string) $this->email);
            if ($email !== '') {
                $student = Student::query()->where('email', $email)->first();
            }
        }

        if ($student) {
            $this->syncStudentUserFields($student);
            $this->setRelation('student', $student);

            return $student;
        }

        $this->setRelation('student', null);

        return null;
    }

    private function syncStudentUserFields(Student $student): void
    {
        $updates = [];
        $studentNactvet = trim((string) $student->nactvet_reg_no);

        if ($studentNactvet !== '' && trim((string) $this->nactvet_reg_no) !== $studentNactvet) {
            $updates['nactvet_reg_no'] = $studentNactvet;
        }

        $studentEmail = trim((string) $student->email);
        if ($studentEmail !== '' && $this->email !== $studentEmail) {
            $userEmail = strtolower(trim((string) $this->email));
            if ($userEmail === '' || str_ends_with($userEmail, '@student.cohas.local')) {
                $updates['email'] = $studentEmail;
            }
        }

        if ($updates !== []) {
            $this->update($updates);
        }
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        if (empty($this->profile_photo_path)) {
            return null;
        }

        return Storage::disk('public')->exists($this->profile_photo_path)
            ? asset('storage/'.$this->profile_photo_path)
            : null;
    }

    public function getInitialsAttribute(): string
    {
        if (! $this->isStudent()) {
            $given = trim((string) $this->name);
            $family = trim((string) $this->surname);
            if ($given !== '' && $family !== '') {
                return strtoupper(substr($given, 0, 1).substr($family, 0, 1));
            }
        }

        $name = trim((string) $this->name);
        if ($name === '') {
            return strtoupper(substr($this->email ?? '?', 0, 1));
        }
        $parts = preg_split('/\s+/', $name, 2);
        if (count($parts) === 1) {
            return strtoupper(substr($parts[0], 0, 2));
        }

        return strtoupper(substr($parts[0], 0, 1).substr($parts[1], 0, 1));
    }

    /** Staff: first / given name(s) stored in users.name. */
    public function staffGivenNames(): string
    {
        return trim((string) $this->name);
    }

    /** Staff: surname / family name. */
    public function staffSurname(): string
    {
        return trim((string) ($this->surname ?? ''));
    }

    public function staffDisplayName(): string
    {
        $given = $this->staffGivenNames();
        $family = $this->staffSurname();

        if ($given !== '' && $family !== '') {
            return $given.' '.$family;
        }

        return $given !== '' ? $given : ($family !== '' ? $family : '—');
    }

    public static function staffRoleSortSqlCase(): string
    {
        $cases = [];
        foreach (self::STAFF_POSITION_SORT_ORDER as $index => $role) {
            $cases[] = "WHEN '".addslashes($role)."' THEN {$index}";
        }

        return 'CASE role '.implode(' ', $cases).' ELSE 99 END';
    }

    /** Value the user types on the login screen (not email for students). */
    public function loginIdentifier(): string
    {
        if ($this->isStudent()) {
            return trim((string) ($this->nactvet_reg_no ?: $this->email));
        }

        return trim((string) ($this->staff_id ?: $this->check_number ?: $this->email));
    }

    /** Whether outbound email is likely deliverable (real address, not a local placeholder). */
    public function hasDeliverableEmail(): bool
    {
        $email = strtolower(trim((string) $this->email));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return ! str_ends_with($email, '@student.cohas.local');
    }
}
