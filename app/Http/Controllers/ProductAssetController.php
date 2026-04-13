<?php

namespace App\Http\Controllers;

use App\Models\ProductAsset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductAssetController extends Controller
{
    // Shows the gallery and the upload form
    public function index()
    {
        $assets = ProductAsset::where('user_id', auth()->id())->latest()->get();
        return view('assets.index', compact('assets'));
    }

    // Handles new file uploads
    // 2. STORE: Handle multiple new uploads
    public function store(Request $request)
    {
        // Validate that it is an array of files, and each file is a valid image
        $request->validate([
            'file_paths' => 'required|array',
            'file_paths.*' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $uploadedCount = 0;

        // Loop through every file the user selected
        foreach ($request->file('file_paths') as $file) {
            
            // Store the file
            $path = $file->store('product_assets', 'public');
            
            // Auto-extract the name from the file (removes the .jpg/.png extension)
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            // Create the record
            ProductAsset::create([
                'user_id' => auth()->id(),
                'name' => $originalName, 
                'file_path' => $path,
            ]);

            $uploadedCount++;
        }

        return back()->with('success', $uploadedCount . ' asset(s) successfully added to your library!');
    }

    // Shows the edit form for a specific asset
    public function edit($id)
    {
        $asset = ProductAsset::where('user_id', auth()->id())->findOrFail($id);
        return view('assets.edit', compact('asset'));
    }

    // Saves changes (rename or replace the physical image)
    public function update(Request $request, $id)
    {
        $asset = ProductAsset::where('user_id', auth()->id())->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'file_path' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $asset->name = $request->name;

        // If they uploaded a new replacement image...
        if ($request->hasFile('file_path')) {
            // Delete old file from server storage
            Storage::disk('public')->delete($asset->file_path);
            // Save new file
            $asset->file_path = $request->file('file_path')->store('product_assets', 'public');
        }

        $asset->save();

        return redirect()->route('assets.index')->with('success', 'Asset updated successfully!');
    }

    // Deletes the asset and the physical file
    public function destroy($id)
    {
        $asset = ProductAsset::where('user_id', auth()->id())->findOrFail($id);
        
        Storage::disk('public')->delete($asset->file_path);
        $asset->delete();

        return back()->with('success', 'Asset deleted permanently.');
    }
}