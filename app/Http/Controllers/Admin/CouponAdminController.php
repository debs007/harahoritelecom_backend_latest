<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;

class CouponAdminController extends Controller
{
    public function index()
    {
        $coupons = Coupon::with('product')->latest()->paginate(20);
        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        return view('admin.coupons.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id'        => 'nullable|exists:products,id',
            'code'              => 'required|string|max:50|unique:coupons,code',
            'description'       => 'nullable|string|max:255',
            'type'              => 'required|in:percent,fixed',
            'value'             => 'required|numeric|min:0',
            'min_order_amount'  => 'nullable|numeric|min:0',
            'max_discount'      => 'nullable|numeric|min:0',
            'usage_limit'       => 'nullable|integer|min:1',
            'is_active'         => 'nullable|boolean',
            'starts_at'         => 'nullable|date',
            'expires_at'        => 'nullable|date|after_or_equal:starts_at',
        ]);

        // Product-specific coupons only make sense as a flat ₹ amount off
        // that one product — a percentage invites confusion about what
        // it's a percentage of once it's scoped to a single item.
        if (! empty($data['product_id'])) {
            $data['type'] = 'fixed';
        }

        $data['code'] = strtoupper($data['code']);
        Coupon::create($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created.');
    }

    public function edit(Coupon $coupon)
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        return view('admin.coupons.edit', compact('coupon', 'products'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $data = $request->validate([
            'product_id'       => 'nullable|exists:products,id',
            'code'             => 'required|string|max:50|unique:coupons,code,' . $coupon->id,
            'description'      => 'nullable|string|max:255',
            'type'             => 'required|in:percent,fixed',
            'value'            => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_discount'     => 'nullable|numeric|min:0',
            'usage_limit'      => 'nullable|integer|min:1',
            'is_active'        => 'nullable|boolean',
            'starts_at'        => 'nullable|date',
            'expires_at'       => 'nullable|date',
        ]);

        if (! empty($data['product_id'])) {
            $data['type'] = 'fixed';
        }

        $data['code'] = strtoupper($data['code']);
        $coupon->update($data);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated.');
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return back()->with('success', 'Coupon deleted.');
    }

    public function show(Coupon $coupon) { return $this->edit($coupon); }
}
