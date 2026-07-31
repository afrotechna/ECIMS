<?php

namespace App\Notifications;

use App\Models\StudentCardStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StudentCardStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly StudentCardStatus $cardStatus)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $doc = $this->cardStatus->documentTypeLabel();

        $message = match ($this->cardStatus->status) {
            'printed' => "Your {$doc} has been printed.",
            'active' => "Your {$doc} is now active and ready for collection.",
            default => "Your {$doc} status has been updated.",
        };

        return [
            'title' => $doc.' status update',
            'message' => $message,
            'url' => route('dashboard'),
            'kind' => 'student_card_status',
        ];
    }
}
