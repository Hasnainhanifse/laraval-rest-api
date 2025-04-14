<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:suppliers,email'],
            'region' => ['required', 'string', 'max:255'],
            'vat_number' => ['required', 'string', 'max:255'],
        ];
    }
}
