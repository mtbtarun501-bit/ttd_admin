<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioHolding extends Model
{
    public const TYPE_CRYPTO = 'crypto';
    public const TYPE_STOCK = 'stock';

    public const TYPES = [
        self::TYPE_CRYPTO => 'Crypto',
        self::TYPE_STOCK => 'Stock',
    ];

    protected $fillable = [
        'asset_type',
        'symbol',
        'name',
        'quantity',
        'buy_price',
        'buy_date',
        'platform',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'float',
        'buy_price' => 'float',
        'buy_date' => 'date',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAssetTypeLabelAttribute()
    {
        return self::TYPES[$this->asset_type] ?? ucfirst($this->asset_type);
    }
}