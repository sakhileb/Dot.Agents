<?php

use App\Http\Controllers\BillingController;
use App\Models\AgentDeployment;
use App\Models\AgentWorkflow;
use Illuminate\Support\Facades\Route;

// Landing page
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'org.context',
])->group(function () {

    // Dashboard
    Route::get('/dashboard', fn () => view('dashboard'))->name('dashboard');

    // Marketplace
    Route::get('/marketplace', fn () => view('marketplace.index'))->name('marketplace');

    // Agents
    Route::prefix('agents')->name('agents.')->group(function () {
        Route::get('/', fn () => view('agents.index'))->name('deployments');
        Route::get('/{deployment}', fn (AgentDeployment $deployment) => view('agents.show', compact('deployment')))->name('show');
        Route::get('/{deployment}/chat', fn (AgentDeployment $deployment) => view('agents.chat', compact('deployment')))->name('chat');
        Route::get('/{deployment}/scorecard', fn (AgentDeployment $deployment) => view('agents.scorecard', compact('deployment')))->name('scorecard');
    });

    // Workflows
    Route::prefix('workflows')->name('workflows.')->group(function () {
        Route::get('/', fn () => view('workflows.index'))->name('index');
        Route::get('/{workflow}/builder', function (AgentWorkflow $workflow) {
            abort_unless(
                $workflow->organization_id === session('current_organization_id'),
                403
            );

            return view('workflows.builder', compact('workflow'));
        })->name('builder');
    });

    // Legacy workflows route kept for backward compat
    Route::get('/workflows', fn () => view('workflows.index'))->name('workflows');

    // Governance
    Route::prefix('governance')->name('governance.')->group(function () {
        Route::get('/approvals', fn () => view('governance.approvals'))->name('approvals');
        Route::get('/audit', fn () => view('governance.audit'))->name('audit');
        Route::get('/decisions', fn () => view('governance.decisions'))->name('decisions');
    });

    // Security
    Route::get('/security', fn () => view('security.center'))->name('security.center');

    // Organization
    Route::prefix('organization')->name('org.')->group(function () {
        Route::get('/departments', fn () => view('org.departments'))->name('departments');
        Route::get('/members', fn () => view('org.members'))->name('members');
        Route::get('/knowledge', fn () => view('org.knowledge'))->name('knowledge');
        Route::get('/settings', fn () => view('org.settings'))->name('settings');
    });

    // Billing
    Route::prefix('billing')->name('billing.')->group(function () {
        Route::get('/', fn () => view('billing.index'))->name('index');
        Route::get('/plans', fn () => view('billing.plans'))->name('plans');
        Route::get('/success', [BillingController::class, 'success'])->name('success');
        Route::get('/portal', [BillingController::class, 'portal'])->name('portal');
        Route::post('/checkout/{plan}', [BillingController::class, 'checkout'])->name('checkout');
        Route::get('/settings', fn () => view('billing.settings'))->name('settings.billing');
    });

    // Settings (catch-all for Jetstream profile routes)
    Route::get('/settings/billing', fn () => view('billing.settings'))->name('settings.billing');
});

// Stripe webhook — must be outside auth middleware + CSRF exempt (handled in VerifyCsrfToken)
Route::post('/webhooks/stripe', [BillingController::class, 'webhook'])->name('stripe.webhook');
