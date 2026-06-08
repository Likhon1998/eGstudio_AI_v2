<?php

use App\Http\Controllers\Admin\AdminCgiAuditController;
use App\Http\Controllers\Admin\PackageController;
use App\Http\Controllers\Auth\OtpResetController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\LogoController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\ProductAssetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CgiGenerationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GalleryDownloadController;
use App\Http\Controllers\AdminController; 
use App\Http\Controllers\ApprovalController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// === OCCASION CONTROLLERS ===
use App\Http\Controllers\OccasionController;
use App\Http\Controllers\PublicStorageController;

// Serve uploaded media from storage/app/public (no auth; avoids Laravel /storage 403 on missing files)
Route::get('/media/{path}', [PublicStorageController::class, 'show'])
    ->where('path', '.*')
    ->name('media.public');

Route::redirect('/', '/login');

// Dashboard Route (Regular Users)
Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


Route::middleware('auth')->group(function () {
    
    // User Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ==========================================
    // 1. CORE CGI STUDIO PIPELINE
    // ==========================================
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
    Route::post('/gallery/download-image', [GalleryDownloadController::class, 'download'])->name('gallery.download-image');
    Route::post('/cgi/apply-branding-image', [CgiGenerationController::class, 'applyBrandingImage'])->name('cgi.applyBrandingImage');
    Route::post('/cgi/apply-branding-video', [CgiGenerationController::class, 'applyBrandingVideo'])->name('cgi.applyBrandingVideo');
    Route::post('/cgi/apply-footer', [CgiGenerationController::class, 'applyFooter'])->name('cgi.addFooter');
    Route::post('/cgi/merge-template', [CgiGenerationController::class, 'mergeTemplate'])->name('cgi.mergeTemplate');
    Route::post('/cgi/merge-video-template', [CgiGenerationController::class, 'mergeVideoTemplate'])->name('cgi.mergeVideoTemplate');
    Route::post('/cgi/{id}/generate-caption', [CgiGenerationController::class, 'generateCaption'])->name('cgi.generateCaption');
    Route::post('/cgi/{id}/publish', [CgiGenerationController::class, 'publishToSocial'])->name('cgi.publish');
    Route::get('/cgi/post-history', [CgiGenerationController::class, 'postHistory'])->name('cgi.post_history');
    Route::post('/cgi-studio/autofill', [CgiGenerationController::class, 'autoFill'])->name('cgi.autofill');
    
    // ==========================================
    // 2. NEW: OCCASION STUDIO PIPELINE
    // ==========================================
    Route::prefix('occasions')->name('occasions.')->group(function () {
        Route::get('/', [OccasionController::class, 'index'])->name('index');
        Route::get('/create', [OccasionController::class, 'create'])->name('create');
        Route::post('/store', [OccasionController::class, 'store'])->name('store');
        
        // AJAX Action Routes
        Route::get('/{occasion}/prompt-status', [OccasionController::class, 'promptStatus'])->name('promptStatus');
        Route::get('/{occasion}/image-status', [OccasionController::class, 'imageStatus'])->name('imageStatus');
        Route::post('/{occasion}/retry-prompt', [OccasionController::class, 'retryPrompt'])->name('retryPrompt');
        Route::put('/{id}/update-prompts', [OccasionController::class, 'updatePrompts'])->name('updatePrompts');
        Route::post('/{occasion}/make-picture', [OccasionController::class, 'makePicture'])->name('makePicture');
        Route::post('/{occasion}/make-video', [OccasionController::class, 'makeVideo'])->name('makeVideo');
        Route::post('/{occasion}/add-logo', [OccasionController::class, 'addBrandingLogo'])->name('addLogo');
        Route::post('/merge-template', [OccasionController::class, 'mergeTemplate'])->name('mergeTemplate');
        
        Route::delete('/{occasion}', [OccasionController::class, 'destroy'])->name('destroy');
        Route::get('/gallery', [OccasionController::class, 'gallery'])->name('gallery');
        Route::post('/{id}/generate-caption', [OccasionController::class, 'generateCaption'])->name('generateCaption');
        Route::post('/{id}/publish', [OccasionController::class, 'publishToSocial'])->name('publishToSocial');
        Route::delete('/post-history/{id}', [OccasionController::class, 'destroyPostHistory'])->name('postHistory.destroy');
        Route::post('/auto-fill', [OccasionController::class, 'autoFill'])->name('autoFill');
    });

    // Brand Logo Library
    Route::get('/logos', [LogoController::class, 'index'])->name('logos.index');
    Route::post('/logos', [LogoController::class, 'store'])->name('logos.store');
    Route::get('/logos/{logo}/edit', [LogoController::class, 'edit'])->name('logos.edit');
    Route::put('/logos/{logo}', [LogoController::class, 'update'])->name('logos.update');
    Route::delete('/logos/{logo}', [LogoController::class, 'destroy'])->name('logos.destroy');
    
    // SaaS User Billing & Pricing
    Route::get('/pricing', [PricingController::class, 'index'])->name('pricing.index');
    Route::post('/pricing/select/{id}', [PricingController::class, 'selectPackage'])->name('pricing.select');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::delete('/billing/{id}', [BillingController::class, 'destroy'])->name('billing.destroy');
    Route::get('/billing/{id}/invoice', [BillingController::class, 'invoice'])->name('billing.invoice');
    Route::post('/billing/{id}/proof', [BillingController::class, 'submitProof'])->name('billing.submitProof');
    Route::post('/billing/wallet/{id}/switch', [BillingController::class, 'switchWallet'])->name('billing.wallet.switch');

    // Assets
    Route::resource('assets', ProductAssetController::class);

    // ==========================================
    // CLIENT APPROVAL WORKFLOW
    // ==========================================
    // User optionally re-flags a finished pic/video for sign-off.
    Route::post('/approvals/submit', [ApprovalController::class, 'submit'])->name('approvals.submit');
    // Approver review dashboard + decision action.
    Route::get('/approvals', [ApprovalController::class, 'index'])->name('approvals.index');
    Route::post('/approvals/review', [ApprovalController::class, 'review'])->name('approvals.review');
});


