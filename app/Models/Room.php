<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    /** Prefix for auto-generated room codes, e.g. MCOHAS-Block01-R1 */
    public const ROOM_CODE_PREFIX = 'MCOHAS';

    public static function generatedCode(int $blockNumber, int $roomInBlock): string
    {
        return sprintf('%s-Block%02d-R%d', self::ROOM_CODE_PREFIX, $blockNumber, $roomInBlock);
    }

    protected $fillable = [
        'hostel_id',
        'block_number',
        'room_in_block',
        'name',
        'bed_count',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'block_number' => 'integer',
        'room_in_block' => 'integer',
        'bed_count' => 'integer',
    ];

    public function hostel(): BelongsTo
    {
        return $this->belongsTo(Hostel::class);
    }

    public function accommodationAllocations(): HasMany
    {
        return $this->hasMany(AccommodationAllocation::class, 'room_id');
    }

    /** Active allocations whose dates include today (for occupancy boards). */
    public function effectiveAllocationsNow(): HasMany
    {
        return $this->accommodationAllocations()->effectiveNow();
    }

    public function activeAllocationsCount(): int
    {
        return $this->accommodationAllocations()->where('status', 'active')->count();
    }

    /**
     * Free berths against active allocations. Uses the eager-loaded
     * accommodation_allocations_count (from withCount) when present, to avoid
     * an extra query per room in a list; falls back to a live count otherwise.
     */
    public function remainingBerths(): int
    {
        $occupied = array_key_exists('accommodation_allocations_count', $this->attributes)
            ? (int) $this->attributes['accommodation_allocations_count']
            : $this->activeAllocationsCount();

        return max(0, (int) $this->bed_count - $occupied);
    }

    /** Residents counted for today (same rules as the live occupancy board). */
    public function effectiveOccupantsCount(): int
    {
        return (int) $this->effectiveAllocationsNow()->count();
    }

    /** Every berth is taken for today’s effective occupancy. */
    public function isFullyOccupiedEffective(): bool
    {
        return $this->bed_count > 0 && $this->effectiveOccupantsCount() >= $this->bed_count;
    }

    /** Human label for the block dropdown (Block 1, Block 2, …). */
    public function blockLabel(): ?string
    {
        if ($this->block_number === null) {
            return null;
        }

        return 'Block '.$this->block_number;
    }

    public function blockRoomLabel(): string
    {
        if ($this->block_number !== null && $this->room_in_block !== null) {
            return 'Block '.$this->block_number.' — Room '.$this->room_in_block;
        }

        return $this->name;
    }
}
