<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingType extends Model
{
    const TYPE_IN_PARTNER = 'in_partner';
    const TYPE_OUT_PARTNER = 'out_partner';

    protected $fillable = [
        'name', 'waiting_days', 'status', 'price', 'commission_rate',
        'in_partner_commission_rate', 'out_partner_commission_rate', 'seva_type_id'
    ];

    /**
     * Get the commission rate for the given agent type.
     * Falls back to the legacy single commission_rate when no matching rate is set.
     */
    public function commissionForType(?string $agentType)
    {
        if ($agentType === self::TYPE_IN_PARTNER) {
            return $this->in_partner_commission_rate ?: $this->commission_rate;
        }

        if ($agentType === self::TYPE_OUT_PARTNER) {
            return $this->out_partner_commission_rate ?: $this->commission_rate;
        }

        return $this->commission_rate;
    }

    /**
     * The seva type this booking type maps to for phone usage tracking.
     */
    public function sevaType()
    {
        return $this->belongsTo(SevaType::class);
    }
}