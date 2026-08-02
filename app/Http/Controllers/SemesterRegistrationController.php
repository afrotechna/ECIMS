<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\Student;
use Illuminate\Http\Request;

class SemesterRegistrationController extends Controller
{
    public function index(Request $request)
    {
        $query = SemesterRegistration::query()
            ->with(['student.programme', 'semester', 'approver'])
            ->whereHas('student')
            ->when(auth()->user()->hodProgrammeId(), fn ($q, $pid) => $q->whereHas('student', fn ($sq) => $sq->where('programme_id', $pid)))
            ->orderByDesc('created_at');

        // No filters submitted at all (first visit, not an explicit "All") — default to the current semester only.
        $noFilterSubmitted = ! $request->has('semester_id') && ! $request->has('academic_year') && ! $request->has('status');
        $currentSemester = $noFilterSubmitted
            ? Semester::where('is_active', true)->orderByDesc('academic_year')->orderByDesc('number')->first()
            : null;

        $selectedSemester = null;
        if ($request->filled('semester_id')) {
            $query->where('semester_id', $request->semester_id);
            $selectedSemester = Semester::find($request->semester_id);
        } elseif ($request->filled('academic_year')) {
            $query->whereHas('semester', fn ($q) => $q->where('academic_year', (int) $request->academic_year));
        } elseif ($currentSemester) {
            $query->where('semester_id', $currentSemester->id);
            $selectedSemester = $currentSemester;
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $registrations = $query->paginate(15)->withQueryString();

        $semesterFilter = Semester::query()->where('is_active', true)->orderByDesc('academic_year')->orderBy('number');
        if ($request->filled('academic_year')) {
            $semesterFilter->where('academic_year', (int) $request->academic_year);
        }
        $semesters = $semesterFilter->get();
        $academicYearOptions = Semester::academicYearOptionsForForms();

        return view('semester-registrations.index', compact('registrations', 'semesters', 'academicYearOptions', 'selectedSemester'));
    }

    public function create(Request $request)
    {
        $students = Student::with('programme')->where('status', 'active')->orderBy('reg_no')->get();
        $academicYearOptions = Semester::academicYearOptionsForForms();
        $selectedAcademicYear = $request->filled('academic_year') ? $request->integer('academic_year') : null;

        $semesters = $selectedAcademicYear !== null
            ? Semester::forAcademicYear($selectedAcademicYear, true)
            : Semester::where('is_active', true)->orderByDesc('academic_year')->orderBy('number')->get();

        $preselectedStudent = $request->get('student_id');
        $preselectedSemester = $request->get('semester_id');

        return view('semester-registrations.create', compact(
            'students',
            'semesters',
            'preselectedStudent',
            'preselectedSemester',
            'academicYearOptions',
            'selectedAcademicYear',
        ));
    }

    public function myRegistrations()
    {
        $student = auth()->user()->student;
        if (! $student) {
            return redirect()->route('dashboard');
        }
        $registrations = SemesterRegistration::with(['semester', 'approver'])
            ->where('student_id', $student->id)
            ->orderByDesc('created_at')
            ->get();

        return view('semester-registrations.my-registrations', compact('student', 'registrations'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'semester_id' => ['required', 'exists:semesters,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $exists = SemesterRegistration::where('student_id', $validated['student_id'])
            ->where('semester_id', $validated['semester_id'])->first();
        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'This student is already registered for the selected semester.');
        }

        $validated['status'] = 'pending';
        $validated['registered_at'] = now();
        SemesterRegistration::create($validated);

        return redirect()->route('semester-registrations.index')->with('success', 'Registration submitted successfully.');
    }

    public function approve(SemesterRegistration $semester_registration)
    {
        if ($semester_registration->status !== 'pending') {
            return redirect()->route('semester-registrations.index')->with('error', 'Only pending registrations can be approved.');
        }
        if ($semester_registration->wizard_step !== null) {
            return redirect()->route('semester-registrations.index')->with('error', 'This registration is still in the guided wizard. Finish all steps there—approval is applied automatically when complete.');
        }
        $semester_registration->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
        ]);

        return redirect()->route('semester-registrations.index')->with('success', 'Registration approved.');
    }

    public function reject(Request $request, SemesterRegistration $semester_registration)
    {
        if ($semester_registration->status !== 'pending') {
            return redirect()->route('semester-registrations.index')->with('error', 'Only pending registrations can be rejected.');
        }
        $semester_registration->update([
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'notes' => $request->input('notes', $semester_registration->notes),
        ]);

        return redirect()->route('semester-registrations.index')->with('success', 'Registration rejected.');
    }

    public function bulkApprove(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $count = SemesterRegistration::whereIn('id', $ids)
            ->where('status', 'pending')
            ->whereNull('wizard_step')
            ->update([
                'status' => 'approved',
                'approved_at' => now(),
                'approved_by' => auth()->id(),
            ]);

        return redirect()->route('semester-registrations.index')->with('success', "Approved {$count} registration(s). Registrations still in the wizard were skipped.");
    }

    public function bulkReject(Request $request)
    {
        $ids = $request->input('ids', []);
        if (! is_array($ids)) {
            $ids = [];
        }
        $notes = $request->input('bulk_reject_notes', '');
        $count = SemesterRegistration::whereIn('id', $ids)->where('status', 'pending')->update([
            'status' => 'rejected',
            'approved_at' => now(),
            'approved_by' => auth()->id(),
            'notes' => $notes,
        ]);

        return redirect()->route('semester-registrations.index')->with('success', "Rejected {$count} registration(s).");
    }
}
