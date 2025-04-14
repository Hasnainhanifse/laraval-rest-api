<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email',
                Rule::unique('suppliers', 'email')->ignore($this->supplier),
            ],
            'region' => ['sometimes', 'string', 'max:255'],
            'vat_number' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
