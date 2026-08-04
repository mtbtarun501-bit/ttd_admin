<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{

    protected $fillable = [
        'booking_no', 'devotee_id', 'booking_type_id', 
        'booking_date', 'preferred_date', 'status', 
        'remarks', 'created_by'
    ];

    // The primary booker (often head of family)
    public function devotee()
    {
        return $this->belongsTo(Devotee::class);
    }

    public function bookingType()
    {
        return $this->belongsTo(BookingType::class);
    }

    // All members attending on this booking
    public function attendees()
    {
        return $this->belongsToMany(Devotee::class, 'booking_devotee');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
