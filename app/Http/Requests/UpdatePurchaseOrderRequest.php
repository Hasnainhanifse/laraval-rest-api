<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var PurchaseOrder $purchaseOrder */
        $purchaseOrder = $this->route('purchase_order');

        // If the PO is immutable (Approved or Rejected), no updates are allowed
        if ($purchaseOrder->isImmutable()) {
            return [];
        }

        return [
            'status' => [
                'sometimes',
                Rule::in(['W', 'P', 'A', 'R']),
                function ($attribute, $value, $fail) use ($purchaseOrder) {
                    if (!$purchaseOrder->canTransitionTo($value)) {
                        $fail('Invalid status transition.');
                    }
                },
            ],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['sometimes', 'integer', 'exists:order_items,id'],
            'items.*.sku' => ['required_with:items', 'string'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        // Remove the total amount validation since it will be calculated automatically
    }
}
