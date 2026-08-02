<?php

namespace App\Notifications;

use App\Models\Semester;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResultsPendingApprovalNotification extends Notification
{
    use Queueable;

    /**
     * @param  array{total: int, pass: int, fail: int}  $summary  Snapshot of all pending results for this semester at send time.
     */
    public function __construct(
        private readonly Semester $semester,
        private readonly int $rowsTouched,
        private readonly array $summary = ['total' => 0, 'pass' => 0, 'fail' => 0]
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Results pending approval',
            'message' => $this->rowsTouched.' result row(s) imported for '.$this->semester->label.' are awaiting approval before publication.',
            'url' => route('results.approvals.index'),
            'kind' => 'results_pending_approval',
            'summary' => $this->summary,
        ];
    }
}
