<?php

namespace App\Notifications;

use App\Models\AccommodationAllocation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class RoomAllocatedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly AccommodationAllocation $allocation)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $room = $this->allocation->room;
        $roomLabel = $room ? $room->hostel->name.' — '.$room->name : 'a room';

        return [
            'title' => 'Room allocated',
            'message' => "You have been allocated to {$roomLabel}, effective ".$this->allocation->from_date->format('d M Y').'.',
            'url' => route('dashboard'),
            'kind' => 'room_allocated',
        ];
    }
}
