<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AccommodationAllocationController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\GuardianAccessController;
use App\Http\Controllers\IntegrationsController;
use App\Http\Controllers\ParentPortalController;
use App\Http\Controllers\PaymentInstalmentController;
use App\Http\Controllers\TranscriptRequestController;
use App\Http\Controllers\ClinicalRotationController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\HostelController;
use App\Http\Controllers\InstitutionDocumentController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\InventoryItemController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileCompleteController;
use App\Http\Controllers\ProgrammeController;
use App\Http\Controllers\QuestionBankController;
use App\Http\Controllers\RegistrationWizardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ResultApprovalController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\ResultImportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\SemesterController;
use App\Http\Controllers\SemesterRegistrationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\UserController;
use App\Models\Payment;
use App\Models\Programme;
use App\Services\AdminDashboardService;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\Student;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login.create');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    Route::get('/register', function () {
        return redirect()->route('login.create')->with('info', 'User accounts are created by an administrator. Contact admin for access.');
    })->name('register.create');
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])->middleware('throttle:5,1')->name('password.update');
});

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware('auth')->group(function () {
    Route::get('password/change', [ChangePasswordController::class, 'create'])->name('password.change');
    Route::post('password/change', [ChangePasswordController::class, 'store'])->name('password.change.store');
    Route::get('profile/complete', [ProfileCompleteController::class, 'create'])->name('profile.complete');
    Route::post('profile/complete', [ProfileCompleteController::class, 'store'])->name('profile.complete.store');
});

