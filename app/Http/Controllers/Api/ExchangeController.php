<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExchangeOffer;
use App\Models\Product;
use App\Models\TradeInPrice;
use Illuminate\Http\Request;

class ExchangeController extends Controller
{
    /**
     * Search the trade-in reference list — what customers pick their old
     * phone from instead of free-typing it, so the exchange value can
     * actually be capped against a real price.
     */
    public function searchModels(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100']);

        $query = TradeInPrice::where('is_active', true);
        if ($request->q) {
            $q = $request->q;
            $query->where(fn($qq) => $qq->where('brand', 'like', "%{$q}%")->orWhere('model', 'like', "%{$q}%"));
        }

        $results = $query->orderBy('brand')->orderBy('model')->take(20)->get()
            ->map(fn($p) => [
                'id'    => $p->id,
                'brand' => $p->brand,
                'model' => $p->model,
                'price' => (float) $p->price,
            ]);

        return response()->json(['models' => $results]);
    }

    public function getOffer($slug)
    {
        $product = Product::where('slug', $slug)->firstOrFail();
        $offer   = ExchangeOffer::where('product_id', $product->id)
            ->where('is_active', true)->first();

        if (!$offer) {
            return response()->json(['offer' => null]);
        }

        return response()->json([
            'offer' => [
                'max_value'    => (float) $offer->max_exchange_value,
                'terms'        => $offer->terms,
                'multipliers'  => [
                    'excellent' => 1.00,
                    'good'      => 0.75,
                    'fair'      => 0.50,
                    'poor'      => 0.25,
                ],
            ],
        ]);
    }

    public function estimate(Request $request)
    {
        $request->validate([
            'product_id'        => 'required|exists:products,id',
            'condition'         => 'required|in:excellent,good,fair,poor',
            'trade_in_price_id' => 'nullable|exists:trade_in_prices,id',
        ]);

        $offer = ExchangeOffer::where('product_id', $request->product_id)
            ->where('is_active', true)->first();

        if (!$offer) {
            return response()->json(['estimated_value' => 0]);
        }

        // No reference model picked ("Other — not listed") → no instant
        // estimate. The request still goes through for manual review
        // rather than granting an uncapped discount for an unknown phone.
        if (!$request->trade_in_price_id) {
            return response()->json([
                'estimated_value' => 0,
                'requires_review' => true,
            ]);
        }

        $tradeIn = TradeInPrice::findOrFail($request->trade_in_price_id);

        return response()->json([
            'estimated_value' => $offer->calculateValue($request->condition, (float) $tradeIn->price),
            'requires_review' => false,
        ]);
    }

    public function verifyImei(Request $request)
    {
        $request->validate(['imei' => 'required|string|size:15|regex:/^[0-9]+$/']);

        $imei = $request->imei;
        $sum  = 0;
        for ($i = 0; $i < 15; $i++) {
            $d = (int) $imei[$i];
            if ($i % 2 === 1) { $d *= 2; if ($d > 9) $d -= 9; }
            $sum += $d;
        }

        $valid = ($sum % 10 === 0);
        return response()->json([
            'valid'   => $valid,
            'message' => $valid ? 'IMEI verified successfully.' : 'Invalid IMEI number.',
        ]);
    }
}
