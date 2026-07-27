<?php

namespace App\Http\Controllers;

use App\Models\ConductRecord;
use App\Models\Student;
use Illuminate\Http\Request;

class ConductRecordController extends Controller
{
    public function index(Request $request)
    {
        $query = ConductRecord::with(['student.programme', 'recordedBy']);
        if ($request->filled('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }
        $records = $query->orderByDesc('date')->paginate(20);
        $students = Student::where('status', 'active')->orderBy('reg_no')->get();
        return view('conduct-records.index', compact('records', 'students'));
    }

    public function create(Request $request)
    {
        $studentId = $request->get('student_id');
        $students = Student::where('status', 'active')->orderBy('reg_no')->get();
        return view('conduct-records.create', compact('students', 'studentId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'date' => ['required', 'date'],
            'type' => ['required', 'string', 'in:warning,reprimand,suspension,fine,other'],
            'sanction' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'effective_until' => ['nullable', 'date'],
        ]);
        $validated['recorded_by'] = auth()->id();
        ConductRecord::create($validated);
        return redirect()->route('conduct-records.index')->with('success', 'Conduct record added.');
    }
}
