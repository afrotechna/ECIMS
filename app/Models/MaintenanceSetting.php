<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class MaintenanceSetting extends Model
{
    private const CACHE_KEY = 'maintenance_setting';

    protected $fillable = [
        'is_active',
        'title',
        'message',
        'starts_at',
        'ends_at',
        'activated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public static function current(): self
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::firstOrCreate(['id' => 1]));
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** True only once the schedule window (if any) is actually in effect right now. */
    public function isEffectiveNow(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    /** True while armed but the scheduled start hasn't arrived yet — used for advance-notice banners. */
    public function isUpcoming(): bool
    {
        return $this->is_active && $this->starts_at && now()->lt($this->starts_at);
    }
}
