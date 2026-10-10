@extends('layouts.admin')
@section('title','Free Gift')
@section('breadcrumb')<span class="mx-1">/</span><span class="text-gray-700">Free Gift</span>@endsection

@section('content')
<div class="space-y-5 max-w-3xl">
    <h1 class="text-2xl font-bold text-gray-900">🎁 Free Gift Popup</h1>

    <form method="POST" action="{{ route('admin.gift.update') }}" enctype="multipart/form-data"
          class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
        @csrf @method('PATCH')

        <div class="flex items-center justify-between">
            <div>
                <p class="font-bold text-gray-800">Show gift popup in the app</p>
                <p class="text-sm text-gray-500">Each customer can claim once — checked by device, email and phone. Changing the gift or banner affects new claims only.</p>
            </div>
            <div x-data="{ on: {{ old('enabled', $enabled) ? 'true' : 'false' }} }">
                <input type="hidden" name="enabled" :value="on ? 1 : 0">
                <button type="button" @click="on = !on" :class="on ? 'bg-indigo-600' : 'bg-gray-300'"
                        class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors">
                    <span :class="on ? 'translate-x-6' : 'translate-x-1'"
                          class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform"></span>
                </button>
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Headline</label>
            <input name="title" value="{{ old('title', $title) }}" maxlength="80"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            @error('title')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">What is the gift?</label>
            <input name="gift_name" value="{{ old('gift_name', $giftName) }}" maxlength="120" placeholder="e.g. Harahori Coffee Mug"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            <p class="text-xs text-gray-500 mt-1">Shown to the customer and printed on the claim invoice.</p>
            @error('gift_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Short description (optional)</label>
            <input name="description" value="{{ old('description', $description) }}" maxlength="300"
                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
            @error('description')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-1">Popup banner image</label>
            @if($banner)
                <img src="{{ asset('storage/'.$banner) }}" class="w-64 rounded-lg border mb-2" alt="">
            @endif
            <input type="file" name="banner" accept="image/*" class="text-sm">
            <p class="text-xs text-gray-500 mt-1">Recommended 900 × 600 px (3:2), JPG/PNG/WebP up to 5 MB. Keep key text away from the edges.</p>
            @error('banner')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
        </div>

        <button class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700">Save</button>
    </form>

    <a href="{{ route('admin.gift-claims.index') }}" class="inline-block text-indigo-600 text-sm font-semibold hover:underline">
        View gift claims &rarr;
    </a>
</div>
@endsection
