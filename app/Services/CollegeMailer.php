<?php

namespace App\Services;

use App\Models\MessageLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CollegeMailer
{
    public function send(string $recipient, string $subject, string $body, array $meta = []): MessageLog
    {
        $status = 'pending';
        $error = null;

        if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            try {
                Mail::raw($body, function ($message) use ($recipient, $subject) {
                    $message->to($recipient)->subject($subject);
                });
                $status = 'sent';
            } catch (\Throwable $e) {
                $status = 'failed';
                $error = $e->getMessage();
                Log::warning('College email failed', ['to' => $recipient, 'error' => $error]);
            }
        } else {
            $status = 'failed';
            $error = 'Invalid email address';
        }

        return MessageLog::create([
            'channel' => 'email',
            'recipient' => $recipient,
            'subject' => $subject,
            'body' => $body,
            'status' => $status,
            'error_message' => $error,
            'template' => $meta['template'] ?? 'manual',
            'student_id' => $meta['student_id'] ?? null,
            'sent_by' => $meta['sent_by'] ?? auth()->id(),
        ]);
    }
}
