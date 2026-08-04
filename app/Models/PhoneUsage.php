<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_name',
        'mobile_number',
        'status',
        'remarks',
    ];

    public function serviceStatuses()
    {
        return $this->hasMany(PhoneUsageServiceStatus::class);
    }

    public function bookingHistories()
    {
        return $this->hasMany(PhoneUsageBookingHistory::class);
    }
}
