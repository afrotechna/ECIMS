<?php

namespace App\Notifications;

use App\Models\Semester;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResultsRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Semester $semester, private readonly string $reviewNotes)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Results rejected',
            'message' => 'Pending results for '.$this->semester->label.' were rejected: '.$this->reviewNotes,
            'url' => route('results.import.ca'),
            'kind' => 'results_rejected',
        ];
    }
}
