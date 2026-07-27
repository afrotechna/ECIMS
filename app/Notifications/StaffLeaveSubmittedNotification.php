<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffLeaveSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly LeaveApplication $leaveApplication)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $staffName = $this->leaveApplication->staffUser?->staffDisplayName() ?? 'Staff';
        $days = $this->leaveApplication->from_date->diffInDays($this->leaveApplication->to_date) + 1;

        return [
            'title' => 'New staff leave request',
            'message' => $staffName.' submitted leave request for '.$days.' days ('.$this->leaveApplication->from_date->format('d M Y').' to '.$this->leaveApplication->to_date->format('d M Y').').',
            'url' => route('leave-applications.index'),
            'kind' => 'leave_submitted',
        ];
    }
}

