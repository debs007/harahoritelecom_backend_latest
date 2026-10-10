@extends('layouts.admin')
@section('title','Edit Trade-In Price')
@section('breadcrumb')<span class="mx-1">/</span><a href="{{ route('admin.trade-in-prices.index') }}" class="hover:text-gray-700">Trade-In Prices</a><span class="mx-1">/</span><span class="text-gray-700">Edit</span>@endsection

@section('content')
<div class="max-w-lg">
    <h1 class="text-2xl font-bold text-gray-900 mb-5">Edit Trade-In Model</h1>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.trade-in-prices.update', $price) }}" class="space-y-5">
            @csrf @method('PUT')
            <div>
                <label class="label">Brand *</label>
                <input type="text" name="brand" value="{{ old('brand', $price->brand) }}" required class="input">
                @error('brand')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Model *</label>
                <input type="text" name="model" value="{{ old('model', $price->model) }}" required class="input">
                @error('model')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Current Market Price (₹) *</label>
                <input type="number" name="price" value="{{ old('price', $price->price) }}" required step="0.01" min="0" class="input">
                <p class="text-xs text-gray-400 mt-1">A customer trading this model in "Excellent" condition can get at most ₹{{ number_format($price->price * 0.5) }} right now.</p>
                @error('price')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" name="is_active" value="1" {{ $price->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                <span class="text-sm font-semibold text-gray-700">Active — selectable by customers</span>
            </label>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">Save Changes</button>
                <a href="{{ route('admin.trade-in-prices.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
