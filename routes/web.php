<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function () {
    Route::get('/account/pending-approval', function () {
        return view('auth.waiting-approval');
    })->name('account.pending');

    Route::get('/account/suspended', function () {
        return view('auth.suspended');
    })->name('account.suspended');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified', 'account.active'])->name('dashboard');

Route::middleware(['auth', 'hq'])->prefix('hq')->name('hq.')->group(function () {
    Route::get('/users/pending', [\App\Http\Controllers\Hq\UserController::class, 'index'])
        ->name('users.pending');
    Route::post('/users/{user}/approve', [\App\Http\Controllers\Hq\UserController::class, 'approve'])
        ->name('users.approve');
});

Route::get('/_test/hq-ping', function () {
    return response('ok');
})->middleware(['auth', 'hq']);

require __DIR__.'/auth.php';
