<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Hostel extends Model
{
    protected $fillable = [
        'name',
        'code',
        'block_count',
        'rooms_per_block',
        'beds_per_room',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'block_count' => 'integer',
        'rooms_per_block' => 'integer',
        'beds_per_room' => 'integer',
    ];

    /** Default campus layout: 14 blocks, 4 rooms each, 4 double-decker beds (8 berths) per room. */
    public const DEFAULT_BLOCK_COUNT = 14;

    public const DEFAULT_ROOMS_PER_BLOCK = 4;

    public const DEFAULT_BEDS_PER_ROOM = 8;

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class)->orderBy('block_number')->orderBy('room_in_block')->orderBy('name');
    }

    public function expectedRoomSlots(): int
    {
        return max(0, (int) $this->block_count) * max(0, (int) $this->rooms_per_block);
    }

    public function totalBedCapacity(): int
    {
        return (int) $this->rooms()->sum('bed_count');
    }
}
