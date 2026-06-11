<?php

use App\Jobs\GenerateMegaV2PlatformScorecard;
use App\Jobs\RunDigitalImmuneSystemCheck;
use App\Models\AgentMessage;
use App\Models\AgentSession;
use App\Models\AgentSkillExecution;
use App\Models\AgentTask;
use App\Models\AuditLog;
use App\Models\PlatformMegaScorecard;
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
        AgentTask::class,
        AgentSession::class,
        AgentSkillExecution::class,
        PlatformMegaScorecard::class,
    ],
])->dailyAt('02:00')->onOneServer();

// Expire pending approvals past their deadline — runs every 30 minutes
Schedule::command('approvals:expire-overdue')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->onOneServer();

// MEGA V2 Autonomous Enterprise Readiness Scorecard — generated daily at 03:15
Schedule::job(new GenerateMegaV2PlatformScorecard)
    ->dailyAt('03:15')
    ->withoutOverlapping(60)
    ->onOneServer()
    ->name('mega-v2-scorecard');

// DWCA — Digital Workforce Certification Audit — runs weekly on Sunday at 04:00
// Audits all agent deployments and updates certification levels + maturity scores
Schedule::command('dwca:audit')
    ->weekly()
    ->sundays()
    ->at('04:00')
    ->withoutOverlapping(120)
    ->onOneServer()
    ->name('dwca-weekly-audit');
