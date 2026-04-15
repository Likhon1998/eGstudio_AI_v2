<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\Billing;

class PackageController extends Controller
{
    public function index()
    {
        if (auth()->user()->role !== 'admin') abort(403, 'Unauthorized Access.');

        $packages = Package::latest()->get();
        return view('admin.packages.index', compact('packages'));
    }

    public function store(Request $request)
    {
        if (auth()->user()->role !== 'admin') abort(403, 'Unauthorized Access.');

        $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'billing_cycle' => 'required|string',
            'directive_allowance' => 'required|integer|min:0',
            'image_allowance' => 'required|integer|min:0',
            'video_allowance' => 'required|integer|min:0',
            'branding_allowance' => 'required|integer|min:0',
            'social_post_allowance' => 'required|integer|min:0',
        ]);

        Package::create($request->all());

        return back()->with('success', 'Package created successfully.');
    }

    public function destroy(Package $package)
    {
        if (auth()->user()->role !== 'admin') abort(403, 'Unauthorized Access.');

        $package->delete();
        return back()->with('success', 'Package deleted successfully.');
    }

    public function approvePayment($id)
    {
        if (auth()->user()->role !== 'admin') abort(403, 'Unauthorized Access.');

        $billing = \App\Models\Billing::findOrFail($id);

        // 1. Mark Invoice as Paid
        $billing->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $user = $billing->user;
        $package = $billing->package;

        // 2. Calculate the exact expiration date robustly (ignoring case sensitivity)
        // If the user already has an active package, we should start counting the new duration
        // from the END of their current package's expiration, so they don't lose time.
        $currentActive = \App\Models\UserPackage::where('user_id', $user->id)
            ->where('is_active_selection', 'true')
            ->first();
            
        $baseDate = now();
        if ($currentActive && $currentActive->expires_at && \Carbon\Carbon::parse($currentActive->expires_at)->isFuture()) {
            $baseDate = \Carbon\Carbon::parse($currentActive->expires_at);
        }

        $cycle = strtolower($package->billing_cycle ?? '');
        
        if (str_contains($cycle, 'month')) {
            $expiry = $baseDate->copy()->addMonth();
        } elseif (str_contains($cycle, 'year')) {
            $expiry = $baseDate->copy()->addYear();
        } else {
            $expiry = now()->addYears(100); // Lifetime
        }

        // 3. PostgreSQL Fix: Use string 'true' instead of boolean true
        $hasActiveWallets = \App\Models\UserPackage::where('user_id', $user->id)
                                ->where('is_active_selection', 'true')
                                ->exists();

        // 4. Create the new independent Wallet (passing Postgres-safe strings)
        \App\Models\UserPackage::create([
            'user_id'             => $user->id,
            'package_id'          => $package->id,
            'directive_credits'   => $package->directive_allowance,
            'image_credits'       => $package->image_allowance,
            'video_credits'       => $package->video_allowance,
            'branding_credits'    => $package->branding_allowance,
            'social_post_credits' => $package->social_post_allowance,
            'expires_at'          => $expiry,
            'is_active_selection' => !$hasActiveWallets ? 'true' : 'false', 
        ]);

        return back()->with('success', "Payment Approved! A new {$package->name} wallet has been added to {$user->name}'s account.");
    }

    // Shows the Edit Form
    public function edit($id)
    {
        if (auth()->user()->role !== 'admin') abort(403, 'Unauthorized Access.');
        
        $package = \App\Models\Package::findOrFail($id);
        
        return view('admin.packages.edit', compact('package'));
    }

    // Saves the Updated Data
    public function update(Request $request, $id)
    {
        if (auth()->user()->role !== 'admin') abort(403, 'Unauthorized Access.');
        
        $package = \App\Models\Package::findOrFail($id);

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'price'                 => 'required|numeric|min:0',
            'billing_cycle'         => 'required|string|in:monthly,yearly,lifetime',
            'directive_allowance'   => 'required|integer|min:0',
            'image_allowance'       => 'required|integer|min:0',
            'video_allowance'       => 'required|integer|min:0',
            'branding_allowance'    => 'required|integer|min:0',
            'social_post_allowance' => 'required|integer|min:0',
        ]);

        $package->update($validated);

        // Assuming you have an admin.packages.index route. If not, redirect wherever your admin views packages!
        return redirect()->route('admin.packages.index')
            ->with('success', "Package '{$package->name}' has been updated successfully.");
    }
}