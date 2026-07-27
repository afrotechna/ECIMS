<?php

namespace App\Http\Controllers;

use App\Models\TranscriptRequest;
use App\Services\CollegeMailer;
use Illuminate\Http\Request;

class TranscriptRequestController extends Controller
{
    public function index()
    {
        $requests = TranscriptRequest::with(['student.programme', 'processor'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('transcript-requests.index', compact('requests'));
    }

    public function store(Request $request)
    {
        $student = auth()->user()->student;
        abort_unless($student, 403);

        $validated = $request->validate([
            'purpose' => ['nullable', 'string', 'max:500'],
        ]);

        $pending = TranscriptRequest::where('student_id', $student->id)
            ->where('status', 'pending')
            ->exists();

        if ($pending) {
            return back()->with('error', 'You already have a pending transcript request.');
        }

        TranscriptRequest::create([
            'student_id' => $student->id,
            'purpose' => $validated['purpose'] ?? null,
            'status' => 'pending',
        ]);

        return back()->with('success', 'Transcript request submitted. The academic office will notify you when it is ready.');
    }

    public function update(Request $request, TranscriptRequest $transcript_request, CollegeMailer $mailer)
    {
        $validated = $request->validate([
            'status' => ['required', 'in:ready,rejected'],
            'staff_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $transcript_request->update([
            'status' => $validated['status'],
            'staff_notes' => $validated['staff_notes'] ?? null,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        $student = $transcript_request->student;
        if ($student?->email) {
            $label = TranscriptRequest::STATUSES[$validated['status']] ?? $validated['status'];
            $mailer->send(
                $student->email,
                'Transcript request update — '.config('app.name'),
                "Your transcript request status is now: {$label}.\n\n".($validated['staff_notes'] ?? ''),
                ['template' => 'transcript_request', 'student_id' => $student->id]
            );
        }

        return back()->with('success', 'Transcript request updated.');
    }
}
