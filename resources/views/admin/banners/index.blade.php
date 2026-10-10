@extends('layouts.admin')
@section('title','Banners')
@section('breadcrumb')<span class="mx-1">/</span><span class="text-gray-700">Banners</span>@endsection

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between flex-wrap gap-3">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Home Banners</h1>
            <p class="text-sm text-gray-500 mt-0.5">Shown as a slider at the top of the Flutter app's home screen.</p>
        </div>
        <a href="{{ route('admin.banners.create') }}"
           class="inline-flex items-center gap-2 bg-indigo-600 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
            + Add Banner
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Banner</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Links To</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Order</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Status</th>
                        <th class="text-left px-4 py-3 font-medium text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($banners as $banner)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <img src="{{ Storage::url($banner->image) }}"
                                     class="w-24 h-10 rounded-lg object-cover border border-gray-200 flex-shrink-0" alt="">
                                <p class="font-semibold text-gray-800 text-sm max-w-[220px] truncate">{{ $banner->title ?: '(no title)' }}</p>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            @if($banner->product)
                                {{ $banner->product->name }}
                                @if($banner->product->is_prebooking)<span class="badge badge-indigo text-xs ml-1">Pre-Booking</span>@endif
                            @else
                                <span class="text-gray-400">— not linked —</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $banner->sort_order }}</td>
                        <td class="px-4 py-3">
                            <span class="badge {{ $banner->is_active ? 'badge-green' : 'badge-gray' }}">{{ $banner->is_active ? 'Active' : 'Hidden' }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.banners.edit', $banner) }}" class="text-indigo-600 hover:underline text-xs font-semibold">Edit</a>
                                <form method="POST" action="{{ route('admin.banners.destroy', $banner) }}" onsubmit="return confirm('Delete this banner?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:underline text-xs font-semibold">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-400">No banners yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
