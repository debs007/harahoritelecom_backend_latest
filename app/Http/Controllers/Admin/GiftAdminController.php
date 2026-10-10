<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CrmSetting;
use App\Models\GiftClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GiftAdminController extends Controller
{
    /** Campaign settings (what the gift is, banner, on/off). */
    public function index()
    {
        return view('admin.gift.index', [
            'enabled'     => (bool) CrmSetting::get('gift_enabled', false),
            'title'       => CrmSetting::get('gift_title', 'A free gift for you!'),
            'giftName'    => CrmSetting::get('gift_name', ''),
            'description' => CrmSetting::get('gift_description', ''),
            'banner'      => CrmSetting::get('gift_banner'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'title'       => 'required|string|max:80',
            'gift_name'   => 'required|string|max:120',
            'description' => 'nullable|string|max:300',
            'banner'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        if ($request->hasFile('banner')) {
            $old = CrmSetting::get('gift_banner');
            CrmSetting::set('gift_banner', $this->saveImage($request->file('banner')));
            if ($old) Storage::disk('public')->delete($old);
        }

        $enabled = $request->boolean('enabled');
        if ($enabled && !CrmSetting::get('gift_banner')) {
            return back()->withErrors(['banner' => 'Upload a banner before switching the gift on.'])->withInput();
        }

        CrmSetting::set('gift_enabled', $enabled);
        CrmSetting::set('gift_title', $data['title']);
        CrmSetting::set('gift_name', $data['gift_name']);
        CrmSetting::set('gift_description', $data['description'] ?? '');

        return back()->with('success', 'Free gift settings saved.');
    }

    /** Claims list: pending / confirmed, with delivery address. */
    public function claims(Request $request)
    {
        $status = $request->query('status');
        $claims = GiftClaim::with('user:id,name,email,phone')
            ->when(in_array($status, GiftClaim::STATUSES, true), fn ($q) => $q->where('status', $status))
            ->latest()->paginate(25)->withQueryString();

        return view('admin.gift.claims', [
            'claims'  => $claims,
            'status'  => $status,
            'counts'  => [
                'all'       => GiftClaim::count(),
                'pending'   => GiftClaim::where('status', 'pending')->count(),
                'confirmed' => GiftClaim::where('status', 'confirmed')->count(),
            ],
        ]);
    }

    public function updateStatus(Request $request, GiftClaim $claim)
    {
        $data = $request->validate(['status' => 'required|in:' . implode(',', GiftClaim::STATUSES)]);

        $claim->update([
            'status'       => $data['status'],
            'confirmed_at' => $data['status'] === 'confirmed' ? ($claim->confirmed_at ?? now()) : null,
        ]);

        return back()->with('success', "{$claim->claim_number} marked {$data['status']}.");
    }

    public function invoice(GiftClaim $claim)
    {
        abort_if($claim->status !== 'confirmed', 422, 'Confirm the claim before generating its invoice.');
        $claim->load('user');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.gift.invoice', compact('claim'))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'          => 'sans-serif',
                'isRemoteEnabled'      => false,
                'isHtml5ParserEnabled' => true,
            ]);

        return $pdf->download('Gift-Invoice-' . $claim->claim_number . '.pdf');
    }

    private function saveImage($file): string
    {
        $path = 'gift/' . uniqid() . '_' . time() . '.webp';
        try {
            $img = \Intervention\Image\Facades\Image::make($file->getRealPath())
                ->resize(900, null, function ($c) { $c->aspectRatio(); $c->upsize(); })
                ->encode('webp', 85);
            Storage::disk('public')->put($path, $img);
        } catch (\Throwable $e) {
            Log::error('Gift banner save failed: ' . $e->getMessage());
            $path = 'gift/' . uniqid() . '_' . time() . '.' . ($file->getClientOriginalExtension() ?: 'jpg');
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
        }
        return $path;
    }
}
