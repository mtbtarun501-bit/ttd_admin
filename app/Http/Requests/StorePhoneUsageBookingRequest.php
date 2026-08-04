<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePhoneUsageBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'seva_type_id' => 'required|exists:seva_types,id',
            'booking_date' => 'required|date|before_or_equal:today',
            'remarks' => 'nullable|string|max:1000',
        ];
    }
    
    public function messages(): array
    {
        return [
            'booking_date.before_or_equal' => 'Booking date cannot be in the future unless explicitly allowed.',
        ];
    }
}
