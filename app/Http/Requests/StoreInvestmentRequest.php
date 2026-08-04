<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvestmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'investor_name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'investment_date' => 'required|date',
            'status' => 'required|string|in:active,completed,withdrawn',
            'remarks' => 'nullable|string'
        ];
    }
}
