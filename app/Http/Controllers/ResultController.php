<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Result;
use App\Models\ResultSemesterSummary;
use App\Models\Semester;
use App\Models\Student;
use App\Services\StudentModuleResultsService;
use Illuminate\Http\Request;

class ResultController extends Controller
{
    public function index(Request $request)
    {
        $hodProgrammeId = auth()->user()->hodProgrammeId();

        $query = Result::query()
            ->with(['student.programme', 'course', 'semester'])
            ->whereHas('student')
            ->when($hodProgrammeId, fn ($q, $pid) => $q->whereHas('student', fn ($sq) => $sq->where('programme_id', $pid)))
            ->orderByDesc('semester_id')
            ->orderBy('student_id')
            ->orderBy(Course::select('code')->whereColumn('id', 'results.course_id'));

        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
        }
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        $results = $query->paginate(20)->withQueryString();
        $semesters = Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();
        $students = Student::where('status', 'active')
            ->when($hodProgrammeId, fn ($q, $pid) => $q->where('programme_id', $pid))
            ->orderBy('reg_no')->get(['id', 'reg_no', 'first_name', 'last_name']);

        return view('results.index', compact('results', 'semesters', 'students'));
    }

    public function transcript(Request $request)
    {
        if (auth()->user()->isStudent() && auth()->user()->student) {
            return redirect()->route('results.portal');
        }
        $studentId = $request->get('student_id');
        if (! $studentId) {
            $students = Student::with('programme')->where('status', 'active')->orderBy('reg_no')->get();

            return view('results.transcript-select', compact('students'));
        }
        $student = Student::with('programme')->findOrFail($studentId);
        $results = Result::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->orderBy('semester_id')
            ->orderBy(Course::select('code')->whereColumn('id', 'results.course_id'))
            ->get()
            ->groupBy('semester_id');
        $summaries = ResultSemesterSummary::where('student_id', $student->id)
            ->whereIn('semester_id', $results->keys())
            ->get()
            ->keyBy('semester_id');

        return view('results.transcript', compact('student', 'results', 'summaries'));
    }

    public function lock(Result $result)
    {
        $result->update(['is_locked' => true]);

        return redirect()->route('results.index')->with('success', 'Result locked.');
    }

    public function unlock(Result $result)
    {
        $result->update(['is_locked' => false]);

        return redirect()->route('results.index')->with('success', 'Result unlocked.');
    }

    public function transcriptShow(Student $student)
    {
        if (auth()->user()->isStudent() && (! auth()->user()->student || auth()->user()->student->id !== $student->id)) {
            abort(403, 'You can only view your own transcript.');
        }
        if (auth()->user()->isGuardian() && auth()->user()->linked_student_id !== $student->id) {
            abort(403);
        }
        if (auth()->user()->isStudent() && $student->balance > 0) {
            return redirect()->route('dashboard')->with('error', 'You must clear your fee balance to view results. Current balance: '.number_format($student->balance, 0).' TZS. Please pay the required amount for the semester.');
        }
        $student->load('programme');
        $resultsQuery = Result::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->orderBy('semester_id')
            ->orderBy(Course::select('code')->whereColumn('id', 'results.course_id'));
        if (auth()->user()->isStudent() || auth()->user()->isGuardian()) {
            $resultsQuery->approved();
        }
        $results = $resultsQuery->get()->groupBy('semester_id');
        $summaries = ResultSemesterSummary::where('student_id', $student->id)
            ->whereIn('semester_id', $results->keys())
            ->get()
            ->keyBy('semester_id');

        return view('results.transcript', compact('student', 'results', 'summaries'));
    }

    public function studentAssessments()
    {
        $student = auth()->user()->student;
        if (! $student) {
            abort(403);
        }
        if (auth()->user()->isStudent() && $student->balance > 0) {
            return redirect()->route('dashboard')->with('error', 'You must clear your fee balance to view assessments. Current balance: '.number_format($student->balance, 0).' TZS.');
        }
        $student->load('programme');

        // Only gate visibility by registration/payment for students whose registration history
        // is actually tracked here — legacy/imported students with no SemesterRegistration rows
        // keep seeing whatever approved results they already had, unfiltered.
        $gateByRegistration = $student->hasAnySemesterRegistrationTracked();
        $registeredSemesterIds = $gateByRegistration ? $student->registeredCompleteSemesterIds() : null;

        $semestersWithResults = Semester::query()
            ->when($gateByRegistration, fn ($q) => $q->whereIn('id', $registeredSemesterIds))
            ->whereHas('results', fn ($q) => $q->where('student_id', $student->id)->approved())
            ->with(['results' => fn ($q) => $q->where('student_id', $student->id)->approved()->with('course')->orderBy(Course::select('code')->whereColumn('id', 'results.course_id'))])
            ->orderBy('academic_year')
            ->orderBy('number')
            ->get();

        $distinctYears = $semestersWithResults->pluck('academic_year')->unique()->sort()->values();
        $yearOrdinals = [];
        foreach ($distinctYears as $idx => $y) {
            $yearOrdinals[$y] = $this->ordinalYear($idx + 1);
        }

        $sections = $semestersWithResults->map(function (Semester $semester) use ($yearOrdinals) {
            $periodLabel = match ((int) $semester->number) {
                Semester::PERIOD_FIRST => 'Semester One',
                Semester::PERIOD_SECOND => 'Semester Two',
                default => 'Semester '.$semester->number,
            };

            return [
                'semester' => $semester,
                'title' => ($yearOrdinals[$semester->academic_year] ?? 'Year').' - '.$periodLabel,
                'academic_year_label' => $semester->academicYearRange(),
                'results' => $semester->results,
            ];
        })->values();

        return view('results.student-assessments', compact('student', 'sections'));
    }

    public function studentModuleResults(StudentModuleResultsService $moduleResults)
    {
        $student = auth()->user()->student;
        if (! $student) {
            abort(403);
        }
        if (auth()->user()->isStudent() && $student->balance > 0) {
            return redirect()->route('dashboard')->with('error', 'You must clear your fee balance to view results. Current balance: '.number_format($student->balance, 0).' TZS.');
        }
        $student->load('programme');

        $yearSections = $moduleResults->yearSections($student);

        return view('results.student-module-results', compact('student', 'yearSections'));
    }

    public function studentPortal(Request $request)
    {
        if (auth()->user()->isStudent()) {
            if ($request->get('tab', 'final') === 'ca') {
                return redirect()->route('my.assessments', $request->except('tab'));
            }

            return redirect()->route('my.module-results', $request->except('tab'));
        }

        $student = auth()->user()->student;
        if (! $student) {
            abort(403);
        }
        if (auth()->user()->isStudent() && $student->balance > 0) {
            return redirect()->route('dashboard')->with('error', 'You must clear your fee balance to view results. Current balance: '.number_format($student->balance, 0).' TZS.');
        }
        $student->load('programme');

        $semestersWithResults = Semester::query()
            ->whereHas('results', fn ($q) => $q->where('student_id', $student->id)->approved())
            ->orderBy('academic_year')
            ->orderBy('number')
            ->get();

        $distinctYears = $semestersWithResults->pluck('academic_year')->unique()->sort()->values();
        $yearLabels = [];
        foreach ($distinctYears as $idx => $y) {
            $yearLabels[$y] = 'Year '.($idx + 1).' ('.$y.'/'.($y + 1).')';
        }

        $selectedYear = $request->integer('academic_year') ?: null;
        $selectedSemesterNumber = $request->integer('semester_number') ?: null;

        $numbersForYear = collect();
        if ($selectedYear) {
            $numbersForYear = $semestersWithResults->where('academic_year', $selectedYear)->pluck('number')->unique()->sort()->values();
        }

        $semester = null;
        $results = collect();
        $summary = null;
        if ($selectedYear && $selectedSemesterNumber) {
            $semester = $semestersWithResults->first(fn ($s) => $s->academic_year === $selectedYear && (int) $s->number === (int) $selectedSemesterNumber);
            if ($semester) {
                $results = Result::with('course')
                    ->where('student_id', $student->id)
                    ->where('semester_id', $semester->id)
                    ->approved()
                    ->orderBy(Course::select('code')->whereColumn('id', 'results.course_id'))
                    ->get();
                $summary = ResultSemesterSummary::where('student_id', $student->id)->where('semester_id', $semester->id)->first();
            }
        }

        return view('results.student-portal', compact(
            'student',
            'semestersWithResults',
            'yearLabels',
            'distinctYears',
            'numbersForYear',
            'selectedYear',
            'selectedSemesterNumber',
            'semester',
            'results',
            'summary'
        ));
    }

    public function transcriptPrint(Student $student)
    {
        if (auth()->user()->isStudent() && (! auth()->user()->student || auth()->user()->student->id !== $student->id)) {
            abort(403);
        }
        if (auth()->user()->isStudent() && $student->balance > 0) {
            return redirect()->route('dashboard')->with('error', 'You must clear your fee balance to view or print results. Current balance: '.number_format($student->balance, 0).' TZS.');
        }
        $student->load('programme');
        $resultsQuery = Result::with(['course', 'semester'])
            ->where('student_id', $student->id)
            ->orderBy('semester_id')->orderBy(Course::select('code')->whereColumn('id', 'results.course_id'));
        if (auth()->user()->isStudent() || auth()->user()->isGuardian()) {
            $resultsQuery->approved();
        }
        $results = $resultsQuery->get()->groupBy('semester_id');
        $summaries = ResultSemesterSummary::where('student_id', $student->id)
            ->whereIn('semester_id', $results->keys())
            ->get()
            ->keyBy('semester_id');

        return view('results.transcript-print', compact('student', 'results', 'summaries'));
    }

    private function ordinalYear(int $n): string
    {
        $suffix = match ($n % 10) {
            1 => $n % 100 === 11 ? 'th' : 'st',
            2 => $n % 100 === 12 ? 'th' : 'nd',
            3 => $n % 100 === 13 ? 'th' : 'rd',
            default => 'th',
        };

        return $n.$suffix.' Year';
    }
}
