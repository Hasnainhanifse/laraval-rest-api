<?php
namespace App\Http\Requests;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        $purchaseOrder = $this->route('purchase_order');
        if ($purchaseOrder->isImmutable()) {
            return [];
        }
        return [
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'status' => [
                'sometimes',
                Rule::in(['W', 'P', 'A', 'R', 'w', 'p', 'a', 'r']),
                function ($attribute, $value, $fail) use ($purchaseOrder) {
                    $upperValue = strtoupper($value);
                    if (!$purchaseOrder->canTransitionTo($upperValue)) {
                        $fail('Invalid status transition.');
                    }
                },
            ],
            'total_amount' => ['sometimes', 'numeric', 'min:0'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.id' => ['sometimes', 'integer', 'exists:order_items,id'],
            'items.*.sku' => ['required_with:items', 'string'],
            'items.*.description' => ['required_with:items', 'string'],
            'items.*.qty' => ['required_with:items', 'integer', 'min:1'],
            'items.*.unit_price' => ['required_with:items', 'numeric', 'min:0'],
            'replace_items' => ['sometimes', 'boolean'],
        ];
    }
    protected function prepareForValidation()
    {
        if ($this->has('status')) {
            $this->merge([
                'status' => strtoupper($this->status),
            ]);
        }
    }
}
