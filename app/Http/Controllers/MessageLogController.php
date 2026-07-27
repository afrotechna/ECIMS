<?php

namespace App\Http\Controllers;

use App\Models\MessageLog;
use App\Models\Student;
use App\Services\CollegeMailer;
use App\Services\Sms\TwilioSmsSender;
use Illuminate\Http\Request;

class MessageLogController extends Controller
{
    public function index()
    {
        $logs = MessageLog::with(['student', 'sentBy'])->orderByDesc('created_at')->paginate(30);

        return view('message-logs.index', compact('logs'));
    }

    public function create(Request $request)
    {
        $students = Student::where('status', 'active')->orderBy('reg_no')->get();
        $programmes = \App\Models\Programme::where('is_active', true)->orderBy('code')->get();

        return view('message-logs.create', compact('students', 'programmes'));
    }

    public function store(Request $request, TwilioSmsSender $sms, CollegeMailer $mailer)
    {
        $validated = $request->validate([
            'channel' => ['required', 'string', 'in:sms,email'],
            'recipients' => ['required', 'string'],
            'body' => ['required', 'string'],
            'subject' => ['nullable', 'string', 'max:255'],
        ]);
        $recipients = array_filter(array_map('trim', explode(',', $validated['recipients'])));
        $sent = 0;
        $failed = 0;
        foreach ($recipients as $recipient) {
            if ($validated['channel'] === 'sms') {
                $log = $sms->send($recipient, $validated['body'], [
                    'template' => 'manual',
                    'sent_by' => auth()->id(),
                ]);
                $log->status === 'sent' ? $sent++ : $failed++;
            } else {
                $log = $mailer->send(
                    $recipient,
                    $validated['subject'] ?? config('app.name').' — message',
                    $validated['body'],
                    ['template' => 'manual', 'sent_by' => auth()->id()]
                );
                $log->status === 'sent' ? $sent++ : $failed++;
            }
        }

        $msg = "{$sent} message(s) recorded.";
        if ($failed > 0) {
            $msg .= " {$failed} SMS failed — see message log.";
        }
        return redirect()->route('message-logs.index')->with('success', $msg);
    }

    public function sendTest(Request $request, TwilioSmsSender $sms)
    {
        $validated = $request->validate([
            'test_phone' => ['required', 'string', 'max:40'],
            'test_locale' => ['nullable', 'string', 'in:en,sw'],
        ]);
        $locale = $validated['test_locale'] ?? 'en';
        $name = config('college.institution_name', 'MuCOHAS');
        $body = $locale === 'sw'
            ? "Ujumbe wa majaribio kutoka {$name}. Twilio imeunganishwa kwa usahihi."
            : "Test SMS from {$name}. Twilio integration is working.";

        $log = $sms->send($validated['test_phone'], $body, [
            'template' => 'sms_test',
            'sent_by' => auth()->id(),
        ]);

        $status = $log->status === 'sent' ? 'success' : 'error';
        $message = $log->status === 'sent'
            ? 'Test SMS sent. Check the device and the message log.'
            : ('Test SMS failed: '.($log->error_message ?? 'unknown error'));

        return redirect()->route('message-logs.create')->with($status, $message);
    }
}
