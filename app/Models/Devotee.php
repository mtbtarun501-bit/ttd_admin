<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Devotee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'is_head_of_family',
        'head_devotee_id',
        'referred_devotee_id',
        'preferred_booking_type_id',
        'name',
        'age',
        'gender',
        'aadhaar',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'pin_code',
        'gothram',
        'photo',
        'remarks',
    ];

    /**
     * Get the user that created this devotee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected $casts = [
        'is_head_of_family' => 'boolean',
    ];

    public function headFamilyMember()
    {
        return $this->belongsTo(Devotee::class, 'head_devotee_id');
    }

    public function familyMembers()
    {
        return $this->hasMany(Devotee::class, 'head_devotee_id');
    }

    /**
     * The referred agent who gets credit for this devotee / family.
     */
    public function referredAgent()
    {
        return $this->belongsTo(Devotee::class, 'referred_devotee_id');
    }

    /**
     * The families this agent has referred.
     */
    public function referredFamilies()
    {
        return $this->hasMany(Devotee::class, 'referred_devotee_id');
    }

    public function preferredBookingType()
    {
        return $this->belongsTo(BookingType::class, 'preferred_booking_type_id');
    }

    public function groupBookings()
    {
        return $this->belongsToMany(Booking::class, 'booking_devotee');
    }

    public function primaryBookings()
    {
        return $this->hasMany(Booking::class, 'devotee_id');
    }
}
