<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffLeavePendingNotification extends Notification
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
        $days = $this->leaveApplication->from_date->diffInDays($this->leaveApplication->to_date) + 1;

        return [
            'title' => 'Leave request submitted',
            'message' => 'Your '.$days.'-day leave request was submitted and is pending principal approval.',
            'url' => route('leave-applications.index'),
            'kind' => 'leave_pending',
        ];
    }
}

