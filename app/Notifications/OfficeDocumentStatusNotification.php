<?php

namespace App\Notifications;

use App\Models\OfficeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfficeDocumentStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly OfficeDocument $document)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $recipient = $this->document->recipient?->name ?? 'The recipient';

        $message = match ($this->document->status) {
            'received' => "{$recipient} received your document.",
            'printed' => "{$recipient} printed your document.",
            'completed' => "{$recipient} marked your document as completed.",
            default => "Your document status changed to {$this->document->statusLabel()}.",
        };

        return [
            'title' => $this->document->title.' — '.$this->document->statusLabel(),
            'message' => $message,
            'url' => route('office-documents.show', $this->document),
            'kind' => 'office_document_status',
        ];
    }
}
