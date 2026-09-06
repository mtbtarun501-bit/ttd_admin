<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueImportBooking extends Model
{
    protected $fillable = [
        'revenue_import_id', 'agent_name', 'booking_reference',
        'service_name', 'ticket_cost', 'service_charge', 'member_count'
    ];

    public function revenueImport()
    {
        return $this->belongsTo(RevenueImport::class);
    }

    public function members()
    {
        return $this->hasMany(RevenueImportMember::class);
    }

    public function revenue()
    {
        return $this->hasOne(Revenue::class, 'revenue_import_booking_id');
    }
}
