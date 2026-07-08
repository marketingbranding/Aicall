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

Route::middleware(['auth', 'verified', 'account.active'])->prefix('training')->name('training.')->group(function () {
    Route::get('/', [\App\Http\Controllers\DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/scenarios/{scenario}', [\App\Http\Controllers\TrainingScenarioController::class, 'briefing'])
        ->name('scenarios.briefing');

    Route::post('/scenarios/{scenario}/sessions', [\App\Http\Controllers\TrainingScenarioController::class, 'createSession'])
        ->name('scenarios.sessions.store');

    Route::get('/sessions/{publicId}/prepare', [\App\Http\Controllers\TrainingScenarioController::class, 'prepare'])
        ->name('sessions.prepare');

    Route::post('/sessions/{publicId}/live-credentials', [\App\Http\Controllers\RoleplayLiveCredentialsController::class, 'store'])
        ->name('sessions.live-credentials.store');

    Route::patch('/sessions/{publicId}/status', [\App\Http\Controllers\RoleplaySessionStatusController::class, 'update'])
        ->name('sessions.status.update');
});

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

    Route::get('/global-rubrics', [\App\Http\Controllers\Hq\GlobalRubricController::class, 'index'])
        ->name('global-rubrics.index');
    Route::get('/global-rubrics/create', [\App\Http\Controllers\Hq\GlobalRubricController::class, 'create'])
        ->name('global-rubrics.create');
    Route::post('/global-rubrics', [\App\Http\Controllers\Hq\GlobalRubricController::class, 'store'])
        ->name('global-rubrics.store');
    Route::get('/global-rubrics/{rubric}/edit', [\App\Http\Controllers\Hq\GlobalRubricController::class, 'edit'])
        ->name('global-rubrics.edit');
    Route::put('/global-rubrics/{rubric}', [\App\Http\Controllers\Hq\GlobalRubricController::class, 'update'])
        ->name('global-rubrics.update');
    Route::post('/global-rubrics/{rubric}/archive', [\App\Http\Controllers\Hq\GlobalRubricController::class, 'archive'])
        ->name('global-rubrics.archive');

    Route::get('/scenarios/{scenario}/rubrics', [\App\Http\Controllers\Hq\ScenarioRubricController::class, 'edit'])
        ->name('scenario-rubrics.edit');
    Route::post('/scenarios/{scenario}/rubrics', [\App\Http\Controllers\Hq\ScenarioRubricController::class, 'update'])
        ->name('scenario-rubrics.update');
});

Route::get('/_test/hq-ping', function () {
    return response('ok');
})->middleware(['auth', 'hq']);

require __DIR__.'/auth.php';
