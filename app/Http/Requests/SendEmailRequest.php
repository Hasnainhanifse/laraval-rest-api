<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'array', 'min:1'],
            'to.*'    => ['required', 'email:rfc,dns'],
            'cc' => ['nullable', 'array'],
            'cc.*' => ['email', 'email:rfc,dns'],
            'bcc' => ['nullable', 'array'],
            'bcc.*' => ['email', 'email:rfc,dns'],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'altBody' => ['nullable', 'string'],
        ];
    }
}
