<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MessageLog extends Model
{
    protected $table = 'message_log';

    protected $fillable = [
        'channel',
        'recipient',
        'recipient_role',
        'body',
        'subject',
        'student_id',
        'semester_id',
        'template',
        'status',
        'external_id',
        'error_message',
        'sent_at',
        'sent_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public const CHANNELS = ['sms' => 'SMS', 'email' => 'Email'];
    public const STATUSES = ['pending' => 'Pending', 'sent' => 'Sent', 'failed' => 'Failed'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }
}
