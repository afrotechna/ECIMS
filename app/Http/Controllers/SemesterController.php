<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\BulkDestroysRecords;
use App\Models\Programme;
use App\Models\Semester;
use Illuminate\Http\Request;

class SemesterController extends Controller
{
    use BulkDestroysRecords;

    public function index(Request $request)
    {
        $query = Semester::query()->orderByDesc('academic_year')->orderBy('number');
        if ($request->filled('academic_year')) {
            $query->where('academic_year', (int) $request->academic_year);
        }
        $semesters = $query->paginate(15)->withQueryString();
        $academicYearOptions = Semester::academicYearOptionsForForms();
        $programmes = Programme::where('is_active', true)->orderBy('code')->get();

        return view('semesters.index', compact('semesters', 'academicYearOptions', 'programmes'));
    }

    public function create()
    {
        $academicYearOptions = Semester::academicYearOptionsForForms();
        $periodOptions = Semester::periodOptions();

        return view('semesters.create', compact('academicYearOptions', 'periodOptions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'number' => ['required', 'integer', 'in:1,2'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['name'] = Semester::nameForPeriod((int) $validated['number']);

        if (Semester::where('academic_year', $validated['academic_year'])->where('number', $validated['number'])->exists()) {
            return redirect()->back()->withInput()->with('error', 'A semester with this academic year and number already exists.');
        }

        Semester::create($validated);
        return redirect()->route('semesters.index')->with('success', 'Semester created successfully.');
    }

    public function edit(Semester $semester)
    {
        $academicYearOptions = Semester::academicYearOptionsForForms();
        $periodOptions = Semester::periodOptions();

        return view('semesters.edit', compact('semester', 'academicYearOptions', 'periodOptions'));
    }

    public function update(Request $request, Semester $semester)
    {
        $validated = $request->validate([
            'academic_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'number' => ['required', 'integer', 'in:1,2'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $validated['is_active'] = $request->boolean('is_active');
        $validated['name'] = Semester::nameForPeriod((int) $validated['number']);

        $exists = Semester::where('academic_year', $validated['academic_year'])
            ->where('number', $validated['number'])
            ->where('id', '!=', $semester->id)
            ->exists();
        if ($exists) {
            return redirect()->back()->withInput()->with('error', 'Another semester with this academic year and number already exists.');
        }

        $semester->update($validated);
        return redirect()->route('semesters.index')->with('success', 'Semester updated successfully.');
    }

    public function openRegistration(Semester $semester)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only an administrator can open a semester for registration.');
        }

        $blockedReason = $semester->canOpenRegistration();
        if ($blockedReason !== null) {
            return redirect()->route('semesters.index')->with('error', $blockedReason);
        }

        $semester->update(['registration_status' => Semester::REGISTRATION_OPEN]);

        return redirect()->route('semesters.index')->with('success', $semester->label.' is now open for registration.');
    }

    public function completeRegistration(Semester $semester)
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Only an administrator can mark a semester complete.');
        }

        $semester->update(['registration_status' => Semester::REGISTRATION_COMPLETE]);

        return redirect()->route('semesters.index')->with('success', $semester->label.' has been marked complete.');
    }

    public function destroy(Semester $semester)
    {
        if ($semester->semesterRegistrations()->exists() || $semester->results()->exists()) {
            return redirect()->route('semesters.index')->with('error', 'Cannot delete semester with registrations or results. Deactivate it instead.');
        }
        $semester->delete();
        return redirect()->route('semesters.index')->with('success', 'Semester deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        return $this->bulkDestroyRecords(
            $request,
            Semester::class,
            'semesters.index',
            singularLabel: 'semester',
            deleter: function (Semester $semester) {
                if ($semester->semesterRegistrations()->exists() || $semester->results()->exists()) {
                    return false;
                }
                $semester->delete();

                return true;
            },
        );
    }
}
