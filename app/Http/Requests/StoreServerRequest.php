<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreServerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Livewire or Controllers will handle Policy checks
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'identifier' => [
                'required',
                'string',
                'min:3',
                'max:63',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', // Lowercase alphanumeric with inner hyphens
                'unique:servers,identifier',
            ],
            'src_ip' => ['required', 'ip'],
            'node_id' => ['required', 'exists:nodes,id'],
            'subscription_id' => ['required', 'exists:subscriptions,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'identifier.regex' => 'The identifier must be lowercase, alphanumeric, and can contain hyphens (but not at the start or end).',
            'identifier.unique' => 'This identifier is already in use across the network.',
        ];
    }
}
