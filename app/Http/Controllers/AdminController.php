<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;
use Illuminate\Support\Str; // <-- Added to generate unique Invoice Numbers

class AdminController extends Controller
{
    // ==========================================
    // 1. AGENT (USER) & SUBSCRIPTION MANAGEMENT
    // ==========================================

    public function indexUsers()
    {
        $users = User::with(['package', 'permissions'])->latest()->get();

        foreach ($users as $user) {
            $latestPaidBill = \App\Models\Billing::where('user_id', $user->id)
                ->where('status', 'paid')
                ->latest('paid_at')
                ->first();

            $user->expiry_date = null;
            
            // Note: We should ideally use the actual user_packages expires_at since it accounts for carry-over duration
            $activeWallet = \App\Models\UserPackage::where('user_id', $user->id)
                ->where('is_active_selection', 'true')
                ->first();
                
            if ($activeWallet && $activeWallet->expires_at) {
                $user->expiry_date = Carbon::parse($activeWallet->expires_at);
            } elseif ($latestPaidBill && $user->package && $latestPaidBill->paid_at) {
                // Fallback to old behavior
                $paidAt = Carbon::parse($latestPaidBill->paid_at);
                
                if ($user->package->billing_cycle === 'monthly') {
                    $user->expiry_date = $paidAt->copy()->addMonth();
                } elseif ($user->package->billing_cycle === 'yearly') {
                    $user->expiry_date = $paidAt->copy()->addYear();
                } else {
                    $user->expiry_date = 'Lifetime';
                }
            }
        }

        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $roles = Role::all();
        return view('admin.users.create', compact('roles'));
    }

    public function storeUser(Request $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users',
            'password'  => 'required|string|min:8',
            'role_name' => 'required|exists:roles,name'
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => 'user', 
        ]);

        $user->assignRole($request->role_name);

        return redirect()->route('dashboard')->with('success', "Agent {$user->name} provisioned with '{$request->role_name}' clearance.");
    }

    // ==========================================
    // 2. SYSTEM ROLES MANAGEMENT
    // ==========================================

    public function indexRoles()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $roles = Role::with('permissions')->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function createRole()
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $permissions = Permission::all();
        return view('admin.roles.create', compact('permissions'));
    }

    public function storeRole(Request $request)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $request->validate([
            'name' => 'required|string|unique:roles,name|max:255',
            'permissions' => 'required|array'
        ]);

        $role = Role::create(['name' => $request->name]);
        $role->givePermissionTo($request->permissions);

        return redirect()->route('admin.roles.index')->with('success', "New Role '{$role->name}' initialized.");
    }

    // ==========================================
    // 3. EDIT SYSTEM ROLES
    // ==========================================

    public function editRole($id)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        
        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function updateRole(Request $request, $id)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $role = Role::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name,' . $role->id,
            'permissions' => 'required|array'
        ]);

        $role->update(['name' => $request->name]);
        $role->syncPermissions($request->permissions);

        return redirect()->route('admin.roles.index')->with('success', "Role '{$role->name}' clearances successfully updated.");
    }

    public function destroyUser($id)
    {
        if (!auth()->check() || auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }

        $user = User::findOrFail($id);

        if (auth()->id() === $user->id) {
            return back()->with('error', 'Critical: You cannot terminate your own active session.');
        }

        $userName = $user->name;
        $user->delete();

        return back()->with('success', "Agent {$userName} has been permanently removed from the roster.");
    }

    /**
     * Admin manually activates a Tier/Package for a Client
     */
    public function activateTier(Request $request, $userId)
    {
        // 1. Validate the package selection
        $request->validate([
            'package_id' => 'required',
        ]);

        $client = \App\Models\User::findOrFail($userId);
        $package = \App\Models\Package::findOrFail($request->package_id);

        // 2. Handle Old Packages & Calculate new Expiration
        // If the user already has an active package, we should start counting the new duration
        // from the END of their current package's expiration, so they don't lose time.
        $currentActive = \App\Models\UserPackage::where('user_id', $client->id)
            ->where('is_active_selection', 'true')
            ->first();
            
        $baseDate = Carbon::now();
        if ($currentActive && $currentActive->expires_at && Carbon::parse($currentActive->expires_at)->isFuture()) {
            $baseDate = Carbon::parse($currentActive->expires_at);
        }

        // Deactivate old packages for this user
        \App\Models\UserPackage::where('user_id', $client->id)
            ->update(['is_active_selection' => 'false']);

        // 3. AUTO-CALCULATE EXPIRATION based on Package's billing cycle and baseDate
        $cycle = strtolower($package->billing_cycle ?? 'monthly');
        if (str_contains($cycle, 'year')) {
            $expiresAt = $baseDate->copy()->addYear()->format('Y-m-d H:i:s');
        } elseif (str_contains($cycle, 'lifetime')) {
            $expiresAt = null; // Infinite
        } else {
            $expiresAt = $baseDate->copy()->addMonth()->format('Y-m-d H:i:s'); // Default Monthly
        }

        // 4. Create Active Wallet directly using _allowance columns
        \App\Models\UserPackage::create([
            'user_id'             => $client->id,
            'package_id'          => $package->id,
            'is_active_selection' => 'true', 
            
            'directive_credits'   => $package->directive_allowance ?? 0,
            'image_credits'       => $package->image_allowance ?? 0,
            'video_credits'       => $package->video_allowance ?? 0,
            'branding_credits'    => $package->branding_allowance ?? 0,
            'social_post_credits' => $package->social_post_allowance ?? 0,
            
            'expires_at'          => $expiresAt,
        ]);

        $client->update([
            'package_id' => $package->id,
            'expiry_date' => $expiresAt,
            // Automatically grant the credits to the user's balance
            'directive_credits'   => \DB::raw('directive_credits + ' . ($package->directive_allowance ?? 0)),
            'image_credits'       => \DB::raw('image_credits + ' . ($package->image_allowance ?? 0)),
            'video_credits'       => \DB::raw('video_credits + ' . ($package->video_allowance ?? 0)),
            'branding_credits'    => \DB::raw('branding_credits + ' . ($package->branding_allowance ?? 0)),
            'social_post_credits' => \DB::raw('social_post_credits + ' . ($package->social_post_allowance ?? 0)),
        ]);

        // 5. GENERATE THE PAID INVOICE AUTOMATICALLY
        \App\Models\Billing::create([
            'user_id'    => $client->id,
            'package_id' => $package->id,
            'invoice_no' => 'INV-' . strtoupper(Str::random(8)), // e.g., INV-A8F9K2X1
            'amount'     => $package->price ?? 0,
            'status'     => 'paid', // Auto-approved because the Admin assigned it
            'paid_at'    => Carbon::now(),
        ]);

        return back()->with('success', "Success! {$package->name} ({$package->billing_cycle}) is active, and a paid invoice has been generated for {$client->name}.");
    }
}