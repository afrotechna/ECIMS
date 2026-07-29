<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Payment $payment)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Payment received',
            'message' => number_format((float) $this->payment->amount).' TZS from '.$this->payment->student->full_name.' ('.$this->payment->student->reg_no.').',
            'url' => route('payments.show', $this->payment),
            'kind' => 'payment_received',
        ];
    }
}
