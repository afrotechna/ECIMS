<?php

namespace App\Services;

use App\Models\ClinicalLogbookEntry;
use App\Models\ClinicalRemediationPlan;
use App\Models\MessageLog;
use App\Models\Student;
use App\Services\Sms\TwilioSmsSender;

class ClinicalNotificationService
{
    public function logbookApproved(ClinicalLogbookEntry $entry, ?int $sentBy = null): void
    {
        $entry->loadMissing(['student', 'procedure', 'semester']);
        $body = sprintf(
            '%s: Your clinical logbook entry "%s" (%s) was approved by your instructor.',
            config('college.institution_name', 'MuCOHAS'),
            $entry->procedure?->name ?? 'procedure',
            $entry->performed_on?->format('j M Y') ?? ''
        );
        $this->notifyStudent($entry->student, $body, 'clinical_logbook_approved', $sentBy, [
            'clinical_logbook_entry_id' => $entry->id,
        ]);
    }

    public function logbookRejected(ClinicalLogbookEntry $entry, ?int $sentBy = null): void
    {
        $entry->loadMissing(['student', 'procedure']);
        $body = sprintf(
            '%s: A clinical logbook entry was returned for revision. Open the portal → Clinical logbook to update and resubmit.',
            config('college.institution_name', 'MuCOHAS')
        );
        $this->notifyStudent($entry->student, $body, 'clinical_logbook_rejected', $sentBy, [
            'clinical_logbook_entry_id' => $entry->id,
        ]);
    }

    public function remediationAssigned(ClinicalRemediationPlan $plan, ?int $sentBy = null): void
    {
        $plan->loadMissing('student');
        $due = $plan->due_date ? ' Due: '.$plan->due_date->format('j M Y').'.' : '';
        $body = sprintf(
            '%s: Clinical remediation assigned: %s.%s Check Clinical training in the student portal.',
            config('college.institution_name', 'MuCOHAS'),
            $plan->title,
            $due
        );
        $this->notifyStudent($plan->student, $body, 'clinical_remediation', $sentBy, [
            'clinical_remediation_plan_id' => $plan->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function notifyStudent(Student $student, string $body, string $template, ?int $sentBy, array $context = []): void
    {
        if (! config('college.clinical_sms.enabled', true)) {
            MessageLog::create([
                'channel' => 'sms',
                'recipient' => $student->phone ?? '—',
                'recipient_role' => 'student',
                'body' => $body,
                'status' => 'skipped',
                'error_message' => 'Clinical SMS disabled in config.',
                'sent_by' => $sentBy,
                'student_id' => $student->id,
                'template' => $template,
            ]);

            return;
        }

        $sender = app(TwilioSmsSender::class);
        $base = array_merge($context, [
            'student_id' => $student->id,
            'template' => $template,
            'sent_by' => $sentBy,
        ]);

        if (filled($student->phone)) {
            $sender->send($student->phone, $body, array_merge($base, ['recipient_role' => 'student']));
        } else {
            MessageLog::create(array_merge($base, [
                'channel' => 'sms',
                'recipient' => '—',
                'recipient_role' => 'student',
                'body' => $body,
                'status' => 'failed',
                'error_message' => 'No student phone on file.',
            ]));
        }
    }
}
