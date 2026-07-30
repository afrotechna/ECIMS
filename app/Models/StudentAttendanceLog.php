<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentAttendanceLog extends Model
{
    protected $fillable = [
        'student_id',
        'punched_at',
        'direction',
        'device_serial',
        'raw_biometric_id',
        'attendance_import_log_id',
    ];

    protected $casts = [
        'punched_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function importLog(): BelongsTo
    {
        return $this->belongsTo(StudentAttendanceImportLog::class, 'attendance_import_log_id');
    }
}
