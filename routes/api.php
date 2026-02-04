<?php

use App\Http\Controllers\Api\GameController;
use App\Http\Controllers\Api\StatisticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Game routes (session-based, works for guests)
// Using web middleware for session support in API
Route::middleware([\Illuminate\Session\Middleware\StartSession::class, \Illuminate\View\Middleware\ShareErrorsFromSession::class])->group(function () {
    Route::get('/game/today', [GameController::class, 'today'])->name('api.game.today');
    Route::get('/game/{id}', [GameController::class, 'show'])->name('api.game.show');
    Route::post('/game/guess', [GameController::class, 'guess'])->name('api.game.guess');
    Route::get('/game/share', [GameController::class, 'share'])->name('api.game.share');
});

// Statistics routes (requires authentication)
Route::middleware(['auth'])->group(function () {
    Route::get('/statistics', [StatisticsController::class, 'index'])->name('api.statistics');
});
