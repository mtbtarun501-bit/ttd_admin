<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRevenueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'revenue_date' => 'required|date',
            'remarks' => 'nullable|string'
        ];
    }
}
