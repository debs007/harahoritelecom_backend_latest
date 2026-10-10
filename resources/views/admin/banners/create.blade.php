@extends('layouts.admin')
@section('title','Add Banner')
@section('breadcrumb')<span class="mx-1">/</span><a href="{{ route('admin.banners.index') }}" class="hover:text-gray-700">Banners</a><span class="mx-1">/</span><span class="text-gray-700">Add</span>@endsection

@section('content')
<div class="max-w-xl">
    <h1 class="text-2xl font-bold text-gray-900 mb-5">Add Banner</h1>
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            <div>
                <label class="label">Banner Image *</label>
                <input type="file" name="image" accept="image/*" required class="input text-sm">
                <p class="text-xs text-gray-400 mt-1">Wide promotional image — uploaded as-is (resized to max 1200px wide, aspect ratio kept, not cropped).</p>
                @error('image')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="label">Title <span class="text-gray-400 font-normal">optional, for your reference only — not shown to customers</span></label>
                <input type="text" name="title" value="{{ old('title') }}" class="input" placeholder="e.g. iPhone 17 Pre-Booking Banner">
            </div>

            <div>
                <label class="label">Links To Product <span class="text-gray-400 font-normal">optional</span></label>
                <select name="product_id" class="input">
                    <option value="">— No link —</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}" {{ old('product_id') == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-400 mt-1">Tapping the banner in the app opens this product's detail page — works the same for a normal product or a pre-booking one.</p>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="label">Sort Order</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', 0) }}" class="input" min="0">
                    <p class="text-xs text-gray-400 mt-1">Lower shows first.</p>
                </div>
                <div class="flex items-end pb-1">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" checked class="rounded border-gray-300 text-indigo-600">
                        <span class="text-sm font-semibold text-gray-700">Active</span>
                    </label>
                </div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-lg text-sm font-bold hover:bg-indigo-700 transition">Create Banner</button>
                <a href="{{ route('admin.banners.index') }}" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
