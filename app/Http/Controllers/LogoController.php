<?php

namespace App\Http\Controllers;

use App\Models\Logo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogoController extends Controller
{
    public function index()
    {
        $logos = auth()->user()->logos()->latest()->get();
        return view('logos.index', compact('logos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg|max:5120', // Max 5MB
        ]);

        // Upload the file to the 'public/logos' directory
        $path = $request->file('logo')->store('logos', 'public');

        auth()->user()->logos()->create([
            'name' => $request->file('logo')->getClientOriginalName(),
            'file_path' => $path,
        ]);

        return back()->with('success', 'Brand logo successfully secured in the vault.');
    }

    public function destroy(Logo $logo)
    {
        // Security check: ensure the user owns this logo
        if ($logo->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        // Delete from storage
        Storage::disk('public')->delete($logo->file_path);
        
        // Delete from database
        $logo->delete();

        return back()->with('success', 'Logo permanently purged from vault.');
    }
}
