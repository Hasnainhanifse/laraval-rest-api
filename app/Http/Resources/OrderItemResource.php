<?php
namespace App\Http\Resources;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        if ($this->resource instanceof \Illuminate\Pagination\AbstractPaginator) {
            return [
                'data' => $this->collection->map(function ($item) {
                    return $this->formatOrderItem($item);
                }),
                'meta' => [
                    'current_page' => $this->currentPage(),
                    'last_page' => $this->lastPage(),
                    'per_page' => $this->perPage(),
                    'total' => $this->total(),
                    'from' => $this->firstItem(),
                    'to' => $this->lastItem(),
                ]
            ];
        }

        return $this->formatOrderItem($this->resource);
    }

    private function formatOrderItem($orderItem)
    {
        // Calculate total amount for this item
        $totalAmount = $orderItem->qty * $orderItem->unit_price;

        return [
            'id' => $orderItem->id,
            'purchase_order_id' => $orderItem->purchase_order_id,
            'sku' => $orderItem->sku,
            'description' => $orderItem->description,
            'qty' => $orderItem->qty,
            'unit_price' => $orderItem->unit_price,
            'total_amount' => number_format($totalAmount, 2, '.', ''),
            'created_at' => $orderItem->created_at,
            'updated_at' => $orderItem->updated_at,
        ];
    }
} 