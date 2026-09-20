<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Agent extends Model
{
    use HasFactory;

    public const TYPE_IN_PARTNER = 'in_partner';
    public const TYPE_OUT_PARTNER = 'out_partner';

    public const TYPES = [
        self::TYPE_IN_PARTNER => 'In Partner',
        self::TYPE_OUT_PARTNER => 'Out Partner',
    ];

    protected $fillable = [
        'name',
        'phone',
        'agent_type',
        'status',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function getAgentTypeLabelAttribute()
    {
        return self::TYPES[$this->agent_type] ?? ucfirst(str_replace('_', ' ', $this->agent_type));
    }
}