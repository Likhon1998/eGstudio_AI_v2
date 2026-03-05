<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\CgiGenerationController;
use App\Models\CgiGeneration; // Import the Model
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::redirect('/', '/login');

// Updated Dashboard Route
Route::get('/dashboard', function () {
    // Fetch only the generations belonging to the logged-in user
    $generations = CgiGeneration::where('user_id', Auth::id())->get();

    return view('dashboard', [
        'generations' => $generations
    ]);
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
    Route::post('/cgi/apply-branding', [CgiGenerationController::class, 'applyBranding'])->name('cgi.applyBranding');
});

// --- NEW CALLBACK ROUTE ---        
// We keep this outside the 'auth' group so n8n can reach it
// Route::post('/cgi/callback', [CgiGenerationController::class, 'callback'])->name('cgi.callback');

require __DIR__.'/auth.php';