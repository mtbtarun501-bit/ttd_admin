<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDevoteeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_head_of_family' => 'boolean',
            'head_devotee_id' => 'nullable|exists:devotees,id',
            'preferred_booking_type_id' => 'nullable|exists:booking_types,id',
            'name' => 'required|string|max:255',
            'age' => 'required|numeric|min:1',
            'gender' => 'required|string',
            'aadhaar' => 'nullable|digits:12',
            'email' => 'nullable|email',
            'phone' => 'nullable|digits:10',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'pin_code' => 'nullable|numeric',
            'gothram' => 'nullable|string',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'remarks' => 'nullable|string'
        ];
    }
}
