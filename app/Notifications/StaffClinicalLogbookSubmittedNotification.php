<?php

namespace App\Notifications;

use App\Models\ClinicalLogbookEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffClinicalLogbookSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly ClinicalLogbookEntry $entry)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Logbook entry submitted for review',
            'message' => $this->entry->student->full_name.' submitted a clinical logbook entry ('.($this->entry->procedure?->name ?? 'procedure').') for review.',
            'url' => route('clinical-logbook.index', ['status' => 'submitted']),
            'kind' => 'clinical_logbook_submitted',
        ];
    }
}
