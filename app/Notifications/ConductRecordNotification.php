<?php

namespace App\Notifications;

use App\Models\ConductRecord;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ConductRecordNotification extends Notification
{
    use Queueable;

    private const PERMIT_TYPES = ['medical_permit', 'emergency_permit'];

    public function __construct(private readonly ConductRecord $record)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $label = ConductRecord::TYPES[$this->record->type] ?? $this->record->type;
        $isPermit = in_array($this->record->type, self::PERMIT_TYPES, true);

        return [
            'title' => $isPermit ? $label.' recorded' : 'New conduct record',
            'message' => $isPermit
                ? "Your {$label} has been recorded, dated ".$this->record->date->format('d M Y').'.'
                : "A {$label} conduct record was recorded for you, dated ".$this->record->date->format('d M Y').'.',
            'url' => route('dashboard'),
            'kind' => 'conduct_record',
        ];
    }
}
