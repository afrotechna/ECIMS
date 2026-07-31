<?php

namespace App\Notifications;

use App\Models\OfficeDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OfficeDocumentReceivedNotification extends Notification
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
        return [
            'title' => 'New document: '.$this->document->title,
            'message' => ($this->document->sender?->name ?? 'A colleague').' sent you a document to review.',
            'url' => route('office-documents.show', $this->document),
            'kind' => 'office_document_sent',
        ];
    }
}
