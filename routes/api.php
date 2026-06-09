<?php

use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\DeploymentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// All API routes are versioned under /v1 with auth:sanctum + org.context
Route::prefix('v1')->name('api.v1.')->middleware(['auth:sanctum', 'org.context'])->group(function () {

    // Authenticated user
    Route::get('/me', fn (Request $request) => response()->json($request->user()))->name('me');

    // Agent marketplace catalog (read-only) — rate limited to prevent bulk enumeration
    Route::prefix('agents')->name('agents.')->middleware('throttle:120,1')->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index');
        Route::get('/{agent}', [AgentController::class, 'show'])->name('show');
    });

    // Agent deployments (org-scoped, rate limited)
    Route::prefix('deployments')->name('deployments.')->middleware('throttle:60,1')->group(function () {
        Route::get('/', [DeploymentController::class, 'index'])->name('index');
        Route::post('/', [DeploymentController::class, 'store'])->name('store');
        Route::get('/{deployment}', [DeploymentController::class, 'show'])->name('show');
        Route::patch('/{deployment}', [DeploymentController::class, 'update'])->name('update');
        Route::post('/{deployment}/pause', [DeploymentController::class, 'pause'])->name('pause');
        Route::delete('/{deployment}', [DeploymentController::class, 'decommission'])->name('decommission');
    });
});
