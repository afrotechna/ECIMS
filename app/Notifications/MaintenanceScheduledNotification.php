<?php

namespace App\Notifications;

use App\Models\MaintenanceSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MaintenanceScheduledNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly MaintenanceSetting $setting)
    {
    }

    /**
     * Everyone gets the in-app bell notification. The admin who actually flipped
     * maintenance on also gets an email — they're the one who needs a durable,
     * off-app reminder (and a way back in) since regular login access is what's
     * being suspended for everyone else.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->setting->activated_by && $notifiable->id === $this->setting->activated_by) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Maintenance mode is now active — '.config('app.name'))
            ->greeting('Hello '.$notifiable->name.',')
            ->line('You turned on maintenance mode for '.config('app.name').'. The landing and login pages are now locked out for everyone, including you — use the link below to get back in.')
            ->line($this->setting->title ?: 'Scheduled system maintenance')
            ->line($this->setting->message ?: 'The system is temporarily unavailable while maintenance is performed.');

        if ($this->setting->starts_at) {
            $mail->line('Starts: '.$this->setting->starts_at->format('d M Y, H:i'));
        }
        if ($this->setting->ends_at) {
            $mail->line('Expected back: '.$this->setting->ends_at->format('d M Y, H:i'));
        }

        return $mail
            ->action('Sign in to manage maintenance', route('maintenance.admin-entry'))
            ->line('You can also reach this at any time by visiting '.route('maintenance.admin-entry').' directly — worth bookmarking or writing down.');
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
            'title' => $this->setting->title ?: 'Scheduled system maintenance',
            'message' => trim(($this->setting->message ?: 'The system will be temporarily unavailable for maintenance.').$schedule),
            'kind' => 'maintenance_scheduled',
        ];
    }
}
