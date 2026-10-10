@extends('layouts.admin')
@section('title','Settings')
@section('breadcrumb')<span class="mx-1">/</span><span class="text-gray-700">Settings</span>@endsection

@section('content')
<div class="space-y-5 max-w-2xl">
    <h1 class="text-2xl font-bold text-gray-900">Store Settings</h1>

    <form method="POST" action="{{ route('admin.settings.update') }}"
          class="bg-white rounded-xl border border-gray-200 p-5">
        @csrf @method('PATCH')

        <div class="flex items-center justify-between">
            <div>
                <p class="font-bold text-gray-800">Cash on Delivery</p>
                <p class="text-sm text-gray-500 mt-0.5">
                    Site-wide switch for the COD payment option at checkout.
                    You can still enable or disable COD for an individual
                    customer from their profile under
                    <a href="{{ route('admin.users.index') }}" class="text-indigo-600 hover:underline">Users</a> —
                    that per-customer setting always overrides this one.
                </p>
            </div>
            <div x-data="{ on: {{ $codEnabled ? 'true' : 'false' }} }" class="shrink-0 ml-4">
                <input type="hidden" name="cod_enabled" :value="on ? 1 : 0">
                <button type="button" @click="on = !on"
                        :class="on ? 'bg-indigo-600' : 'bg-gray-300'"
                        class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors">
                    <span :class="on ? 'translate-x-6' : 'translate-x-1'"
                          class="inline-block h-5 w-5 transform rounded-full bg-white transition-transform"></span>
                </button>
            </div>
        </div>

        <div class="mt-5 pt-4 border-t border-gray-100">
            <button type="submit" class="bg-indigo-600 text-white px-5 py-2 rounded-lg text-sm font-semibold hover:bg-indigo-700 transition">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
