<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExchangeOffer extends Model
{
    protected $fillable = ['product_id', 'max_exchange_value', 'is_active', 'terms'];

    protected $casts = [
        'max_exchange_value' => 'decimal:2',
        'is_active'          => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate estimated exchange value based on condition, capped
     * against the old phone's own market price.
     *
     * $oldPhonePrice is required to return anything above zero — without
     * a verified reference price for the phone actually being traded in,
     * there's nothing to cap the value against, so no discount is
     * granted. This is deliberately fail-closed: it's what actually
     * closes the loophole where a customer could trade in literally any
     * phone and still get up to the full max_exchange_value configured
     * on whatever they're buying, regardless of what the old phone is
     * actually worth. A missing price must mean "no instant discount",
     * never "no cap".
     *
     * When a price is supplied, the value is capped at 50% of it for
     * 'excellent' condition, scaled down for lesser conditions using the
     * same multipliers below (good → 37.5%, fair → 25%, poor → 12.5%) —
     * so the cap tightens the same way the base value does as condition
     * gets worse.
     */
    public function calculateValue(string $condition, ?float $oldPhonePrice = null): float
    {
        if ($oldPhonePrice === null || $oldPhonePrice <= 0) {
            return 0;
        }

        $multipliers = [
            'excellent' => 1.00,
            'good'      => 0.75,
            'fair'      => 0.50,
            'poor'      => 0.25,
        ];
        $multiplier = $multipliers[$condition] ?? 0.5;

        $value    = round((float) $this->max_exchange_value * $multiplier, 2);
        $priceCap = round($oldPhonePrice * 0.5 * $multiplier, 2);

        return min($value, $priceCap);
    }
}
