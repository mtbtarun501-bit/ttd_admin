<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PortfolioHoldingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'asset_type' => ['required', Rule::in(['crypto', 'stock'])],
            'symbol' => 'required|string|max:50',
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|gt:0',
            'buy_price' => 'required|numeric|min:0',
            'buy_date' => 'required|date',
            'platform' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'quantity.gt' => 'Quantity must be greater than zero.',
            'asset_type.in' => 'Choose a valid asset type (crypto or stock).',
        ];
    }
}