Route::middleware(['auth', 'password.changed', 'profile.completed'])->group(function () {
    Route::post('profile/photo', [\App\Http\Controllers\ProfilePhotoController::class, 'update'])->name('profile.photo.update');
    Route::delete('profile/photo', [\App\Http\Controllers\ProfilePhotoController::class, 'destroy'])->name('profile.photo.destroy');
    Route::get('students/{student}/id-card', [\App\Http\Controllers\StudentCardStatusController::class, 'card'])->name('students.id-card');
    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');

    Route::get('/dashboard', function () {
        if (auth()->user()->isGuardian()) {
            return redirect()->route('parent.portal');
        }

        $academicYear = \App\Support\AcademicSession::defaultStartYear();
        $studentCount = 0;
        $programmeCount = 0;
        $paymentsToday = 0;
        $activeStudentCount = 0;
        $chartEnrollment = [];
        $chartPayments = [];
        $chartByNtaLevel = [];
        $chartByProgramme = [];
        $chartByProgrammeLevel = [];
        $chartRegistrationTrend = [];
        $chartBalance = ['cleared' => 0, 'in_arrears' => 0, 'total_arrears' => 0];
        $graduationYear = now()->year;
        $graduatedCount = 0;
        $graduationYearOptions = [];

        if (! auth()->user()->isStudent()) {
            $adminDash = app(AdminDashboardService::class)->build();
            $studentCount = $adminDash['studentCount'];
            $programmeCount = $adminDash['programmeCount'];
            $paymentsToday = $adminDash['paymentsToday'];
            $academicYear = $adminDash['academicYear'];
            $activeStudentCount = $adminDash['activeStudentCount'];
            $chartByNtaLevel = $adminDash['chartByNtaLevel'];
            $chartByProgramme = $adminDash['chartByProgramme'];
            $chartByProgrammeLevel = $adminDash['chartByProgrammeLevel'];
            $chartEnrollment = $adminDash['chartEnrollment'];
            $chartPayments = $adminDash['chartPayments'];
            $chartRegistrationTrend = $adminDash['chartRegistrationTrend'];
            $chartBalance = $adminDash['chartBalance'];
            $graduationYear = (int) request()->get('graduation_year', now()->year);
            $graduationYearOptions = \App\Models\Student::query()
                ->where('status', 'graduated')
                ->whereNotNull('graduated_at')
                ->selectRaw('YEAR(graduated_at) as y')
                ->groupBy('y')
                ->orderByDesc('y')
                ->pluck('y')
                ->map(fn ($y) => (int) $y)
                ->values()
                ->all();
            if (! in_array($graduationYear, $graduationYearOptions, true)) {
                $graduationYearOptions[] = $graduationYear;
                rsort($graduationYearOptions);
            }
            $graduatedCount = \App\Models\Student::query()
                ->where('status', 'graduated')
                ->whereDate('graduated_at', '>=', $graduationYear.'-01-01')
                ->whereDate('graduated_at', '<=', $graduationYear.'-12-31')
                ->count();
        }

        $recentPayments = auth()->user()->isStudent() && auth()->user()->student
            ? Payment::where('student_id', auth()->user()->student->id)->orderByDesc('paid_at')->get()
            : Payment::query()->with('student')->whereHas('student')->orderByDesc('paid_at')->limit(50)->get();
        $semestersForDashboard = Semester::where('is_active', true)->where('academic_year', '>=', $academicYear - 1)->orderBy('academic_year')->orderBy('number')->get();
        $registrationCounts = SemesterRegistration::whereIn('semester_id', $semestersForDashboard->pluck('id'))->selectRaw('semester_id, status, count(*) as cnt')->groupBy('semester_id', 'status')->get()->groupBy('semester_id');
        $userRole = auth()->user()->role ?? 'student';
        $announcements = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('announcements')) {
            $announcements = \App\Models\Announcement::forUser(auth()->user())
                ->orderByDesc('created_at')
                ->limit(5)
                ->get();
        }

        $studentRegistrationBadge = null;
        $studentDashboard = null;
        if (auth()->user()->isStudent() && auth()->user()->student) {
            $student = auth()->user()->student;
            $studentRegistrationBadge = $student->semesterRegistrationBadge();
            $studentDashboard = app(\App\Services\StudentDashboardService::class)->build($student);
        }

        $arrearsFollowUp = collect();
        if (! auth()->user()->isStudent() && auth()->user()->canModule('finance_payments', 'update')) {
            $arrearsFollowUp = \App\Models\Student::query()
                ->where('status', 'active')
                ->with('programme')
                ->get()
                ->filter(fn ($s) => $s->balance > 0)
                ->sortByDesc('balance')
                ->take(8)
                ->values();
        }

        $resultsPendingEntry = 0;
        if (! auth()->user()->isStudent() && auth()->user()->canModule('results', 'update')) {
            $resultsPendingEntry = \App\Models\Course::query()
                ->whereHas('semesters', fn ($q) => $q->where('semesters.academic_year', $academicYear)->where('semesters.is_active', true))
                ->whereDoesntHave('results', fn ($q) => $q->whereHas('semester', fn ($q2) => $q2->where('academic_year', $academicYear)->where('is_active', true)))
                ->count();
        }

        $publicInstitutionDocuments = collect();
        if (\Illuminate\Support\Facades\Schema::hasTable('institution_documents')) {
            $studentProgrammeId = auth()->user()->student?->programme_id;
            $publicInstitutionDocuments = \App\Models\InstitutionDocument::query()
                ->publicOnly()
                ->inStudentFolders()
                ->visibleToProgramme($studentProgrammeId)
                ->with('programme')
                ->orderByDesc('created_at')
                ->limit(8)
                ->get();
        }

        return view('dashboard', compact(
            'studentCount', 'programmeCount', 'paymentsToday', 'academicYear', 'activeStudentCount',
            'recentPayments', 'semestersForDashboard', 'registrationCounts', 'userRole', 'announcements',
            'chartEnrollment', 'chartPayments', 'chartByNtaLevel', 'chartByProgramme', 'chartByProgrammeLevel', 'chartRegistrationTrend', 'chartBalance',
            'studentRegistrationBadge', 'studentDashboard', 'publicInstitutionDocuments',
            'graduationYear', 'graduatedCount', 'graduationYearOptions',
            'arrearsFollowUp', 'resultsPendingEntry'
        ));
    })->name('dashboard');

    Route::get('calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('calendar/events/feed', [CalendarController::class, 'events'])->name('calendar.events.feed');

    Route::middleware(['not_student', 'module.permission'])->group(function () {
        // e-Office: internal document routing, open to every staff member (not gated by config/permissions.php).
        Route::get('office-documents', [\App\Http\Controllers\OfficeDocumentController::class, 'index'])->name('office-documents.index');
        Route::get('office-documents/create', [\App\Http\Controllers\OfficeDocumentController::class, 'create'])->name('office-documents.create');
        Route::post('office-documents', [\App\Http\Controllers\OfficeDocumentController::class, 'store'])->name('office-documents.store');
        Route::get('office-documents/{office_document}', [\App\Http\Controllers\OfficeDocumentController::class, 'show'])->name('office-documents.show');
        Route::get('office-documents/{office_document}/download', [\App\Http\Controllers\OfficeDocumentController::class, 'download'])->name('office-documents.download');
        Route::post('office-documents/{office_document}/mark-printed', [\App\Http\Controllers\OfficeDocumentController::class, 'markPrinted'])->name('office-documents.mark-printed');
        Route::post('office-documents/{office_document}/mark-completed', [\App\Http\Controllers\OfficeDocumentController::class, 'markCompleted'])->name('office-documents.mark-completed');

        // Staff only: semester registration (payments, official registry) after fees are paid
        Route::get('registration-wizard/start', [RegistrationWizardController::class, 'startForm'])->name('registration-wizard.start');
        Route::post('registration-wizard/start', [RegistrationWizardController::class, 'start'])->name('registration-wizard.start.store');
        Route::get('registration-wizard/{semester_registration}/step/{step}', [RegistrationWizardController::class, 'step'])->whereNumber('step')->name('registration-wizard.step');
        Route::post('registration-wizard/{semester_registration}/step/{step}', [RegistrationWizardController::class, 'saveStep'])->whereNumber('step')->name('registration-wizard.step.save');
        // Academic: tutor or admin only
        Route::middleware('tutor_or_admin')->group(function () {
            Route::get('academics', [AcademicController::class, 'index'])->name('academics.index');
            Route::post('semesters/bulk-destroy', [SemesterController::class, 'bulkDestroy'])->name('semesters.bulk-destroy');
            Route::resource('semesters', SemesterController::class)->except(['show']);
            Route::post('courses/bulk-destroy', [CourseController::class, 'bulkDestroy'])->name('courses.bulk-destroy');
            Route::resource('courses', CourseController::class)->except(['show']);
            Route::post('programmes/{programme}/nta-level-documents', [ProgrammeController::class, 'storeNtaLevelDocument'])->name('programmes.nta-level-documents.store');
            Route::delete('programmes/{programme}/nta-level-documents/{document}', [ProgrammeController::class, 'destroyNtaLevelDocument'])->name('programmes.nta-level-documents.destroy')->whereNumber('document');
            Route::post('programmes/bulk-destroy', [ProgrammeController::class, 'bulkDestroy'])->name('programmes.bulk-destroy');
            Route::resource('programmes', ProgrammeController::class)->except(['show']);
            Route::get('semester-registrations', [SemesterRegistrationController::class, 'index'])->name('semester-registrations.index');
            Route::get('semester-registrations/create', [SemesterRegistrationController::class, 'create'])->name('semester-registrations.create');
            Route::post('semester-registrations', [SemesterRegistrationController::class, 'store'])->name('semester-registrations.store');
            Route::post('semester-registrations/{semester_registration}/approve', [SemesterRegistrationController::class, 'approve'])->name('semester-registrations.approve');
            Route::post('semester-registrations/{semester_registration}/reject', [SemesterRegistrationController::class, 'reject'])->name('semester-registrations.reject');
            Route::post('semester-registrations/bulk-approve', [SemesterRegistrationController::class, 'bulkApprove'])->name('semester-registrations.bulk-approve');
            Route::post('semester-registrations/bulk-reject', [SemesterRegistrationController::class, 'bulkReject'])->name('semester-registrations.bulk-reject');
            Route::post('results/{result}/lock', [ResultController::class, 'lock'])->name('results.lock');
            Route::post('results/{result}/unlock', [ResultController::class, 'unlock'])->name('results.unlock');
            Route::get('results/import/ca', [ResultImportController::class, 'createCa'])->name('results.import.ca');
            Route::get('results/import/ca/template', [ResultImportController::class, 'downloadCaTemplate'])->name('results.import.ca.template');
            Route::get('results/import/ca/demo-excel', [ResultImportController::class, 'downloadCaDemoExcel'])->name('results.import.ca.demo-excel');
            Route::post('results/import/ca', [ResultImportController::class, 'storeCa'])->name('results.import.ca.store');
            Route::get('results/import/final', [ResultImportController::class, 'createFinal'])->name('results.import.final');
            Route::get('results/import/final/template', [ResultImportController::class, 'downloadFinalTemplate'])->name('results.import.final.template');
            Route::get('results/import/final/demo-excel', [ResultImportController::class, 'downloadFinalDemoExcel'])->name('results.import.final.demo-excel');
            Route::post('results/import/final', [ResultImportController::class, 'storeFinal'])->name('results.import.final.store');
            Route::post('results/notify-sms', [\App\Http\Controllers\ResultSmsController::class, 'notify'])->name('results.notify-sms');
            Route::get('results', [ResultController::class, 'index'])->name('results.index');
            Route::get('results/approvals', [ResultApprovalController::class, 'index'])->name('results.approvals.index');
            Route::post('results/approvals/approve', [ResultApprovalController::class, 'approve'])->name('results.approvals.approve');
            Route::post('results/approvals/reject', [ResultApprovalController::class, 'reject'])->name('results.approvals.reject');
            Route::get('exam-slots/export-docx', [\App\Http\Controllers\ExamSlotController::class, 'exportDocx'])->name('exam-slots.export-docx');
            Route::get('exam-slots/courses-for-semester', [\App\Http\Controllers\ExamSlotController::class, 'coursesForSemester'])->name('exam-slots.courses-json');
            Route::get('exam-slots', [\App\Http\Controllers\ExamSlotController::class, 'index'])->name('exam-slots.index');
            Route::get('exam-slots/create', [\App\Http\Controllers\ExamSlotController::class, 'create'])->name('exam-slots.create');
            Route::post('exam-slots', [\App\Http\Controllers\ExamSlotController::class, 'store'])->name('exam-slots.store');
            Route::get('exam-slots/{exam_slot}/edit', [\App\Http\Controllers\ExamSlotController::class, 'edit'])->name('exam-slots.edit');
            Route::put('exam-slots/{exam_slot}', [\App\Http\Controllers\ExamSlotController::class, 'update'])->name('exam-slots.update');
            Route::post('exam-slots/bulk-destroy', [\App\Http\Controllers\ExamSlotController::class, 'bulkDestroy'])->name('exam-slots.bulk-destroy');
            Route::delete('exam-slots/{exam_slot}', [\App\Http\Controllers\ExamSlotController::class, 'destroy'])->name('exam-slots.destroy');
            Route::post('timetable-slots/bulk-destroy', [\App\Http\Controllers\TimetableSlotController::class, 'bulkDestroy'])->name('timetable-slots.bulk-destroy');
            Route::delete('timetable-slots/{timetable_slot}', [\App\Http\Controllers\TimetableSlotController::class, 'destroy'])->name('timetable-slots.destroy');
            Route::get('clinical-rotations', [ClinicalRotationController::class, 'index'])->name('clinical-rotations.index');
            Route::get('clinical-rotations/create', [ClinicalRotationController::class, 'create'])->name('clinical-rotations.create');
            Route::post('clinical-rotations', [ClinicalRotationController::class, 'store'])->name('clinical-rotations.store');
            Route::get('clinical-rotations/schedule-template/export', [ClinicalRotationController::class, 'exportScheduleStandalone'])->name('clinical-rotations.schedule.export.standalone');
            Route::get('clinical-rotations/{clinical_rotation_round}', [ClinicalRotationController::class, 'show'])->name('clinical-rotations.show');
            Route::delete('clinical-rotations/{clinical_rotation_round}', [ClinicalRotationController::class, 'destroy'])->name('clinical-rotations.destroy');
            Route::post('clinical-rotations/bulk-destroy', [ClinicalRotationController::class, 'bulkDestroy'])->name('clinical-rotations.bulk-destroy');
            Route::post('clinical-rotations/{clinical_rotation_round}/regenerate', [ClinicalRotationController::class, 'regenerate'])->name('clinical-rotations.regenerate');
            Route::put('clinical-rotations/{clinical_rotation_round}/groups/{clinical_rotation_group}', [ClinicalRotationController::class, 'updateGroup'])->name('clinical-rotations.groups.update');
            Route::get('clinical-rotations/{clinical_rotation_round}/roster/print', [ClinicalRotationController::class, 'printRoster'])->name('clinical-rotations.roster.print');
            Route::get('clinical-rotations/{clinical_rotation_round}/roster-all-weeks/print', [ClinicalRotationController::class, 'printFullRoster'])->name('clinical-rotations.roster.all-weeks.print');
            Route::get('clinical-rotations/{clinical_rotation_round}/roster-all-weeks/csv', [ClinicalRotationController::class, 'downloadFullRosterCsv'])->name('clinical-rotations.roster.all-weeks.csv');
            Route::get('clinical-rotations/{clinical_rotation_round}/schedule-print', [ClinicalRotationController::class, 'printScheduleTemplate'])->name('clinical-rotations.schedule.print');
            Route::get('clinical-rotations/{clinical_rotation_round}/schedule-export', [ClinicalRotationController::class, 'exportScheduleRound'])->name('clinical-rotations.schedule.export');
            Route::get('clinical-rotations/{clinical_rotation_round}/roster-csv', [ClinicalRotationController::class, 'downloadRosterCsv'])->name('clinical-rotations.roster.csv');
            Route::get('clinical-rotations/{clinical_rotation_round}/attendance-csv', [ClinicalRotationController::class, 'downloadRoundAttendanceCsv'])->name('clinical-rotations.attendance.round-csv');
            Route::get('clinical-rotations/{clinical_rotation_round}/groups/{clinical_rotation_group}/attendance', [ClinicalRotationController::class, 'editAttendance'])->name('clinical-rotations.attendance.edit');
            Route::get('clinical-rotations/{clinical_rotation_round}/groups/{clinical_rotation_group}/attendance-csv', [ClinicalRotationController::class, 'downloadGroupAttendanceCsv'])->name('clinical-rotations.attendance.group-csv');
            Route::post('clinical-rotations/{clinical_rotation_round}/groups/{clinical_rotation_group}/attendance', [ClinicalRotationController::class, 'storeAttendance'])->name('clinical-rotations.attendance.store');

            Route::get('clinical-procedures', [\App\Http\Controllers\ClinicalProcedureController::class, 'index'])->name('clinical-procedures.index');
            Route::get('clinical-procedures/create', [\App\Http\Controllers\ClinicalProcedureController::class, 'create'])->name('clinical-procedures.create');
            Route::post('clinical-procedures', [\App\Http\Controllers\ClinicalProcedureController::class, 'store'])->name('clinical-procedures.store');
            Route::get('clinical-procedures/{clinical_procedure}/edit', [\App\Http\Controllers\ClinicalProcedureController::class, 'edit'])->name('clinical-procedures.edit');
            Route::put('clinical-procedures/{clinical_procedure}', [\App\Http\Controllers\ClinicalProcedureController::class, 'update'])->name('clinical-procedures.update');

            Route::get('clinical-logbook/framework', [\App\Http\Controllers\ClinicalLogbookReviewController::class, 'framework'])->name('clinical.framework');
            Route::get('clinical-logbook', [\App\Http\Controllers\ClinicalLogbookReviewController::class, 'index'])->name('clinical-logbook.index');
            Route::get('clinical-logbook/students/{student}', [\App\Http\Controllers\ClinicalLogbookReviewController::class, 'studentProgress'])->name('clinical-logbook.student');
            Route::get('clinical-logbook/{clinical_logbook_entry}', [\App\Http\Controllers\ClinicalLogbookReviewController::class, 'show'])->name('clinical-logbook.show');
            Route::post('clinical-logbook/{clinical_logbook_entry}/approve', [\App\Http\Controllers\ClinicalLogbookReviewController::class, 'approve'])->name('clinical-logbook.approve');
            Route::post('clinical-logbook/{clinical_logbook_entry}/reject', [\App\Http\Controllers\ClinicalLogbookReviewController::class, 'reject'])->name('clinical-logbook.reject');

            Route::get('clinical-coordinator', [\App\Http\Controllers\ClinicalCoordinatorController::class, 'dashboard'])->name('clinical.coordinator');
            Route::get('clinical-reports', [\App\Http\Controllers\ClinicalCoordinatorController::class, 'reports'])->name('clinical.reports');
            Route::get('clinical-export/logbook', [\App\Http\Controllers\ClinicalCoordinatorController::class, 'exportLogbookCsv'])->name('clinical.export.logbook');
            Route::get('clinical-progression', [\App\Http\Controllers\ClinicalProgressionController::class, 'index'])->name('clinical.progression.index');
            Route::post('clinical-progression/students/{student}', [\App\Http\Controllers\ClinicalProgressionController::class, 'store'])->name('clinical.progression.store');
            Route::post('clinical-remediation/students/{student}', [\App\Http\Controllers\ClinicalRemediationController::class, 'store'])->name('clinical.remediation.store');
            Route::post('clinical-remediation/{clinical_remediation_plan}/complete', [\App\Http\Controllers\ClinicalRemediationController::class, 'complete'])->name('clinical.remediation.complete');
            Route::get('clinical-print/students/{student}', [\App\Http\Controllers\ClinicalCoordinatorController::class, 'printStudentLogbook'])->name('clinical.print.student');

            Route::get('student-attendance', [\App\Http\Controllers\StudentAttendanceController::class, 'index'])->name('student-attendance.index');
            Route::get('student-attendance/import', [\App\Http\Controllers\StudentAttendanceController::class, 'createImport'])->name('student-attendance.import');
            Route::post('student-attendance/import', [\App\Http\Controllers\StudentAttendanceController::class, 'storeImport'])->name('student-attendance.import.store');
            Route::get('student-attendance/import/template', [\App\Http\Controllers\StudentAttendanceController::class, 'downloadTemplate'])->name('student-attendance.import.template');
            Route::get('student-attendance/mapping', [\App\Http\Controllers\StudentAttendanceController::class, 'mappingForm'])->name('student-attendance.mapping');
            Route::post('student-attendance/mapping', [\App\Http\Controllers\StudentAttendanceController::class, 'mappingStore'])->name('student-attendance.mapping.store');
            Route::get('student-attendance/{student}', [\App\Http\Controllers\StudentAttendanceController::class, 'show'])->name('student-attendance.show');

            Route::get('timetable-slots', [\App\Http\Controllers\TimetableSlotController::class, 'index'])->name('timetable-slots.index');
            Route::get('timetable-slots/create', [\App\Http\Controllers\TimetableSlotController::class, 'create'])->name('timetable-slots.create');
            Route::post('timetable-slots', [\App\Http\Controllers\TimetableSlotController::class, 'store'])->name('timetable-slots.store');

            Route::get('question-bank', [QuestionBankController::class, 'index'])->name('question-bank.index');
            Route::post('question-bank', [QuestionBankController::class, 'storeBank'])->name('question-bank.store');
            Route::get('question-bank/{questionBank}', [QuestionBankController::class, 'show'])->name('question-bank.show');
            Route::post('question-bank/{questionBank}/materials', [QuestionBankController::class, 'uploadMaterial'])->name('question-bank.materials.store');
            Route::post('question-bank/{questionBank}/generate', [QuestionBankController::class, 'generate'])->name('question-bank.generate');
            Route::post('question-bank/{questionBank}/generate-all', [QuestionBankController::class, 'generateAllSections'])->name('question-bank.generate-all');
            Route::post('question-bank/{questionBank}/reset-section', [QuestionBankController::class, 'resetSectionQuestions'])->name('question-bank.reset-section');
            Route::post('question-bank/{questionBank}/exams', [QuestionBankController::class, 'createExam'])->name('question-bank.exams.store');
            Route::get('question-bank/{questionBank}/exams/{examPaper}', [QuestionBankController::class, 'showExam'])->name('question-bank.exams.show');
            Route::get('question-bank/{questionBank}/exams/{examPaper}/export-docx', [QuestionBankController::class, 'exportDocx'])->name('question-bank.exams.export-docx');
            Route::delete('question-bank/{questionBank}/exams/{examPaper}', [QuestionBankController::class, 'destroyExam'])->name('question-bank.exams.destroy');
            Route::post('question-bank/{questionBank}/exams/bulk-destroy', [QuestionBankController::class, 'bulkDestroyExams'])->name('question-bank.exams.bulk-destroy');
            Route::post('question-bank/{questionBank}/purge-unused-ai-questions', [QuestionBankController::class, 'purgeUnusedAiQuestions'])->name('question-bank.purge-unused-ai');
        });

        // Finance: bursar/accountant or admin only
        Route::middleware('bursar_or_admin')->group(function () {
            Route::post('fee-structures/bulk-destroy', [FeeStructureController::class, 'bulkDestroy'])->name('fee-structures.bulk-destroy');
            Route::resource('fee-structures', FeeStructureController::class)->except(['show']);
            Route::resource('payments', PaymentController::class)->only(['index', 'show']);
            Route::get('payments/{payment}/reverse', [PaymentController::class, 'reverseForm'])->name('payments.reverse.form');
            Route::post('payments/{payment}/reverse', [PaymentController::class, 'reverse'])->name('payments.reverse');
            Route::post('students/{student}/ledger/charge', [StudentController::class, 'ledgerCharge'])->name('students.ledger.charge');
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
            Route::get('reports/enrollment', [ReportController::class, 'enrollment'])->name('reports.enrollment');
            Route::get('reports/enrollment/export', [ReportController::class, 'enrollmentExport'])->name('reports.enrollment.export');
            Route::get('reports/arrears', [ReportController::class, 'arrears'])->name('reports.arrears');
            Route::get('reports/arrears/export', [ReportController::class, 'arrearsExport'])->name('reports.arrears.export');
            Route::get('reports/income', [ReportController::class, 'income'])->name('reports.income');
            Route::get('reports/income/export', [ReportController::class, 'incomeExport'])->name('reports.income.export');
            Route::get('reports/payment-by-programme', [ReportController::class, 'paymentByProgramme'])->name('reports.payment-by-programme');
            Route::get('reports/class-list', [ReportController::class, 'classList'])->name('reports.class-list');
            Route::get('reports/class-list/export', [ReportController::class, 'classListExport'])->name('reports.class-list.export');
            Route::get('reports/fee-collection-summary', [ReportController::class, 'feeCollectionSummary'])->name('reports.fee-collection-summary');
            Route::get('reports/admission-control-sheet', [ReportController::class, 'admissionControlSheet'])->name('reports.admission-control-sheet');
            Route::get('reports/admission-control-sheet/export', [ReportController::class, 'admissionControlSheetExport'])->name('reports.admission-control-sheet.export');
            Route::get('reports/students-on-leave', [ReportController::class, 'studentsOnLeave'])->name('reports.students-on-leave');
            Route::get('reports/academic-standing', [ReportController::class, 'academicStanding'])->name('reports.academic-standing');
            Route::get('reports/graduation-clearance', [ReportController::class, 'graduationClearance'])->name('reports.graduation-clearance');
            Route::get('reports/nactvet', [ReportController::class, 'nactvetHub'])->name('reports.nactvet-hub');
            Route::get('reports/nactvet-export', [ReportController::class, 'nactvetExport'])->name('reports.nactvet-export');
            Route::get('reports/nactvet-results-export', [ReportController::class, 'nactvetResultsExport'])->name('reports.nactvet-results-export');
            Route::get('reports/alumni-export', [ReportController::class, 'alumniExport'])->name('reports.alumni-export');
            Route::get('payment-instalments', [PaymentInstalmentController::class, 'index'])->name('payment-instalments.index');
            Route::post('payment-instalments', [PaymentInstalmentController::class, 'store'])->name('payment-instalments.store');
            Route::put('payment-instalments/{payment_instalment}', [PaymentInstalmentController::class, 'update'])->name('payment-instalments.update');
            Route::delete('payment-instalments/{payment_instalment}', [PaymentInstalmentController::class, 'destroy'])->name('payment-instalments.destroy');
        });

        // Shared: both tutor and bursar (students, documents, etc.)
        Route::get('students/import/form', [StudentController::class, 'importForm'])->name('students.import');
        Route::post('students/import', [StudentController::class, 'importStore'])->name('students.import.store');
        Route::get('students/import-admitted/form', [StudentController::class, 'importAdmittedForm'])->name('students.import-admitted');
        Route::post('students/import-admitted', [StudentController::class, 'importAdmittedStore'])->name('students.import-admitted.store');
        Route::get('reports/admission-control-sheet/student/{student}', [ReportController::class, 'admissionControlSheetStudent'])->name('reports.admission-control-sheet.student');
        Route::post('students/{student}/guardian-access', [GuardianAccessController::class, 'store'])->name('students.guardian-access.store');
        Route::delete('students/{student}/guardian-access', [GuardianAccessController::class, 'destroy'])->name('students.guardian-access.destroy');
        Route::get('transcript-requests', [TranscriptRequestController::class, 'index'])->name('transcript-requests.index');
        Route::patch('transcript-requests/{transcript_request}', [TranscriptRequestController::class, 'update'])->name('transcript-requests.update');
        Route::post('students/{student}/documents', [\App\Http\Controllers\StudentDocumentController::class, 'store'])->name('students.documents.store');
        Route::get('student-documents/{student_document}/download', [\App\Http\Controllers\StudentDocumentController::class, 'download'])->name('student-documents.download');
        Route::delete('student-documents/{student_document}', [\App\Http\Controllers\StudentDocumentController::class, 'destroy'])->name('student-documents.destroy');
        Route::resource('students', StudentController::class)->except(['show']);
        Route::post('students/bulk-sms', [StudentController::class, 'bulkSms'])->name('students.bulk-sms');
        Route::get('search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search.index');
        Route::get('leave-applications', [\App\Http\Controllers\LeaveApplicationController::class, 'index'])->name('leave-applications.index');
        Route::get('leave-applications/create', [\App\Http\Controllers\LeaveApplicationController::class, 'create'])->name('leave-applications.create');
        Route::post('leave-applications', [\App\Http\Controllers\LeaveApplicationController::class, 'store'])->name('leave-applications.store');
        Route::post('leave-applications/{leave_application}/approve', [\App\Http\Controllers\LeaveApplicationController::class, 'approve'])->name('leave-applications.approve');
        Route::post('leave-applications/{leave_application}/reject', [\App\Http\Controllers\LeaveApplicationController::class, 'reject'])->name('leave-applications.reject');
        Route::get('graduation-clearances', [\App\Http\Controllers\GraduationClearanceController::class, 'index'])->name('graduation-clearances.index');
        Route::post('graduation-clearances', [\App\Http\Controllers\GraduationClearanceController::class, 'store'])->name('graduation-clearances.store');
        Route::get('graduation-clearances/{graduation_clearance}/edit', [\App\Http\Controllers\GraduationClearanceController::class, 'edit'])->name('graduation-clearances.edit');
        Route::put('graduation-clearances/{graduation_clearance}', [\App\Http\Controllers\GraduationClearanceController::class, 'update'])->name('graduation-clearances.update');
        Route::get('certificate-collections', [\App\Http\Controllers\CertificateCollectionController::class, 'index'])->name('certificate-collections.index');
        Route::get('certificate-collections/create', [\App\Http\Controllers\CertificateCollectionController::class, 'create'])->name('certificate-collections.create');
        Route::post('certificate-collections', [\App\Http\Controllers\CertificateCollectionController::class, 'store'])->name('certificate-collections.store');
        Route::delete('certificate-collections/{certificate_collection}', [\App\Http\Controllers\CertificateCollectionController::class, 'destroy'])->name('certificate-collections.destroy');

        Route::get('student-card-status', [\App\Http\Controllers\StudentCardStatusController::class, 'index'])->name('student-card-status.index');
        Route::post('student-card-status/bulk-update', [\App\Http\Controllers\StudentCardStatusController::class, 'bulkUpdate'])->name('student-card-status.bulk-update');
        Route::put('student-card-status/{student}', [\App\Http\Controllers\StudentCardStatusController::class, 'update'])->name('student-card-status.update');
        Route::get('conduct-records', [\App\Http\Controllers\ConductRecordController::class, 'index'])->name('conduct-records.index');
        Route::get('conduct-records/create', [\App\Http\Controllers\ConductRecordController::class, 'create'])->name('conduct-records.create');
        Route::post('conduct-records', [\App\Http\Controllers\ConductRecordController::class, 'store'])->name('conduct-records.store');
        Route::get('conduct-records/{conduct_record}/medical-form', [\App\Http\Controllers\ConductRecordController::class, 'downloadMedicalForm'])->name('conduct-records.medical-form');
        Route::get('message-logs', [\App\Http\Controllers\MessageLogController::class, 'index'])->name('message-logs.index');
        Route::get('message-logs/create', [\App\Http\Controllers\MessageLogController::class, 'create'])->name('message-logs.create');
        Route::post('message-logs', [\App\Http\Controllers\MessageLogController::class, 'store'])->name('message-logs.store');
        Route::post('message-logs/test-sms', [\App\Http\Controllers\MessageLogController::class, 'sendTest'])->name('message-logs.test-sms');
        Route::post('announcements/bulk-destroy', [AnnouncementController::class, 'bulkDestroy'])->name('announcements.bulk-destroy');
        Route::resource('announcements', AnnouncementController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
        Route::post('hostels/bulk-destroy', [HostelController::class, 'bulkDestroy'])->name('hostels.bulk-destroy');
        Route::resource('hostels', HostelController::class)->except(['show']);
        Route::post('hostels/{hostel}/generate-rooms', [HostelController::class, 'generateRooms'])->name('hostels.generate-rooms');
        Route::get('rooms/occupancy', [RoomController::class, 'occupancy'])->name('rooms.occupancy');
        Route::get('rooms/occupancy/live-data', [RoomController::class, 'occupancyLiveData'])->name('rooms.occupancy.live-data');
        Route::post('rooms/bulk-destroy', [RoomController::class, 'bulkDestroy'])->name('rooms.bulk-destroy');
        Route::resource('rooms', RoomController::class)->except(['show']);
        Route::post('accommodation-allocations/{accommodation_allocation}/end', [AccommodationAllocationController::class, 'end'])->name('accommodation-allocations.end');
        Route::get('accommodation-allocations/room-options', [AccommodationAllocationController::class, 'roomOptions'])->name('accommodation-allocations.room-options');
        Route::resource('accommodation-allocations', AccommodationAllocationController::class)->only(['index', 'create', 'store', 'edit', 'update']);
        Route::post('inventory-items/bulk-destroy', [InventoryItemController::class, 'bulkDestroy'])->name('inventory-items.bulk-destroy');
        Route::resource('inventory-items', InventoryItemController::class)->except(['show']);
        // Read-only, gated by the "system" module permission (Principal/VP oversight per RBAC policy)
        // rather than the hard admin-only group below — everything that mutates data stays admin-only.
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity-log.index');
        Route::middleware('admin')->group(function () {
            Route::get('users/role-permissions', [UserController::class, 'rolePermissions'])->name('users.role-permissions');
            Route::get('users/role-permissions/matrix', [UserController::class, 'roleMatrix'])->name('users.role-matrix');
            Route::get('users/{user}/permissions', [UserController::class, 'permissions'])->name('users.permissions');
            Route::post('users/{user}/permissions', [UserController::class, 'permissionsStore'])->name('users.permissions.store');
            Route::delete('users/{user}/permissions/{user_module_permission}', [UserController::class, 'permissionsDestroy'])->name('users.permissions.destroy');
            Route::get('users/import', [UserController::class, 'importForm'])->name('users.import');
            Route::post('users/import', [UserController::class, 'importStore'])->name('users.import.store');
            Route::post('users/students/{student}/create-login', [UserController::class, 'createStudentLogin'])->name('users.create-student-login');
            Route::post('users/create-all-student-logins', [UserController::class, 'createAllStudentLogins'])->name('users.create-all-student-logins');
            Route::post('users/issue-all-student-passwords', [UserController::class, 'issueAllStudentPasswords'])->name('users.issue-all-student-passwords');
            Route::post('users/{user}/issue-temporary-password', [UserController::class, 'issueTemporaryPassword'])->name('users.issue-temporary-password');
            Route::resource('users', UserController::class)->only(['create', 'store', 'edit', 'update']);
            Route::get('activity-log/export', [ActivityLogController::class, 'export'])->name('activity-log.export');
            Route::get('export', [ReportController::class, 'export'])->name('export.index');
            Route::get('export/run', [ReportController::class, 'exportRun'])->middleware('throttle:10,1')->name('export.run');
            Route::get('integrations', [IntegrationsController::class, 'index'])->name('integrations.index');
            Route::get('trash', [\App\Http\Controllers\TrashController::class, 'index'])->name('trash.index');
            Route::post('trash/{type}/{id}/restore', [\App\Http\Controllers\TrashController::class, 'restore'])->whereNumber('id')->name('trash.restore');
            Route::delete('trash/{type}/{id}', [\App\Http\Controllers\TrashController::class, 'forceDelete'])->whereNumber('id')->name('trash.force-delete');
            Route::get('maintenance-mode', [MaintenanceController::class, 'edit'])->name('maintenance.edit');
            Route::put('maintenance-mode', [MaintenanceController::class, 'update'])->name('maintenance.update');
        });
        Route::post('calendar/holidays/activate', [CalendarController::class, 'activateCatalog'])->name('calendar.holidays.activate');
        Route::post('calendar/events', [CalendarController::class, 'storeEvent'])->name('calendar.events.store');
        Route::delete('calendar/events/{calendar_event}', [CalendarController::class, 'destroyEvent'])->name('calendar.events.destroy');

        Route::get('institution-documents', [InstitutionDocumentController::class, 'index'])->name('institution-documents.index');
        Route::get('institution-documents/create', [InstitutionDocumentController::class, 'create'])->name('institution-documents.create');
        Route::post('institution-documents', [InstitutionDocumentController::class, 'store'])->name('institution-documents.store');
        Route::get('institution-documents/{institution_document}/edit', [InstitutionDocumentController::class, 'edit'])->name('institution-documents.edit');
        Route::put('institution-documents/{institution_document}', [InstitutionDocumentController::class, 'update'])->name('institution-documents.update');
        Route::post('institution-documents/bulk-destroy', [InstitutionDocumentController::class, 'bulkDestroy'])->name('institution-documents.bulk-destroy');
        Route::delete('institution-documents/{institution_document}', [InstitutionDocumentController::class, 'destroy'])->name('institution-documents.destroy');
    });

    Route::get('parent-portal', [ParentPortalController::class, 'index'])->name('parent.portal');
    Route::get('college-documents', [InstitutionDocumentController::class, 'studentIndex'])->name('college-documents.index');
    Route::get('institution-documents/{institution_document}/download', [InstitutionDocumentController::class, 'download'])->name('institution-documents.download');
    Route::post('transcript-requests', [TranscriptRequestController::class, 'store'])->name('transcript-requests.store');

    Route::get('payments/{payment}/receipt', [PaymentController::class, 'receipt'])->name('payments.receipt');
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('students/{student}/ledger', [StudentController::class, 'ledger'])->name('students.ledger');
    Route::get('results/my-results', [ResultController::class, 'studentPortal'])->name('results.portal');
    Route::get('results/transcript', [ResultController::class, 'transcript'])->name('results.transcript');
    Route::get('results/transcript/{student}', [ResultController::class, 'transcriptShow'])->name('results.transcript.show');
    Route::get('results/transcript/{student}/print', [ResultController::class, 'transcriptPrint'])->name('results.transcript.print');
    Route::get('my-registrations', [SemesterRegistrationController::class, 'myRegistrations'])->name('my.registrations');
    Route::get('my-accommodation', function () {
        $student = auth()->user()->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }
        $allocation = \App\Models\AccommodationAllocation::with('room.hostel')->where('student_id', $student->id)->where('status', 'active')->first();

        return view('students.my-accommodation', compact('student', 'allocation'));
    })->name('my.accommodation');
    Route::get('my-exams', function (\Illuminate\Http\Request $request) {
        $student = auth()->user()->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }
        $semesterIds = $student->semesterRegistrations()->where('status', 'approved')->pluck('semester_id');
        $semesters = \App\Models\Semester::whereIn('id', $semesterIds)->orWhere('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $semesterId = $request->get('semester_id', $semesters->first()?->id);
        $slots = \App\Models\ExamSlot::with(['semester', 'course.programme'])
            ->whereIn('semester_id', $semesterIds)
            ->when($student->programme_id, fn ($q) => $q->whereHas('course', fn ($c) => $c->where('programme_id', $student->programme_id)))
            ->when($semesterId, fn ($q) => $q->where('semester_id', $semesterId))
            ->orderBy('exam_date')
            ->orderBy('start_time')
            ->get();

        return view('exam-slots.my-exams', compact('slots', 'semesters', 'semesterId'));
    })->name('my.exams');
    Route::get('my-clinical-placement', [\App\Http\Controllers\StudentClinicalController::class, 'placement'])->name('my.clinical.placement');
    Route::get('my-clinical-logbook', [\App\Http\Controllers\StudentClinicalController::class, 'logbookIndex'])->name('my.clinical.logbook.index');
    Route::get('my-clinical-logbook/print', [\App\Http\Controllers\StudentClinicalController::class, 'printLogbook'])->name('my.clinical.logbook.print');
    Route::get('my-clinical-logbook/create', [\App\Http\Controllers\StudentClinicalController::class, 'logbookCreate'])->name('my.clinical.logbook.create');
    Route::post('my-clinical-logbook', [\App\Http\Controllers\StudentClinicalController::class, 'logbookStore'])->name('my.clinical.logbook.store');
    Route::get('my-clinical-logbook/{clinical_logbook_entry}', [\App\Http\Controllers\StudentClinicalController::class, 'logbookShow'])->name('my.clinical.logbook.show');
    Route::get('my-clinical-logbook/{clinical_logbook_entry}/edit', [\App\Http\Controllers\StudentClinicalController::class, 'logbookEdit'])->name('my.clinical.logbook.edit');
    Route::put('my-clinical-logbook/{clinical_logbook_entry}', [\App\Http\Controllers\StudentClinicalController::class, 'logbookUpdate'])->name('my.clinical.logbook.update');
    Route::post('my-clinical-logbook/{clinical_logbook_entry}/submit', [\App\Http\Controllers\StudentClinicalController::class, 'logbookSubmit'])->name('my.clinical.logbook.submit');
    Route::get('my-clinical-remediation', [\App\Http\Controllers\StudentClinicalController::class, 'remediationIndex'])->name('my.clinical.remediation');

    Route::get('my-module-registration', [\App\Http\Controllers\StudentModuleEnrollmentController::class, 'edit'])->name('my.module-registration');
    Route::put('my-module-registration', [\App\Http\Controllers\StudentModuleEnrollmentController::class, 'update'])->name('my.module-registration.update');
    Route::get('my-modules', function (\Illuminate\Http\Request $request) {
        $student = auth()->user()->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }

        $academicYearStart = \App\Support\AcademicSession::resolveStartYear(
            $request->filled('academic_year') ? $request->integer('academic_year') : null
        );

        $catalogService = app(\App\Services\StudentModuleCatalogService::class);
        $catalog = $catalogService->catalogForStudent($student, $academicYearStart);

        $academicYearOptions = \App\Models\Semester::academicYearOptionsForForms();

        return view('timetable-slots.my-modules', compact('student', 'catalog', 'academicYearStart', 'academicYearOptions'));
    })->name('my.modules');
    Route::get('my-timetable', function (\Illuminate\Http\Request $request) {
        $student = auth()->user()->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }

        $student->load('programme');

        $academicYearStart = \App\Support\AcademicSession::resolveStartYear(
            $request->filled('academic_year') ? $request->integer('academic_year') : null
        );

        $catalogService = app(\App\Services\StudentModuleCatalogService::class);
        $timetable = $catalogService->timetableByTerm($student, $academicYearStart);

        $academicYearOptions = \App\Models\Semester::academicYearOptionsForForms();

        return view('timetable-slots.my-timetable', compact('student', 'timetable', 'academicYearStart', 'academicYearOptions'));
    })->name('my.timetable');
    Route::get('my-assessments', [ResultController::class, 'studentAssessments'])->name('my.assessments');
    Route::get('my-module-results', [ResultController::class, 'studentModuleResults'])->name('my.module-results');
});
