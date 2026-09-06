<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueImportMember extends Model
{
    protected $fillable = [
        'revenue_import_booking_id', 'member_name'
    ];

    public function booking()
    {
        return $this->belongsTo(RevenueImportBooking::class, 'revenue_import_booking_id');
    }
}
