<?php


use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecordingController;
use App\Http\Controllers\OperatorController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\VideoUploadController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Landing page ─────────────────────────────────────────────────────────
Route::get('/', function () {
    return redirect()->route('login');
});

// Temporary route to clear cache on server
Route::get('/clear-cache', function () {
    \Illuminate\Support\Facades\Artisan::call('config:clear');
    \Illuminate\Support\Facades\Artisan::call('cache:clear');
    \Illuminate\Support\Facades\Artisan::call('view:clear');
    \Illuminate\Support\Facades\Artisan::call('route:clear');
    return "All cache cleared successfully! You can now access your website.";
});

// ─── Dashboard (Handled by RecordingController) ───────────────────────────
Route::get('/dashboard', [RecordingController::class, 'dashboard'])
    ->middleware('auth')
    ->name('dashboard');

// ─── Auth routes ──────────────────────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ─── Operator ─────────────────────────────────────────────────────

    Route::post('/upload-video-chunk', [VideoUploadController::class, 'uploadChunk'])->name('video.upload.chunk');
    Route::post('/recordings', [RecordingController::class, 'store'])->name('recordings.store');
    Route::get('/recordings/check/{order_number}', [RecordingController::class, 'check'])->name('recordings.check');

    // ─── Archive & Records (Accessible by all Auth users) ──────────────
    Route::get('/archive',    [RecordingController::class, 'search'])->name('admin.archive');
    Route::get('/records/{id}',          [RecordingController::class, 'show'])->name('admin.recordings.show');
    Route::get('/records/{id}/stream',   [RecordingController::class, 'stream'])->name('admin.recordings.stream');
    Route::get('/records/{id}/download', [RecordingController::class, 'download'])->name('admin.recordings.download');

    // ─── Admin Only (URL pendek) ─────────────────────────────────────────
    Route::middleware('can:admin')->name('admin.')->group(function () {
        Route::delete('/records/{id}',       [RecordingController::class, 'destroy'])->name('recordings.destroy');

        // /operators
        Route::get('/operators',              [OperatorController::class, 'index'])->name('operators.index');
        Route::post('/operators',             [OperatorController::class, 'store'])->name('operators.store');
        Route::patch('/operators/{operator}', [OperatorController::class, 'update'])->name('operators.update');
        Route::delete('/operators/{operator}',[OperatorController::class, 'destroy'])->name('operators.destroy');

        // /settings
        Route::get('/settings',  [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });
});

require __DIR__ . '/auth.php';
