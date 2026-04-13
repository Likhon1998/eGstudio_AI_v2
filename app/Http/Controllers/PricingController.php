<?php

namespace App\Http\Controllers;

use App\Models\Package;
use App\Models\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PricingController extends Controller
{
    public function index()
    {
        $packages = Package::orderBy('price', 'asc')->get();
        return view('pricing.index', compact('packages'));
    }

    public function selectPackage(Request $request, $id)
    {
        $package = Package::findOrFail($id);
        $user = Auth::user();

        // 1. SAFETY CHECK: Does the user already have an invoice waiting for admin approval?
        $pendingApproval = Billing::where('user_id', $user->id)
            ->where('status', 'due')
            ->whereNotNull('payment_proof')
            ->first();

        if ($pendingApproval) {
            return redirect()->route('billing.index')
                ->withErrors(['error' => 'You have a payment currently under review. Please wait for the admin to approve it before requesting a new package.']);
        }

        // 2. Clean up any stale, abandoned invoices
        Billing::where('user_id', $user->id)
            ->where('status', 'due')
            ->whereNull('payment_proof')
            ->delete();

        // 3. Generate a secure, unique Invoice Number
        $invoiceNo = 'INV-' . strtoupper(Str::random(8));

        // 4. Create the new pending billing record
        Billing::create([
            'user_id'    => $user->id,
            'package_id' => $package->id,
            'invoice_no' => $invoiceNo,
            'amount'     => $package->price,
            'status'     => 'due',
        ]);

        return redirect()->route('billing.index')
            ->with('success', "Package '{$package->name}' selected! Please upload your payment proof to activate your credits.");
    }
}