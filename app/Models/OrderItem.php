<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'sku',
        'description',
        'qty',
        'unit_price',
    ];

    /**
     * Get the purchase order that owns the order item.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the total amount for this item.
     */
    public function getTotalAmount(): float
    {
        return $this->qty * $this->unit_price;
    }
}
