<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Revenue extends Model
{
    use LogsActivity;

    protected $fillable = [
        'agent_name', 'source', 'amount', 'revenue_date', 'remarks', 'created_by', 'revenue_import_booking_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
        ->logFillable()
        ->logOnlyDirty()
        ->dontSubmitEmptyLogs();
    }

    public function importBooking()
    {
        return $this->belongsTo(RevenueImportBooking::class, 'revenue_import_booking_id');
    }
}
