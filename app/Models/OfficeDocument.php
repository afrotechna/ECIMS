<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficeDocument extends Model
{
    protected $fillable = [
        'sender_id',
        'recipient_id',
        'parent_id',
        'title',
        'notes',
        'file_path',
        'original_name',
        'mime_type',
        'size',
        'status',
        'received_at',
        'printed_at',
        'completed_at',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'printed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUSES = [
        'sent' => 'Sent',
        'received' => 'Received',
        'printed' => 'Printed',
        'completed' => 'Completed',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * Ordered stages of the document's journey, each with whether it has happened,
     * when, and by whom — used to render the timeline.
     *
     * @return list<array{label: string, at: ?\Illuminate\Support\Carbon, by: ?string, done: bool}>
     */
    public function timeline(): array
    {
        return [
            ['label' => 'Sent', 'at' => $this->created_at, 'by' => $this->sender?->name, 'done' => true],
            ['label' => 'Received', 'at' => $this->received_at, 'by' => $this->recipient?->name, 'done' => $this->received_at !== null],
            ['label' => 'Printed', 'at' => $this->printed_at, 'by' => $this->recipient?->name, 'done' => $this->printed_at !== null],
            ['label' => 'Completed', 'at' => $this->completed_at, 'by' => $this->recipient?->name, 'done' => $this->completed_at !== null],
        ];
    }
}
