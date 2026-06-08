<?php

namespace App\Http\Controllers;

use App\Models\Logo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LogoController extends Controller
{
    public function index()
    {
        return redirect()->route('assets.index', ['tab' => 'logo']);
    }

    public function store(Request $request)
    {
        if ($request->hasFile('logos')) {
            $request->validate([
                'logos' => 'required|array',
                'logos.*' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
            ]);

            $uploadedCount = 0;
            foreach ($request->file('logos') as $file) {
                $path = $file->store('logos', 'public');
                auth()->user()->logos()->create([
                    'name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                    'file_path' => $path,
                ]);
                $uploadedCount++;
            }

            return redirect()
                ->route('assets.index', ['tab' => 'logo'])
                ->with('success', $uploadedCount . ' logo(s) successfully added to your library!');
        }

        $request->validate([
            'logo' => 'required|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
        ]);

        $path = $request->file('logo')->store('logos', 'public');

        auth()->user()->logos()->create([
            'name' => pathinfo($request->file('logo')->getClientOriginalName(), PATHINFO_FILENAME),
            'file_path' => $path,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()
            ->route('assets.index', ['tab' => 'logo'])
            ->with('success', 'Brand logo successfully added to your library.');
    }

    public function edit(Logo $logo)
    {
        if ($logo->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        return view('logos.edit', compact('logo'));
    }

    public function update(Request $request, Logo $logo)
    {
        if ($logo->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:5120',
        ]);

        $logo->name = $request->name;

        if ($request->hasFile('logo')) {
            Storage::disk('public')->delete($logo->file_path);
            $logo->file_path = $request->file('logo')->store('logos', 'public');
        }

        $logo->save();

        return redirect()
            ->route('assets.index', ['tab' => 'logo'])
            ->with('success', 'Logo updated successfully!');
    }

    public function destroy(Logo $logo)
    {
        if ($logo->user_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }

        Storage::disk('public')->delete($logo->file_path);
        $logo->delete();

        return redirect()
            ->route('assets.index', ['tab' => 'logo'])
            ->with('success', 'Logo deleted permanently.');
    }
}
