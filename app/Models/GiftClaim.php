<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftClaim extends Model
{
    public const STATUSES = ['pending', 'confirmed'];

    protected $fillable = [
        'user_id', 'device_id', 'email', 'phone_key', 'gift_name', 'status', 'ip',
        'ship_name', 'ship_phone', 'ship_address1', 'ship_address2',
        'ship_city', 'ship_state', 'ship_pincode', 'ship_country', 'confirmed_at',
    ];

    protected $casts = ['confirmed_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }

    public function getClaimNumberAttribute(): string
    {
        return 'GIFT-' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    public function getFullAddressAttribute(): string
    {
        return implode(', ', array_filter([
            $this->ship_address1, $this->ship_address2, $this->ship_city,
            $this->ship_state, $this->ship_pincode, $this->ship_country,
        ]));
    }

    /** "+91 98765-43210" and "09876543210" both become "9876543210". */
    public static function phoneKey(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);
        return substr($digits, -10);
    }
}
