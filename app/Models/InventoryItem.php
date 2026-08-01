<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\HasSoftDeleteAudit;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryItem extends Model
{
    use SoftDeletes, HasSoftDeleteAudit;

    protected $fillable = [
        'asset_tag',
        'name',
        'description',
        'category',
        'kind',
        'quantity',
        'unit',
        'location',
        'custodian',
        'acquired_on',
        'cost',
        'supplier',
        'serial_number',
        'condition',
        'status',
        'notes',
        'created_by',
    ];

    public const KINDS = [
        'item' => 'Item / supplies',
        'asset' => 'Fixed asset',
    ];

    public const CONDITIONS = [
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
        'damaged' => 'Damaged',
        'retired' => 'Retired',
    ];

    public const STATUSES = [
        'active' => 'Active',
        'disposed' => 'Disposed',
        'lost' => 'Lost',
        'in_repair' => 'In repair',
    ];

    protected function casts(): array
    {
        return [
            'acquired_on' => 'date',
            'cost' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function conditionLabel(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
