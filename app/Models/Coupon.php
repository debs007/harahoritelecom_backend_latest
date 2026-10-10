<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'product_id', 'code', 'description', 'type', 'value',
        'min_order_amount', 'max_discount',
        'usage_limit', 'used_count', 'is_active',
        'starts_at', 'expires_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active'  => 'boolean',
        'value'      => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function isProductSpecific(): bool
    {
        return ! is_null($this->product_id);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) return false;
        if ($this->starts_at && $this->starts_at->isFuture()) return false;
        if ($this->expires_at && $this->expires_at->isPast()) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if ($amount < $this->min_order_amount) return 0;

        $discount = $this->type === 'percent'
            ? ($amount * $this->value / 100)
            : (float) $this->value;

        if ($this->max_discount) {
            $discount = min($discount, $this->max_discount);
        }

        // A discount can never exceed the amount it's being applied
        // against — matters most for product-specific flat coupons,
        // where $amount is just that one product's line total.
        return round(min($discount, $amount), 2);
    }
}
