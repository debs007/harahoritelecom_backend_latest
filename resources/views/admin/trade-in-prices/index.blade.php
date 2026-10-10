@extends('layouts.admin')
@section('title','Trade-In Prices')
@section('breadcrumb')<span class="mx-1">/</span><span class="text-gray-700">Trade-In Prices</span>@endsection

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Trade-In Reference Prices</h1>
            <p class="text-sm text-gray-500 mt-0.5 max-w-2xl">
                Current Indian market price for each model — this is what a customer's exchange value gets
                capped against (max 50% of this price for "Excellent" condition, less for lower conditions),
                so an old phone can't be worth more in trade-in credit than it actually costs new.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('admin.trade-in-prices.seed') }}" onsubmit="return confirm('Add any of your active products not already in this list, using their current selling price? You can edit or remove entries afterward.')">
                @csrf
                <button type="submit" class="inline-flex items-center gap-2 border border-gray-300 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    ⚡ Seed from my products
                </button>
            </form>
            <a href="{{ route('admin.trade-in-prices.create') }}"
               class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                + Add Model
            </a>
        </div>
    </div>

    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search brand or model…" class="input max-w-xs text-sm">
        <button type="submit" class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50">Search</button>
    </form>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Brand</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Model</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Reference Price</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Max Trade-In (Excellent)</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($prices as $p)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-gray-600">{{ $p->brand }}</td>
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $p->model }}</td>
                        <td class="px-4 py-3 text-gray-600">₹{{ number_format($p->price) }}</td>
                        <td class="px-4 py-3 text-gray-600">₹{{ number_format($p->price * 0.5) }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $p->is_active ? 'badge-green' : 'badge-gray' }}">{{ $p->is_active ? 'Active' : 'Hidden' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.trade-in-prices.edit', $p) }}" class="text-indigo-600 hover:underline text-xs font-semibold">Edit</a>
                                <form method="POST" action="{{ route('admin.trade-in-prices.destroy', $p) }}" onsubmit="return confirm('Remove this reference price?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-400">No trade-in reference prices yet. Try "Seed from my products" to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $prices->links() }}
</div>
@endsection
