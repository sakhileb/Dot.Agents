<?php

use App\Jobs\RunDigitalImmuneSystemCheck;
use App\Models\AgentMessage;
use App\Models\AuditLog;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Scheduled Platform Jobs ──────────────────────────────────────────────────

// Digital Immune System — runs every 15 minutes, without overlapping, on one server
Schedule::job(new RunDigitalImmuneSystemCheck)
    ->everyFifteenMinutes()
    ->withoutOverlapping(10)
    ->onOneServer()
    ->name('dis-health-check');

// Prune old audit logs and agent messages older than 90 days — runs nightly at 02:00
Schedule::command('model:prune', [
    '--model' => [
        AuditLog::class,
        AgentMessage::class,
    ],
])->dailyAt('02:00')->onOneServer();

// Expire pending approvals past their deadline — runs every 30 minutes
Schedule::command('approvals:expire-overdue')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();
