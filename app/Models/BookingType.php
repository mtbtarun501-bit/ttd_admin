<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingType extends Model
{
    protected $fillable = [
        'name', 'waiting_days', 'status'
    ];
}
