<?php

namespace App\Http\Controllers;

use App\Models\Billing;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    // Shows the User's Subscription Dashboard & Wallets
    public function index()
    {
        $user = Auth::user();
        $billings = Billing::where('user_id', $user->id)->latest()->get();
        
        // Fetch all the user's purchased wallets (packages)
        $wallets = UserPackage::with('package')
            ->where('user_id', $user->id)
            ->orderBy('is_active_selection', 'desc') // Puts the active one at the top
            ->latest()
            ->get();
        
        return view('billing.index', compact('user', 'billings', 'wallets'));
    }

    // Handles the user clicking "Set as Active" on a wallet
    public function switchWallet(Request $request, $id)
    {
        $user = Auth::user();

        // Find the wallet they want to activate
        $walletToActivate = UserPackage::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        // Safety check: Prevent them from activating an expired wallet
        if ($walletToActivate->expires_at && now()->greaterThan($walletToActivate->expires_at)) {
            return back()->withErrors(['error' => 'You cannot activate an expired package.']);
        }

        // Deactivate all other wallets (PostgreSQL Fix: use string 'false')
        UserPackage::where('user_id', $user->id)->update(['is_active_selection' => 'false']);

        // Activate the selected wallet (PostgreSQL Fix: use string 'true')
        $walletToActivate->update(['is_active_selection' => 'true']);

        return back()->with('success', "Active wallet switched! You are now using credits from your {$walletToActivate->package->name} plan.");
    }

    // Shows the specific Invoice
    public function invoice($id)
    {
        $billing = Billing::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('billing.invoice', compact('billing'));
    }

    // Handles the Payment Proof Upload
    public function submitProof(Request $request, $id)
    {
        $request->validate([
            'transaction_id' => 'required|string|max:255',
            'payment_proof' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $billing = Billing::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $path = $request->file('payment_proof')->store('payment_proofs', 'public');

        $billing->update([
            'transaction_id' => $request->transaction_id,
            'payment_proof' => $path,
        ]);

        return back()->with('success', 'Payment proof submitted! The Admin is reviewing it now.');
    }

    // Handles deleting a billing history record
    public function destroy($id)
    {
        $billing = Billing::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $billing->delete();

        return back()->with('success', 'Billing history record deleted successfully.');
    }
}