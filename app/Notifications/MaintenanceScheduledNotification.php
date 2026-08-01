<?php

namespace App\Notifications;

use App\Models\MaintenanceSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MaintenanceScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly MaintenanceSetting $setting)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $schedule = '';
        if ($this->setting->starts_at) {
            $schedule .= ' Starting '.$this->setting->starts_at->format('d M Y, H:i');
        }
        if ($this->setting->ends_at) {
            $schedule .= ' until '.$this->setting->ends_at->format('d M Y, H:i').'.';
        } elseif ($this->setting->starts_at) {
            $schedule .= '.';
        }

        return [
            'title' => $this->setting->title ?: 'Sorry for the inconvenience',
            'message' => trim(($this->setting->message ?: "We're performing scheduled maintenance and will be back online shortly.").$schedule),
            'kind' => 'maintenance_scheduled',
        ];
    }
}
