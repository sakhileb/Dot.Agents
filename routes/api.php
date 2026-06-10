<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\DeploymentController;
use App\Http\Controllers\Api\V1\SkillApprovalController;
use App\Http\Controllers\Api\V1\SkillController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public health probe — no authentication, used by load balancers and CI/CD pipelines
Route::get('/health', HealthController::class)->name('api.health');

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

        // Enterprise skill assignments per deployment
        Route::post('/{deployment}/skills', [SkillController::class, 'assign'])->name('skills.assign');
        Route::patch('/{deployment}/skills/{skill}', [SkillController::class, 'toggleSkill'])->name('skills.toggle');
        Route::post('/{deployment}/skills/{skill}/execute', [SkillController::class, 'execute'])->name('skills.execute');
        Route::get('/{deployment}/skills', [SkillController::class, 'deploymentSkills'])->name('skills.index');
        Route::get('/{deployment}/skill-scores', [SkillController::class, 'scores'])->name('skills.scores');
    });

    // Enterprise skill catalog (read-only, filter by department)
    Route::prefix('skills')->name('skills.')->middleware('throttle:120,1')->group(function () {
        Route::get('/', [SkillController::class, 'index'])->name('index');
        Route::get('/{skill}', [SkillController::class, 'show'])->name('show');
    });

    // Skill approval workflow (org-scoped, rate limited)
    Route::prefix('skill-approvals')->name('skill-approvals.')->middleware('throttle:60,1')->group(function () {
        Route::get('/', [SkillApprovalController::class, 'index'])->name('index');
        Route::get('/{approval}', [SkillApprovalController::class, 'show'])->name('show');
        Route::post('/{approval}/approve', [SkillApprovalController::class, 'approve'])->name('approve');
        Route::post('/{approval}/reject', [SkillApprovalController::class, 'reject'])->name('reject');
    });
});
