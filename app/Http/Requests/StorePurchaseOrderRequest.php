<?php

namespace App\Http\Requests;

use App\Models\PurchaseOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
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
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'order_no' => [
                'required',
                'string',
                Rule::unique('purchase_orders')->where(function ($query) {
                    return $query->where('supplier_id', $this->supplier_id);
                }),
            ],
            'status' => ['required', Rule::in(['W', 'P', 'A', 'R'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sku' => ['required', 'string'],
            'items.*.description' => ['required', 'string'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        // Remove the total amount validation since it will be calculated automatically
    }
}
