<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;

class UserModulePermission extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = [
        'user_id',
        'module',
        'actions',
        'granted_by',
    ];

    protected $casts = [
        'actions' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grantedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
