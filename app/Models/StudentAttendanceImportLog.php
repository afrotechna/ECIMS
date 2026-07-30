<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAttendanceImportLog extends Model
{
    protected $fillable = [
        'user_id',
        'original_filename',
        'rows_touched',
        'rows_skipped',
        'notes',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(StudentAttendanceLog::class, 'attendance_import_log_id');
    }
}
