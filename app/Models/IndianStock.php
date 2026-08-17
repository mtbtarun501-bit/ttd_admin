<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndianStock extends Model
{
    protected $fillable = [
        'stock_symbol',
        'stock_name',
        'quantity',
        'buy_price',
        'buy_date',
    ];

    protected $casts = [
        'buy_date' => 'date',
        'buy_price' => 'decimal:2',
        'quantity' => 'integer',
    ];
}
