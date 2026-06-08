<?php

namespace App\Http\Controllers;

use App\Models\Logo;
use App\Models\ProductAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductAssetController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->query('tab', ProductAsset::TYPE_PRODUCT);
        $allowedTabs = [ProductAsset::TYPE_PRODUCT, ProductAsset::TYPE_TEMPLATE, 'logo'];
        if (! in_array($tab, $allowedTabs, true)) {
            $tab = ProductAsset::TYPE_PRODUCT;
        }

        $assets = collect();
        $logos = collect();

        if ($tab === 'logo') {
            $logos = Logo::where('user_id', auth()->id())->latest()->get();
        } else {
            $assets = ProductAsset::where('user_id', auth()->id())
                ->where('asset_type', $tab)
                ->latest()
                ->get();
        }

        $missingFileCount = 0;
        if ($tab === 'logo') {
            $missingFileCount = $logos->filter(fn ($logo) => ! $logo->fileExistsOnDisk())->count();
        } else {
            $missingFileCount = $assets->filter(fn ($asset) => ! $asset->fileExistsOnDisk())->count();
        }

        return view('assets.index', compact('assets', 'logos', 'tab', 'missingFileCount'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'asset_type' => 'required|in:product,template',
            'file_paths' => 'required|array',
            'file_paths.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $assetType = $request->input('asset_type');
        $storageFolder = $assetType === ProductAsset::TYPE_TEMPLATE ? 'template_assets' : 'product_assets';
        $created = [];

        foreach ($request->file('file_paths') as $file) {
            $path = $file->store($storageFolder, 'public');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $created[] = ProductAsset::create([
                'user_id' => auth()->id(),
                'asset_type' => $assetType,
                'name' => $originalName,
                'file_path' => $path,
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'assets' => collect($created)->map(fn (ProductAsset $asset) => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'path' => $asset->file_path,
                    'url' => $asset->public_url,
                ])->values(),
            ]);
        }

        $label = $assetType === ProductAsset::TYPE_TEMPLATE ? 'template(s)' : 'product asset(s)';

        return redirect()
            ->route('assets.index', ['tab' => $assetType])
            ->with('success', count($created) . ' ' . $label . ' successfully added to your library!');
    }

    public function edit($id)
    {
        $asset = ProductAsset::where('user_id', auth()->id())->findOrFail($id);

        return view('assets.edit', compact('asset'));
    }

    public function update(Request $request, $id)
    {
        $asset = ProductAsset::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'file_path' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $asset->name = $request->name;

        if ($request->hasFile('file_path')) {
            Storage::disk('public')->delete($asset->file_path);
            $folder = $asset->isTemplate() ? 'template_assets' : 'product_assets';
            $asset->file_path = $request->file('file_path')->store($folder, 'public');
        }

        $asset->save();

        return redirect()
            ->route('assets.index', ['tab' => $asset->asset_type])
            ->with('success', 'Asset updated successfully!');
    }

    public function destroy($id)
    {
        $asset = ProductAsset::where('user_id', auth()->id())->findOrFail($id);
        $tab = $asset->asset_type;

        Storage::disk('public')->delete($asset->file_path);
        $asset->delete();

        return redirect()
            ->route('assets.index', ['tab' => $tab])
            ->with('success', 'Asset deleted permanently.');
    }
}
