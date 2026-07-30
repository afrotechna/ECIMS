<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\InstitutionDocument;
use App\Models\Payment;
use App\Models\Result;
use App\Models\SemesterRegistration;
use App\Services\StudentDashboardService;
use Illuminate\Http\Request;

class ParentPortalController extends Controller
{
    public function index(Request $request, StudentDashboardService $dashboardService)
    {
        $user = $request->user();
        abort_unless($user->isGuardian(), 403);

        $student = $user->linkedStudent;
        abort_unless($student, 404, 'No student linked to this guardian account.');

        $student->load('programme');
        $studentDashboard = $dashboardService->build($student);

        $recentPayments = Payment::where('student_id', $student->id)
            ->orderByDesc('paid_at')
            ->limit(10)
            ->get();

        $registrations = SemesterRegistration::with('semester')
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $recentResults = Result::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->where('status', 'approved')
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $announcements = Announcement::query()
            ->visible()
            ->whereIn('audience', [Announcement::AUDIENCE_STUDENTS, Announcement::AUDIENCE_ALL])
            ->forStudent($student)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $publicDocuments = InstitutionDocument::query()
            ->publicOnly()
            ->inStudentFolders()
            ->visibleToProgramme($student->programme_id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return view('parent-portal.index', compact(
            'student',
            'studentDashboard',
            'recentPayments',
            'registrations',
            'recentResults',
            'announcements',
            'publicDocuments'
        ));
    }
}
