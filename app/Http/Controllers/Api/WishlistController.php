<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Wishlist;

class WishlistController extends Controller
{
    public function index()
    {
        $items = Wishlist::with([
                'product.brand',
                'product.category',
                'product.images',
                'product.variants',
                'product.exchangeOffer',
            ])
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        // Filter out any rows where the product was deleted
        $products = $items
            ->filter(fn($w) => $w->product !== null)
            ->map(fn($w) => new ProductResource($w->product))
            ->values();

        return response()->json([
            'items' => $products,
            'count' => $products->count(),
        ]);
    }

    public function toggle($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();

        $existing = Wishlist::where('user_id', auth()->id())
            ->where('product_id', $product->id)
            ->first();

        if ($existing) {
            $existing->delete();
            $inWishlist = false;
        } else {
            Wishlist::create([
                'user_id'    => auth()->id(),
                'product_id' => $product->id,
            ]);
            $inWishlist = true;

            // ── Auto-create CRM lead when product added to wishlist ────────
            // Mirrors the cart-add lead (Api/CartController@add), but only
            // fires on adding (not removing) and scores lower — a wishlist
            // add is a weaker buying signal than a cart add, so it shouldn't
            // outrank actual cart-abandonment leads in a sales queue.
            try {
                $user = auth()->user();
                \App\Services\LoyaltyService::syncContactFromUser($user);
                $contact = $user->fresh()->crmContact;
                if ($contact) {
                    \App\Models\CrmLead::create([
                        'crm_contact_id'      => $contact->id,
                        'title'               => $product->name . ' — Wishlist Interest',
                        'value'               => $product->getCurrentPrice(),
                        'stage'               => 'new',
                        'score'               => 10,
                        'source'              => 'organic',
                        'product_interest'    => $product->name,
                        'expected_close_date' => now()->addDays(14)->toDateString(),
                        'notes'               => 'Auto-created when user added product to wishlist.',
                    ]);
                }
            } catch (\Throwable) {
                // Never block a wishlist toggle due to CRM errors
            }
        }

        return response()->json([
            'in_wishlist' => $inWishlist,
            'message'     => $inWishlist ? 'Added to wishlist!' : 'Removed from wishlist.',
        ]);
    }
}
