<?php

namespace App\Notifications;

use App\Models\LeaveApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StaffLeaveDecisionNotification extends Notification
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
        $status = strtolower((string) $this->leaveApplication->status);
        $isApproved = $status === 'approved';

        return [
            'title' => $isApproved ? 'Leave approved' : 'Leave rejected',
            'message' => $isApproved
                ? 'Your leave request has been approved.'
                : 'Your leave request has been rejected.',
            'url' => route('leave-applications.index'),
            'kind' => 'leave_decision',
        ];
    }
}

