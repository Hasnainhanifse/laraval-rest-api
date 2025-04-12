<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use InvalidArgumentException;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'order_no',
        'status',
        'total_amount',
    ];

    const STATUS_WAITING = 'W';
    const STATUS_PENDING = 'P';
    const STATUS_APPROVED = 'A';
    const STATUS_REJECTED = 'R';

    /**
     * Get the supplier that owns the purchase order.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the order items for the purchase order.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Check if the status transition is valid.
     */
    public function canTransitionTo(string $newStatus): bool
    {
        if ($this->status === $newStatus) {
            return true;
        }

        $allowedTransitions = [
            self::STATUS_WAITING => [self::STATUS_PENDING],
            self::STATUS_PENDING => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => [],
            self::STATUS_REJECTED => [],
        ];

        return in_array($newStatus, $allowedTransitions[$this->status] ?? []);
    }

    /**
     * Update the status of the purchase order.
     */
    public function updateStatus(string $newStatus): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException('Invalid status transition.');
        }

        $this->status = $newStatus;
        $this->save();
    }

    /**
     * Check if the purchase order is immutable.
     */
    public function isImmutable(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }

    /**
     * Calculate total amount from order items.
     */
    public function calculateTotalAmount(): float
    {
        return $this->orderItems->sum(function ($item) {
            return $item->qty * $item->unit_price;
        });
    }
}
