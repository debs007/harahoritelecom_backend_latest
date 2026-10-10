<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeInPrice extends Model
{
    protected $fillable = ['brand', 'model', 'price', 'is_active'];

    protected $casts = [
        'price'     => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function exchangeRequests()
    {
        return $this->hasMany(ExchangeRequest::class);
    }
}
