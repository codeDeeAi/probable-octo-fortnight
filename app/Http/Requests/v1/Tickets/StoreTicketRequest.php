<?php

namespace App\Http\Requests\v1\Tickets;

use App\Enums\TicketTypes;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|string|in:'.TicketTypes::inValidationRule(),
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|integer|min:1',
        ];
    }
}
