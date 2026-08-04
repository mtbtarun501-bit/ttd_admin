<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SevaType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cooldown_months',
        'display_order',
        'status',
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
