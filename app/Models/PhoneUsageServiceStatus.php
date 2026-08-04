<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhoneUsageServiceStatus extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_usage_id',
        'seva_type_id',
        'last_booked_date',
        'next_eligible_date',
    ];

    protected $casts = [
        'last_booked_date' => 'date',
        'next_eligible_date' => 'date',
    ];

    public function phoneUsage()
    {
        return $this->belongsTo(PhoneUsage::class);
    }

    public function sevaType()
    {
        return $this->belongsTo(SevaType::class);
    }
}
