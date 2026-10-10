<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\TradeInPrice;
use Illuminate\Http\Request;

class TradeInPriceAdminController extends Controller
{
    public function index(Request $request)
    {
        $query = TradeInPrice::query();
        if ($request->search) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('brand', 'like', "%{$s}%")->orWhere('model', 'like', "%{$s}%"));
        }
        $prices = $query->orderBy('brand')->orderBy('model')->paginate(30)->withQueryString();

        return view('admin.trade-in-prices.index', compact('prices'));
    }

    public function create()
    {
        return view('admin.trade-in-prices.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'brand'     => 'required|string|max:100',
            'model'     => 'required|string|max:150',
            'price'     => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->has('is_active');

        TradeInPrice::create($data);

        return redirect()->route('admin.trade-in-prices.index')->with('success', 'Trade-in price added.');
    }

    public function edit(TradeInPrice $tradeInPrice)
    {
        return view('admin.trade-in-prices.edit', ['price' => $tradeInPrice]);
    }

    public function update(Request $request, TradeInPrice $tradeInPrice)
    {
        $data = $request->validate([
            'brand'     => 'required|string|max:100',
            'model'     => 'required|string|max:150',
            'price'     => 'required|numeric|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = $request->has('is_active');

        $tradeInPrice->update($data);

        return redirect()->route('admin.trade-in-prices.index')->with('success', 'Trade-in price updated.');
    }

    public function destroy(TradeInPrice $tradeInPrice)
    {
        $tradeInPrice->delete();
        return back()->with('success', 'Trade-in price removed.');
    }

    /**
     * One-click seed from products you already sell — a starting point,
     * not a substitute for keeping this list current over time.
     */
    public function seedFromCatalog()
    {
        $existing = TradeInPrice::pluck('model')->map(fn($m) => strtolower($m))->all();

        $added = 0;
        Product::with('brand')->active()->chunk(100, function ($products) use (&$added, $existing) {
            foreach ($products as $product) {
                if (in_array(strtolower($product->name), $existing)) continue;
                TradeInPrice::create([
                    'brand'     => $product->brand->name ?? 'Unknown',
                    'model'     => $product->name,
                    'price'     => $product->price,
                    'is_active' => true,
                ]);
                $added++;
            }
        });

        return back()->with('success', "Added {$added} model(s) from your product catalog.");
    }
}
