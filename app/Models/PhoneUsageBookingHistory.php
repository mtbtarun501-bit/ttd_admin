<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneUsageBookingHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_usage_id',
        'seva_type_id',
        'booking_date',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    public function phoneUsage()
    {
        return $this->belongsTo(PhoneUsage::class);
    }

    public function sevaType()
    {
        return $this->belongsTo(SevaType::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
