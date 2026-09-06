<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevenueImport extends Model
{
    protected $fillable = [
        'batch_month', 'file_name', 'total_bookings', 'total_members',
        'total_ticket_cost', 'total_revenue', 'imported_by'
    ];

    public function bookings()
    {
        return $this->hasMany(RevenueImportBooking::class);
    }

    public function importer()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}
