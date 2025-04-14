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
    
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
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
    public function updateStatus(string $newStatus): void
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new InvalidArgumentException('Invalid status transition.');
        }
        $this->status = $newStatus;
        $this->save();
    }
    public function isImmutable(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_REJECTED]);
    }
    public function calculateTotalAmount(): float
    {
        return $this->orderItems->sum(function ($item) {
            return $item->qty * $item->unit_price;
        });
    }
}
