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
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
    public function getTotalAmount(): float
    {
        return $this->qty * $this->unit_price;
    }
}
