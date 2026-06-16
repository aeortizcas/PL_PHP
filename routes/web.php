<?php

use App\Http\Controllers\CallLogController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::resource('contacts', ContactController::class);

    Route::resource('call-logs', CallLogController::class)->parameters([
        'call-logs' => 'callLog',
    ]);

    Route::prefix('emails')->name('emails.')->group(function () {
        Route::get('/', [EmailController::class, 'index'])->name('index');
        Route::get('/sent', [EmailController::class, 'sent'])->name('sent');
        Route::get('/create', [EmailController::class, 'create'])->name('create');
        Route::post('/', [EmailController::class, 'store'])->name('store');
        Route::post('/sync', [EmailController::class, 'sync'])->name('sync');
        Route::get('/preferences', [EmailController::class, 'preferences'])->name('preferences');
        Route::post('/preferences', [EmailController::class, 'updatePreferences'])->name('preferences.update');
        Route::get('/{email}', [EmailController::class, 'show'])->name('show');
        Route::post('/{email}/suggest-reply', [EmailController::class, 'suggestReply'])->name('suggest-reply');
        Route::delete('/{email}', [EmailController::class, 'destroy'])->name('destroy');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

require __DIR__.'/auth.php';
