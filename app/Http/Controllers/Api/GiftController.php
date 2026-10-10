<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\CrmSetting;
use App\Models\GiftClaim;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class GiftController extends Controller
{
    /** Is the campaign switched on and fully configured? */
    private function campaign(): ?array
    {
        $enabled = (bool) CrmSetting::get('gift_enabled', false);
        $banner  = CrmSetting::get('gift_banner');
        $name    = trim((string) CrmSetting::get('gift_name', ''));
        if (!$enabled || !$banner || $name === '') return null;

        return [
            'title'       => CrmSetting::get('gift_title', 'A free gift for you!'),
            'gift_name'   => $name,
            'description' => CrmSetting::get('gift_description', ''),
            'banner'      => url('storage/' . $banner),
        ];
    }

    /** Has this device / account / email / phone already claimed? */
    private function alreadyClaimed(?string $deviceId, $user): bool
    {
        $q = GiftClaim::query();
        $q->where(function ($w) use ($deviceId, $user) {
            if ($deviceId) $w->orWhere('device_id', $deviceId);
            if ($user) {
                $w->orWhere('user_id', $user->id)
                  ->orWhere('email', strtolower(trim($user->email)));
                $key = GiftClaim::phoneKey($user->phone);
                if (strlen($key) >= 10) $w->orWhere('phone_key', $key);
            }
        });
        return $q->exists();
    }

    /**
     * Public (token optional). Tells the app whether to show the popup and
     * the home-screen "Claim free gift" button. When a valid token is sent,
     * the account's own email/phone are checked too, not just the device.
     */
    public function show(Request $request)
    {
        $campaign = $this->campaign();
        $deviceId = (string) $request->query('device_id', '');
        $user     = auth('sanctum')->user();

        if (!$campaign || $deviceId === '' || $this->alreadyClaimed($deviceId, $user)) {
            return response()->json(['active' => false]);
        }
        return response()->json(['active' => true] + $campaign);
    }

    /** Authenticated. Records the claim with its delivery address (once per device, email and phone). */
    public function claim(Request $request)
    {
        $data = $request->validate([
            'device_id'  => 'required|string|min:8|max:128',
            'address_id' => 'required|integer',
        ]);

        $campaign = $this->campaign();
        if (!$campaign) {
            return response()->json(['message' => 'This offer is not available right now.'], 422);
        }

        $user = $request->user();
        $phoneKey = GiftClaim::phoneKey($user->phone);
        if (strlen($phoneKey) < 10) {
            return response()->json(['message' => 'Add a valid phone number to your profile to claim the gift.'], 422);
        }

        $address = Address::where('id', $data['address_id'])->where('user_id', $user->id)->first();
        if (!$address) {
            return response()->json(['message' => 'Please choose one of your saved addresses.'], 422);
        }

        if ($this->alreadyClaimed($data['device_id'], $user)) {
            return response()->json([
                'message' => 'The free gift has already been claimed on this account, device, email or phone.',
            ], 409);
        }

        try {
            $claim = GiftClaim::create([
                'user_id'       => $user->id,
                'device_id'     => $data['device_id'],
                'email'         => strtolower(trim($user->email)),
                'phone_key'     => $phoneKey,
                'gift_name'     => $campaign['gift_name'],
                'status'        => 'pending',
                'ip'            => $request->ip(),
                'ship_name'     => $address->full_name,
                'ship_phone'    => $address->phone,
                'ship_address1' => $address->address_line1,
                'ship_address2' => $address->address_line2,
                'ship_city'     => $address->city,
                'ship_state'    => $address->state,
                'ship_pincode'  => $address->pincode,
                'ship_country'  => $address->country,
            ]);
        } catch (QueryException $e) {
            // A concurrent request won the race: a UNIQUE index rejected us.
            return response()->json(['message' => 'The free gift has already been claimed.'], 409);
        }

        return response()->json([
            'message' => '🎉 Gift claimed! We will confirm and ship it soon.',
            'claim'   => $this->format($claim),
        ], 201);
    }

    /** Authenticated. The customer's "My Claimed Gifts" list. */
    public function mine(Request $request)
    {
        $claims = GiftClaim::where('user_id', $request->user()->id)->latest()->get();
        return response()->json(['claims' => $claims->map(fn ($c) => $this->format($c))]);
    }

    private function format(GiftClaim $c): array
    {
        return [
            'id'           => $c->id,
            'claim_number' => $c->claim_number,
            'gift_name'    => $c->gift_name,
            'status'       => $c->status,
            'ship_name'    => $c->ship_name,
            'ship_phone'   => $c->ship_phone,
            'address'      => $c->full_address,
            'claimed_at'   => $c->created_at->toIso8601String(),
            'confirmed_at' => $c->confirmed_at?->toIso8601String(),
        ];
    }
}