// ==========================================
// 3. ADMIN SECURE ROUTES
// ==========================================
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');
    
    // User & Agent Management
    Route::get('/users', [AdminController::class, 'indexUsers'])->name('users.index');
    Route::get('/users/create', [AdminController::class, 'createUser'])->name('users.create');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::delete('/users/{id}', [AdminController::class, 'destroyUser'])->name('users.destroy');
    Route::post('/users/{id}/top-up', [AdminController::class, 'topUpCredits'])->name('users.top_up');
    Route::post('/users/{user}/activate-tier', [AdminController::class, 'activateTier'])->name('users.activate_tier');
    // Attach an approval credential (approver login) to an existing user
    Route::post('/users/{id}/approver', [AdminController::class, 'storeApprover'])->name('users.approver.store');

    // Role Management
    Route::get('/roles', [AdminController::class, 'indexRoles'])->name('roles.index');
    Route::get('/roles/create', [AdminController::class, 'createRole'])->name('roles.create');
    Route::post('/roles', [AdminController::class, 'storeRole'])->name('roles.store');
    Route::get('/roles/{id}/edit', [AdminController::class, 'editRole'])->name('roles.edit');
    Route::put('/roles/{id}', [AdminController::class, 'updateRole'])->name('roles.update');
    
    // Credit Logs
    Route::get('/credit-logs', [AdminController::class, 'creditLogs'])->name('credit_logs');
    
    // SaaS Master Templates (CGI Studio)
    Route::get('/packages', [PackageController::class, 'index'])->name('packages.index');
    Route::post('/packages', [PackageController::class, 'store'])->name('packages.store');
    Route::delete('/packages/{package}', [PackageController::class, 'destroy'])->name('packages.destroy');
    Route::get('/packages/{id}/edit', [PackageController::class, 'edit'])->name('packages.edit');
    Route::put('/packages/{id}', [PackageController::class, 'update'])->name('packages.update');

    // Activation Requests (Secured View)
    Route::post('/billings/{id}/approve', [PackageController::class, 'approvePayment'])->name('billings.approve'); 
    Route::get('/billings/requests', function () {
        if (auth()->user()->role !== 'admin') {
            abort(403, 'Unauthorized Access.');
        }
        return view('admin.packages.requests');
    })->name('billings.requests');

    Route::get('/cgi-audit', [AdminCgiAuditController::class, 'index'])->name('cgi_audit.index');

});


// ==========================================
// 4. LOAD DEFAULT AUTH THEN OVERRIDE WITH CUSTOM OTP
// ==========================================

// 1. Load Breeze defaults first
require __DIR__.'/auth.php';

// 2. Load our custom OTP routes
Route::middleware('guest')->group(function () {
    // 1. Enter Email
    Route::get('/forgot-password', [OtpResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password/send', [OtpResetController::class, 'sendOtp'])->name('password.otp.send');
    
    // 2. BLADE 1: Enter & Check OTP
    Route::get('/otp-system/verify', [OtpResetController::class, 'showVerifyForm'])->name('password.otp.form');
    Route::post('/otp-system/check', [OtpResetController::class, 'checkOtp'])->name('password.otp.check');
    
    // 3. BLADE 2: Enter New Password
    Route::get('/otp-system/new-password', [OtpResetController::class, 'showNewPasswordForm'])->name('password.new.form');
    Route::post('/otp-system/update', [OtpResetController::class, 'resetPassword'])->name('password.otp.update');
});