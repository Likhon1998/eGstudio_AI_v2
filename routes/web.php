<?php

use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\LogoController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProductAssetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CgiGenerationController;
use App\Http\Controllers\AdminController; 
use App\Models\CgiGeneration; 
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::redirect('/', '/login');

// Updated Dashboard Route (Regular Users)
Route::get('/dashboard', function () {
    $generations = CgiGeneration::where('user_id', Auth::id())->get();
    return view('dashboard', ['generations' => $generations]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/cgi/create', [CgiGenerationController::class, 'create'])->name('cgi.create');
    Route::post('/cgi/store', [CgiGenerationController::class, 'store'])->name('cgi.store');
    Route::get('/cgi', [CgiGenerationController::class, 'index'])->name('cgi.index');
    Route::post('/cgi/{id}/stop', [CgiGenerationController::class, 'stop'])->name('cgi.stop');
    Route::delete('/cgi-directives/{id}', [CgiGenerationController::class, 'destroy'])->name('cgi.destroy');
    Route::put('/cgi/{id}/update-prompts', [CgiGenerationController::class, 'updatePrompts'])->name('cgi.updatePrompts');
    Route::post('/cgi/{id}/make-picture', [CgiGenerationController::class, 'makePicture'])->name('cgi.makePicture');
    Route::post('/cgi/{id}/make-video', [CgiGenerationController::class, 'makeVideo'])->name('cgi.make-video');
    Route::get('/cgi/videos', [CgiGenerationController::class, 'videoGallery'])->name('cgi.videos');
    Route::get('/cgi/images', [CgiGenerationController::class, 'imageGallery'])->name('cgi.images');
    Route::post('/cgi/apply-branding-image', [CgiGenerationController::class, 'applyBrandingImage'])->name('cgi.applyBrandingImage');
    Route::post('/cgi/apply-branding-video', [CgiGenerationController::class, 'applyBrandingVideo'])->name('cgi.applyBrandingVideo');
    Route::post('/cgi/{id}/publish', [CgiGenerationController::class, 'publishToSocial'])->name('cgi.publish');
    Route::get('/cgi/post-history', [CgiGenerationController::class, 'postHistory'])->name('cgi.post_history');
    
    // Brand Logo Library
    Route::get('/logos', [LogoController::class, 'index'])->name('logos.index');
    Route::post('/logos', [LogoController::class, 'store'])->name('logos.store');
    Route::delete('/logos/{logo}', [LogoController::class, 'destroy'])->name('logos.destroy');
    // SaaS User Billing & Pricing
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing/select/{id}', [PricingController::class, 'selectPackage'])->name('pricing.select');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::delete('/billing/{id}', [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::get('/billing/{id}/invoice', [BillingController::class, 'invoice'])->name('billing.invoice');
    Route::post('/billing/{id}/proof', [BillingController::class, 'submitProof'])->name('billing.submitProof');
    Route::post('/billing/wallet/{id}/switch', [BillingController::class, 'switchWallet'])->name('billing.wallet.switch');

    Route::resource('assets', ProductAssetController::class);
    
});

// --- ADMIN ROUTES (Unified Group) ---
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    // Admin Dashboard
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // User Management
    Route::get('/users', [AdminController::class, 'indexUsers'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    
    // Role Management
    Route::get('/roles', [AdminController::class, 'indexRoles'])->name('roles.index');
    Route::get('/roles/create', [AdminController::class, 'createRole'])->name('roles.create');
    Route::post('/roles', [AdminController::class, 'storeRole'])->name('roles.store');
    Route::get('/roles/{id}/edit', [AdminController::class, 'editRole'])->name('roles.edit');
    Route::put('/roles/{id}', [AdminController::class, 'updateRole'])->name('roles.update');
    Route::post('/users/{id}/top-up', [AdminController::class, 'topUpCredits'])->name('users.top_up');
    Route::get('/credit-logs', [AdminController::class, 'creditLogs'])->name('credit_logs');
    // FIXED: Removed the extra '/admin' and 'admin.' since they are automatically applied by the group!
    Route::post('/users/{user}/activate-tier', [AdminController::class, 'activateTier'])
        ->name('users.activate_tier');
    
    // SaaS Packages
    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
    Route::get('packages/{id}/edit', [PackageController::class, 'edit'])->name('packages.edit');
    Route::put('packages/{id}', [PackageController::class, 'update'])->name('packages.update');

    
    
    // Activation Requests (Secured Controller & View)
    Route::post('/billings/{id}/approve', [PackageController::class, 'approvePayment'])->name('billings.approve'); 
    
    // Added backend security layer to prevent users from forcing URL access
    Route::get('/billings/requests', function () {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }
        return view('admin.packages.requests');
    })->name('billings.requests');

});

require __DIR__.'/auth.php';