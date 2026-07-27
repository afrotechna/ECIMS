<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\CertificateCollection;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CertificateCollectionController extends Controller
{
    public function index(Request $request): View
    {
        $query = CertificateCollection::with(['student.programme', 'recordedBy']);

        if ($request->filled('academic_year')) {
            $query->where('academic_year', (int) $request->academic_year);
        }
        if ($request->filled('programme_id')) {
            $query->whereHas('student', fn ($q) => $q->where('programme_id', $request->programme_id));
        }

        $collections = $query->orderByDesc('collected_on')->paginate(20)->withQueryString();
        $programmes = \App\Models\Programme::orderBy('code')->get();
        $academicYears = CertificateCollection::query()->distinct()->orderByDesc('academic_year')->pluck('academic_year');

        return view('certificate-collections.index', compact('collections', 'programmes', 'academicYears'));
    }

    public function create(): View
    {
        $students = $this->eligibleStudents();

        return view('certificate-collections.create', compact('students'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'collected_on' => ['required', 'date'],
            'certificate_number' => ['required', 'string', 'max:100'],
            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'phone_number' => ['nullable', 'string', 'max:30'],
            'signature_confirmed' => ['required', 'accepted'],
        ]);

        $student = Student::findOrFail($validated['student_id']);
        if (! $this->isEligible($student)) {
            return back()->withInput()->with('error', 'This student is not eligible: certificate collection requires graduated status and no outstanding fees.');
        }

        $collection = CertificateCollection::create([
            ...$validated,
            'signature_confirmed' => true,
            'phone_number' => $validated['phone_number'] ?? $student->phone,
            'recorded_by' => auth()->id(),
        ]);

        ActivityLog::log(
            'certificate.collected',
            CertificateCollection::class,
            $collection->id,
            "Certificate #{$collection->certificate_number} collected by {$student->full_name}"
        );

        return redirect()->route('certificate-collections.index')->with('success', 'Certificate collection recorded.');
    }

    public function destroy(CertificateCollection $certificate_collection): RedirectResponse
    {
        $certificate_collection->delete();

        return redirect()->route('certificate-collections.index')->with('success', 'Record removed.');
    }

    /** @return \Illuminate\Support\Collection<int, Student> */
    private function eligibleStudents()
    {
        return Student::with('programme')
            ->where('status', 'graduated')
            ->orderBy('reg_no')
            ->get()
            ->filter(fn (Student $s) => $s->balance <= 0)
            ->values();
    }

    private function isEligible(Student $student): bool
    {
        return $student->status === 'graduated' && $student->balance <= 0;
    }
}
