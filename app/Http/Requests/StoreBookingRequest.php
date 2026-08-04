<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'devotee_id' => 'required|exists:devotees,id',
            'booking_type_id' => 'required|exists:booking_types,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'preferred_date' => 'nullable|date|after_or_equal:booking_date',
            'remarks' => 'nullable|string',
            'created_by' => 'nullable|exists:users,id',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'exists:devotees,id'
        ];
    }
}
