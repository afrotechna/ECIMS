<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Services\ResultReleaseSmsService;
use Illuminate\Http\Request;

class ResultSmsController extends Controller
{
  public function notify(Request $request, ResultReleaseSmsService $sms)
  {
    $validated = $request->validate([
      'semester_id' => ['required', 'exists:semesters,id'],
      'template' => ['nullable', 'string', 'in:results_final,results_ca'],
    ]);

    $semester = Semester::findOrFail($validated['semester_id']);
    $template = $validated['template'] ?? ResultReleaseSmsService::TEMPLATE_FINAL;

    $out = $sms->notifySemester($semester, $template, auth()->id());

    $msg = "Queued {$out['queued']} SMS notification(s)";
    if ($out['skipped'] > 0) {
      $msg .= " ({$out['skipped']} already sent for this semester)";
    }
    $msg .= '. Run the queue worker if QUEUE_CONNECTION is not sync.';

    return back()->with('success', $msg);
  }
}
