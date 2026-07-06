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
    Route::post('/users/{user}/suspend', [\App\Http\Controllers\Hq\UserController::class, 'suspend'])
        ->name('users.suspend');
    Route::post('/users/{user}/reactivate', [\App\Http\Controllers\Hq\UserController::class, 'reactivate'])
        ->name('users.reactivate');

    Route::get('/personas', [\App\Http\Controllers\Hq\PersonaController::class, 'index'])
        ->name('personas.index');
    Route::get('/personas/create', [\App\Http\Controllers\Hq\PersonaController::class, 'create'])
        ->name('personas.create');
    Route::post('/personas', [\App\Http\Controllers\Hq\PersonaController::class, 'store'])
        ->name('personas.store');
    Route::get('/personas/{persona}/edit', [\App\Http\Controllers\Hq\PersonaController::class, 'edit'])
        ->name('personas.edit');
    Route::put('/personas/{persona}', [\App\Http\Controllers\Hq\PersonaController::class, 'update'])
        ->name('personas.update');
    Route::post('/personas/{persona}/archive', [\App\Http\Controllers\Hq\PersonaController::class, 'archive'])
        ->name('personas.archive');
    Route::post('/personas/{persona}/duplicate', [\App\Http\Controllers\Hq\PersonaController::class, 'duplicate'])
        ->name('personas.duplicate');

    Route::get('/scenarios', [\App\Http\Controllers\Hq\ScenarioController::class, 'index'])
        ->name('scenarios.index');
    Route::get('/scenarios/create', [\App\Http\Controllers\Hq\ScenarioController::class, 'create'])
        ->name('scenarios.create');
    Route::post('/scenarios', [\App\Http\Controllers\Hq\ScenarioController::class, 'store'])
        ->name('scenarios.store');
    Route::get('/scenarios/{scenario}/edit', [\App\Http\Controllers\Hq\ScenarioController::class, 'edit'])
        ->name('scenarios.edit');
    Route::put('/scenarios/{scenario}', [\App\Http\Controllers\Hq\ScenarioController::class, 'update'])
        ->name('scenarios.update');
    Route::post('/scenarios/{scenario}/archive', [\App\Http\Controllers\Hq\ScenarioController::class, 'archive'])
        ->name('scenarios.archive');
    Route::post('/scenarios/{scenario}/duplicate', [\App\Http\Controllers\Hq\ScenarioController::class, 'duplicate'])
        ->name('scenarios.duplicate');
});

Route::get('/_test/hq-ping', function () {
    return response('ok');
})->middleware(['auth', 'hq']);

require __DIR__.'/auth.php';
