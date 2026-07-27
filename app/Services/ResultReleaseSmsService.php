<?php

namespace App\Services;

use App\Jobs\SendStudentResultSmsJob;
use App\Models\MessageLog;
use App\Models\Result;
use App\Models\Semester;
use App\Models\Student;
use Illuminate\Support\Collection;

class ResultReleaseSmsService
{
    public const TEMPLATE_FINAL = 'results_final';

    public const TEMPLATE_CA = 'results_ca';

    public function buildMessage(Student $student, Semester $semester, string $template = self::TEMPLATE_FINAL): string
    {
        $college = config('college.institution_name', 'MuCOHAS');
        $semLabel = $semester->academicYearRange().' · '.$semester->periodName();
        $portal = url(config('college.result_sms.portal_path', '/my-module-results'));
        $locale = strtolower((string) config('college.result_sms.locale', 'en'));

        $isCa = $template === self::TEMPLATE_CA;

        if ($locale === 'sw') {
            $typeLabel = $isCa ? 'tathmini ya endapo ya masomo (CA)' : 'mwisho wa semester';

            return sprintf(
                '%s: Matokeo ya %s kwa %s (%s), %s, yapo tayari. Angalia hapa: %s',
                $college,
                $typeLabel,
                $student->full_name,
                $student->reg_no,
                $semLabel,
                $portal
            );
        }

        $typeLabel = $isCa ? 'Continuous assessment' : 'Final semester';

        return sprintf(
            '%s: %s results for %s (%s), %s, are available. View: %s',
            $college,
            $typeLabel,
            $student->full_name,
            $student->reg_no,
            $semLabel,
            $portal
        );
    }

    /**
     * Queue SMS to guardians and/or students for everyone with results in the semester.
     *
     * @return array{queued: int, skipped: int}
     */
    public function notifySemester(Semester $semester, string $template = self::TEMPLATE_FINAL, ?int $sentBy = null): array
    {
        $studentIds = $this->studentsWithResultsForSemester($semester, $template);

        $queued = 0;
        $skipped = 0;

        foreach ($studentIds as $studentId) {
            if ($this->shouldSkipStudent($studentId, $semester->id, $template)) {
                $skipped++;

                continue;
            }

            SendStudentResultSmsJob::dispatch($studentId, $semester->id, $template, $sentBy);
            $queued++;
        }

        return ['queued' => $queued, 'skipped' => $skipped];
    }

    /** @return Collection<int, int> */
    private function studentsWithResultsForSemester(Semester $semester, string $template): Collection
    {
        $query = Result::query()
            ->where('semester_id', $semester->id)
            ->whereHas('student', fn ($q) => $q->where('status', 'active'));

        if ($template === self::TEMPLATE_CA) {
            $query->whereNotNull('ca_mark');
        } else {
            $query->where(function ($q) {
                $q->whereNotNull('exam_mark')->orWhereNotNull('grade');
            });
        }

        return $query->distinct()->pluck('student_id');
    }

    private function shouldSkipStudent(int $studentId, int $semesterId, string $template): bool
    {
        if (! config('college.result_sms.dedupe', true)) {
            return false;
        }

        return MessageLog::query()
            ->where('student_id', $studentId)
            ->where('semester_id', $semesterId)
            ->where('template', $template)
            ->where('channel', 'sms')
            ->where('status', 'sent')
            ->exists();
    }

    public function sendForStudent(Student $student, Semester $semester, string $template = self::TEMPLATE_FINAL, ?int $sentBy = null): array
    {
        $body = $this->buildMessage($student, $semester, $template);
        $sender = app(\App\Services\Sms\TwilioSmsSender::class);
        $sent = 0;
        $failed = 0;

        $context = [
            'student_id' => $student->id,
            'semester_id' => $semester->id,
            'template' => $template,
            'sent_by' => $sentBy,
        ];

        if (config('college.result_sms.notify_guardian', true) && filled($student->guardian_phone)) {
            $log = $sender->send($student->guardian_phone, $body, array_merge($context, [
                'recipient_role' => 'guardian',
            ]));
            $log->status === 'sent' ? $sent++ : $failed++;
        }

        if (config('college.result_sms.notify_student', true) && filled($student->phone)) {
            $log = $sender->send($student->phone, $body, array_merge($context, [
                'recipient_role' => 'student',
            ]));
            $log->status === 'sent' ? $sent++ : $failed++;
        }

        if ($sent === 0 && $failed === 0) {
            MessageLog::create(array_merge($context, [
                'channel' => 'sms',
                'recipient' => '—',
                'recipient_role' => null,
                'body' => $body,
                'status' => 'failed',
                'error_message' => 'No guardian_phone or student phone on file.',
                'sent_by' => $sentBy,
            ]));
            $failed++;
        }

        return ['sent' => $sent, 'failed' => $failed];
    }
}
