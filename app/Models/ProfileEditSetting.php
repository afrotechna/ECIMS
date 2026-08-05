<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ProfileEditSetting extends Model
{
    private const CACHE_KEY = 'profile_edit_setting';

    protected $fillable = [
        'is_locked',
        'locked_by',
        'locked_at',
    ];

    protected $casts = [
        'is_locked' => 'boolean',
        'locked_at' => 'datetime',
    ];

    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::firstOrCreate(['id' => 1]));
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function lockedByUser(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }
}
