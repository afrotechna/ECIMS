<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;

class CertificateCollection extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = [
        'student_id',
        'collected_on',
        'certificate_number',
        'academic_year',
        'phone_number',
        'signature_confirmed',
        'recorded_by',
    ];

    protected $casts = [
        'collected_on' => 'date',
        'signature_confirmed' => 'boolean',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
