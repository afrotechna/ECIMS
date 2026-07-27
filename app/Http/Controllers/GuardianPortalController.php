<?php

namespace App\Http\Controllers;

use App\Models\InstitutionDocument;
use App\Models\Result;
use App\Models\Student;
use App\Support\AcademicSession;
use Illuminate\Support\Facades\DB;

class GuardianPortalController extends Controller
{
    public function dashboard()
    {
        $student = $this->linkedStudent();
        $balance = $this->studentBalance($student);
        $recentResults = Result::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->where('is_locked', true)
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('guardian.dashboard', compact('student', 'balance', 'recentResults'));
    }

    public function fees()
    {
        $student = $this->linkedStudent();
        $balance = $this->studentBalance($student);
        $instalments = $student->paymentInstalments()->orderBy('due_date')->get();
        $payments = $student->payments()->orderByDesc('paid_at')->limit(20)->get();

        return view('guardian.fees', compact('student', 'balance', 'instalments', 'payments'));
    }

    public function results()
    {
        $student = $this->linkedStudent();
        $results = Result::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->where('is_locked', true)
            ->orderByDesc('semester_id')
            ->get()
            ->groupBy(fn ($r) => $r->semester?->label ?? 'Other');

        return view('guardian.results', compact('student', 'results'));
    }

    public function documents()
    {
        $student = $this->linkedStudent();
        $documents = InstitutionDocument::query()
            ->publicOnly()
            ->inStudentFolders()
            ->visibleToProgramme($student->programme_id)
            ->orderByDesc('created_at')
            ->limit(30)
            ->get();

        return view('guardian.documents', compact('student', 'documents'));
    }

    private function linkedStudent(): Student
    {
        $user = auth()->user();
        abort_unless($user->isGuardian() && $user->guardian_of_student_id, 403);

        return Student::with('programme')->findOrFail($user->guardian_of_student_id);
    }

    private function studentBalance(Student $student): float
    {
        $row = DB::table('ledger_entries')
            ->selectRaw("
                SUM(CASE WHEN type = 'debit' THEN amount ELSE 0 END)
                - SUM(CASE WHEN type = 'credit' THEN amount ELSE 0 END) AS balance
            ")
            ->where('student_id', $student->id)
            ->first();

        return (float) ($row->balance ?? 0);
    }
}
