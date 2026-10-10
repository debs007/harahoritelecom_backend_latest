@extends('layouts.admin')
@section('title','Gift Claims')
@section('breadcrumb')<span class="mx-1">/</span><span class="text-gray-700">Gift Claims</span>@endsection

@section('content')
<div class="space-y-5">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">🎁 Gift Claims</h1>
        <a href="{{ route('admin.gift.index') }}" class="text-sm text-indigo-600 font-semibold hover:underline">Gift settings</a>
    </div>

    <div class="flex gap-2 text-sm">
        @foreach(['' => 'All', 'pending' => 'Pending', 'confirmed' => 'Confirmed'] as $key => $label)
            <a href="{{ route('admin.gift-claims.index', $key ? ['status' => $key] : []) }}"
               class="px-3 py-1.5 rounded-full border {{ ($status ?? '') === $key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-gray-700 border-gray-200' }}">
                {{ $label }} ({{ $counts[$key ?: 'all'] }})
            </a>
        @endforeach
    </div>

    <div class="bg-white rounded-xl border border-gray-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-gray-500">
                <tr>
                    <th class="px-4 py-2">Claim</th><th>Customer</th><th>Gift</th>
                    <th>Delivery address</th><th>Status</th><th class="pr-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($claims as $c)
                <tr class="border-t align-top">
                    <td class="px-4 py-3">
                        <div class="font-semibold">{{ $c->claim_number }}</div>
                        <div class="text-xs text-gray-500">{{ $c->created_at->format('d M Y, h:i A') }}</div>
                    </td>
                    <td class="py-3">
                        <div>{{ $c->user?->name ?? '—' }}</div>
                        <div class="text-xs text-gray-500">{{ $c->email }}</div>
                    </td>
                    <td class="py-3">{{ $c->gift_name }}</td>
                    <td class="py-3 max-w-xs">
                        <div class="font-medium">{{ $c->ship_name }} · {{ $c->ship_phone }}</div>
                        <div class="text-xs text-gray-600">{{ $c->full_address }}</div>
                    </td>
                    <td class="py-3">
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $c->status === 'confirmed' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800' }}">
                            {{ ucfirst($c->status) }}
                        </span>
                    </td>
                    <td class="py-3 pr-4 text-right whitespace-nowrap">
                        <form method="POST" action="{{ route('admin.gift-claims.status', $c) }}" class="inline">
                            @csrf @method('PATCH')
                            <select name="status" onchange="this.form.submit()" class="border border-gray-300 rounded-lg px-2 py-1 text-xs">
                                @foreach(\App\Models\GiftClaim::STATUSES as $s)
                                    <option value="{{ $s }}" @selected($c->status === $s)>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </form>
                        @if($c->status === 'confirmed')
                            <a href="{{ route('admin.gift-claims.invoice', $c) }}" class="ml-2 text-xs text-indigo-600 font-semibold hover:underline">Invoice</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-6 text-gray-400">No claims yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $claims->links() }}
</div>
@endsection
