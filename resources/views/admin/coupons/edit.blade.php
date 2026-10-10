@extends('layouts.admin')
@section('title','Edit Coupon')
@section('breadcrumb')<span class="mx-1">/</span><a href="{{ route('admin.coupons.index') }}" class="hover:text-gray-700">Coupons</a><span class="mx-1">/</span><span class="text-gray-700">Edit</span>@endsection

@section('content')
<div class="max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-5">Edit Coupon — {{ $coupon->code }}</h1>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.coupons.update', $coupon) }}"
              class="space-y-5" x-data="{ productId: '{{ old('product_id', $coupon->product_id ?? '') }}' }">
            @csrf @method('PUT')

            <div>
                <label class="label">Specific Product <span class="text-gray-400 font-normal">optional</span></label>
                <select name="product_id" x-model="productId" class="input">
                    <option value="">— Site-wide coupon —</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Leave blank for a normal site-wide coupon. When set, it only shows and works on that product's page, and only discounts that item.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Coupon Code *</label>
                    <input type="text" name="code" value="{{ old('code', $coupon->code) }}"
                           class="input uppercase tracking-widest font-mono" required style="text-transform:uppercase">
                    @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="label">Description</label>
                    <input type="text" name="description" value="{{ old('description', $coupon->description) }}" class="input">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Discount Type *</label>
                    <select name="type" class="input" required x-effect="if (productId && $el.value !== 'fixed') $el.value = 'fixed'">
                        <option value="percent" :disabled="productId !== ''" {{ $coupon->type === 'percent' ? 'selected' : '' }}>Percentage (%)</option>
                        <option value="fixed"   {{ $coupon->type === 'fixed'   ? 'selected' : '' }}>Fixed Amount (₹)</option>
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="productId !== ''">Locked to Fixed Amount for product-specific coupons.</p>
                </div>
                <div>
                    <label class="label">Discount Value *</label>
                    <input type="number" name="value" value="{{ old('value', $coupon->value) }}" class="input" required step="0.01" min="0">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Minimum Order Amount (₹)</label>
                    <input type="number" name="min_order_amount" value="{{ old('min_order_amount', $coupon->min_order_amount) }}" class="input" step="0.01" min="0">
                </div>
                <div>
                    <label class="label">Maximum Discount (₹)</label>
                    <input type="number" name="max_discount" value="{{ old('max_discount', $coupon->max_discount) }}" class="input" step="0.01" min="0">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Usage Limit</label>
                    <input type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit) }}" class="input" min="1">
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" {{ $coupon->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm font-semibold text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="label">Start Date</label>
                    <input type="datetime-local" name="starts_at" value="{{ $coupon->starts_at ? $coupon->starts_at->format('Y-m-d\TH:i') : '' }}" class="input text-sm">
                </div>
                <div>
                    <label class="label">Expiry Date</label>
                    <input type="datetime-local" name="expires_at" value="{{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '' }}" class="input text-sm">
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">Update Coupon</button>
                <a href="{{ route('admin.coupons.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
