<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhoneUsageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'member_name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20|unique:phone_usages,mobile_number',
            'status' => 'nullable|string|in:Active,Inactive',
            'remarks' => 'nullable|string|max:1000',
            'seva_dates' => 'nullable|array',
            'seva_dates.*' => 'nullable|date',
        ];
    }
}
