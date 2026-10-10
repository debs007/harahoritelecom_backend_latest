<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BannerAdminController extends Controller
{
    public function index()
    {
        $banners = Banner::with('product')->orderBy('sort_order')->orderByDesc('id')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        return view('admin.banners.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'image'      => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
            'title'      => 'nullable|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        $data['image']      = $this->saveBannerImage($request->file('image'));
        $data['is_active']  = $request->has('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        Banner::create($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner created.');
    }

    public function edit(Banner $banner)
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        return view('admin.banners.edit', compact('banner', 'products'));
    }

    public function update(Request $request, Banner $banner)
    {
        $data = $request->validate([
            'image'      => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'title'      => 'nullable|string|max:255',
            'product_id' => 'nullable|exists:products,id',
            'sort_order' => 'nullable|integer|min:0',
            'is_active'  => 'nullable|boolean',
        ]);

        if ($request->hasFile('image')) {
            if ($banner->image) {
                Storage::disk('public')->delete($banner->image);
            }
            $data['image'] = $this->saveBannerImage($request->file('image'));
        }

        $data['is_active']  = $request->has('is_active');
        $data['sort_order'] = $data['sort_order'] ?? 0;

        $banner->update($data);

        return redirect()->route('admin.banners.index')->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        if ($banner->image) {
            Storage::disk('public')->delete($banner->image);
        }
        $banner->delete();

        return back()->with('success', 'Banner deleted.');
    }

    /**
     * Banners are wide promotional graphics the admin has already designed
     * at a specific aspect ratio, so this resizes (constrains max width)
     * rather than the fit()-and-crop used for product thumbnails.
     */
    private function saveBannerImage($file): string
    {
        $path = 'banners/' . uniqid() . '_' . time() . '.webp';

        try {
            $img = \Intervention\Image\Facades\Image::make($file->getRealPath())
                ->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('webp', 85);

            Storage::disk('public')->put($path, $img);
        } catch (\Throwable $e) {
            Log::error('Banner image save failed: ' . $e->getMessage());
            $ext  = $file->getClientOriginalExtension() ?: 'jpg';
            $path = 'banners/' . uniqid() . '_' . time() . '.' . $ext;
            Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));
        }

        return $path;
    }
}
