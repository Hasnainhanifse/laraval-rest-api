<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class UpdateOrderItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'purchase_order_id' => ['sometimes', 'exists:purchase_orders,id'],
            'sku' => ['sometimes', 'string'],
            'description' => ['sometimes', 'string'],
            'qty' => ['sometimes', 'integer', 'min:1'],
            'unit_price' => ['sometimes', 'numeric', 'min:0'],
        ];
    }
} 