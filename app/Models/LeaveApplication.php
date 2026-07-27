<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveApplication extends Model
{
    protected $fillable = ['student_id', 'staff_user_id', 'from_date', 'to_date', 'reason', 'status', 'approved_by', 'approved_at', 'notes'];

    protected $casts = ['from_date' => 'date', 'to_date' => 'date', 'approved_at' => 'datetime'];

    public const STATUSES = ['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
