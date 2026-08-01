<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StudentDocumentController extends Controller
{
    public function store(Request $request, Student $student)
    {
        $request->validate([
            'type' => ['required', 'string', 'max:50'],
            'document' => ['required', 'file', 'max:10240'], // 10MB
        ]);
        $file = $request->file('document');
        $path = $file->store('student-documents/' . $student->id, 'local');
        StudentDocument::create([
            'student_id' => $student->id,
            'type' => $request->input('type'),
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
        return back()->with('success', 'Document uploaded.');
    }

    public function destroy(StudentDocument $student_document)
    {
        // Soft-deleted only: the file is kept on disk so the record can still be restored from Trash.
        $student_document->delete();
        return back()->with('success', 'Document removed.');
    }

    public function download(StudentDocument $student_document)
    {
        if (auth()->user()->isStudent() && (!auth()->user()->student || auth()->user()->student->id !== $student_document->student_id)) {
            abort(403);
        }
        if (!Storage::disk('local')->exists($student_document->path)) {
            abort(404);
        }
        return Storage::disk('local')->download($student_document->path, $student_document->name ?: 'document');
    }
}
