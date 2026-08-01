<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Records who soft-deleted a row (deleted_by) alongside Eloquent's own deleted_at,
 * without every controller having to set it explicitly.
 */
trait HasSoftDeleteAudit
{
    protected static function bootHasSoftDeleteAudit(): void
    {
        static::deleting(function ($model) {
            if (auth()->hasUser()) {
                $model->deleted_by = auth()->id();
                $model->saveQuietly();
            }
        });

        static::restoring(function ($model) {
            $model->deleted_by = null;
        });
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
