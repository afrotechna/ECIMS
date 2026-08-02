<?php

namespace App\Http\Controllers;

use App\Models\ConductRecord;
use App\Models\Student;
use App\Notifications\ConductRecordNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ConductRecordController extends Controller
{
    private const MEDICAL_TYPES = ['medical_permit'];

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
            'type' => ['required', 'string', Rule::in(array_keys(ConductRecord::TYPES))],
            'sanction' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'effective_until' => ['nullable', 'date'],
            'medical_form' => [
                Rule::requiredIf(in_array($request->input('type'), self::MEDICAL_TYPES, true)),
                'nullable', 'file', 'max:10240',
            ],
        ]);
        $validated['recorded_by'] = auth()->id();

        if ($request->hasFile('medical_form')) {
            $file = $request->file('medical_form');
            $validated['medical_form_path'] = $file->store('conduct-records/'.$validated['student_id'], 'local');
            $validated['medical_form_name'] = $file->getClientOriginalName();
        }
        unset($validated['medical_form']);

        $record = ConductRecord::create($validated);

        $studentUser = $record->student?->userAccount;
        $studentUser?->notify(new ConductRecordNotification($record));

        return redirect()->route('conduct-records.index')->with('success', 'Conduct record added.');
    }

    public function downloadMedicalForm(ConductRecord $conduct_record)
    {
        if (! $conduct_record->medical_form_path || ! Storage::disk('local')->exists($conduct_record->medical_form_path)) {
            abort(404);
        }

        return Storage::disk('local')->download($conduct_record->medical_form_path, $conduct_record->medical_form_name ?: 'medical-form');
    }
}
