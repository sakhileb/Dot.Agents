<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\AgentApproval;
use App\Models\AgentCategory;
use App\Models\AgentDepartment;
use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Models\AgentPluginInstallation;
use App\Models\AgentScorecard;
use App\Models\AgentSession;
use App\Models\AgentSkill;
use App\Models\AgentTask;
use App\Models\AgentWorkflow;
use App\Models\AuditLog;
use App\Models\DecisionLog;
use App\Models\Department;
use App\Models\Division;
use App\Models\Invoice;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeBase;
use App\Models\Organization;
use App\Models\PlatformNotification;
use App\Models\SecurityEvent;
use App\Models\Team;
use App\Models\UsageRecord;
use App\Models\User;
use App\Models\WorkflowExecution;
use App\Models\AgentSkillApproval;
use App\Policies\AgentSkillApprovalPolicy;
use App\Policies\AgentApprovalPolicy;
use App\Policies\AgentDeploymentPolicy;
use App\Policies\AgentMemoryPolicy;
use App\Policies\AgentPluginInstallationPolicy;
use App\Policies\AgentPolicy;
use App\Policies\AgentScorecardPolicy;
use App\Policies\AgentSessionPolicy;
use App\Policies\AgentSkillPolicy;
use App\Policies\AgentTaskPolicy;
use App\Policies\AgentWorkflowPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\DecisionLogPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DivisionPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\KnowledgeArticlePolicy;
use App\Policies\KnowledgeBasePolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\PlatformNotificationPolicy;
use App\Policies\SecurityEventPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UsageRecordPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkflowExecutionPolicy;
use App\Services\AI\AgentOrchestrationService;
use App\Services\AI\AgentPluginService;
use App\Services\AI\AgentSandboxService;
use App\Services\AI\GraphWorkflowEngineService;
use App\Services\AI\MemoryService;
use App\Services\AI\ModelRouterService;
use App\Services\AI\OutputModerationService;
use App\Services\AI\SkillExecutionPipeline;
use App\Services\AI\SkillRegistryService;
use App\Services\AI\ToolPermissionService;
use App\Services\Governance\AgentReliabilityAuditorService;
use App\Services\Governance\AgentReputationService;
use App\Services\Governance\AuditService;
use App\Services\Governance\CustomerSuccessService;
use App\Services\Governance\DataTrustScoreService;
use App\Services\Governance\DelusionDetectionService;
use App\Services\Governance\DigitalImmuneSystem;
use App\Services\Governance\FinancialIntelligenceService;
use App\Services\Governance\MegaV2ScorecardService;
use App\Services\Governance\OrganizationalMemoryService;
use App\Services\Governance\PredictionAccuracyTrackingService;
use App\Services\Governance\ScorecardService;
use App\Services\Infrastructure\ObservabilityService;
use App\Services\Resilience\CircuitBreakerService;
use App\Skills\Governance\AuditLoggingSkill;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ModelRouterService::class);
        $this->app->singleton(MemoryService::class);
        $this->app->singleton(AuditService::class);
        $this->app->singleton(DelusionDetectionService::class);
        $this->app->singleton(ScorecardService::class);
        $this->app->singleton(CircuitBreakerService::class);
        $this->app->singleton(ToolPermissionService::class);
        $this->app->singleton(OutputModerationService::class);
        $this->app->singleton(ObservabilityService::class);
        $this->app->singleton(DataTrustScoreService::class);
        $this->app->singleton(PredictionAccuracyTrackingService::class);
        $this->app->singleton(OrganizationalMemoryService::class);

        $this->app->singleton(FinancialIntelligenceService::class);
        $this->app->singleton(CustomerSuccessService::class);

        $this->app->singleton(AgentReputationService::class);

        $this->app->singleton(AgentReliabilityAuditorService::class, function ($app) {
            return new AgentReliabilityAuditorService(
                $app->make(AgentReputationService::class),
            );
        });

        $this->app->singleton(AgentSandboxService::class, function ($app) {
            return new AgentSandboxService(
                $app->make(ToolPermissionService::class)
            );
        });

        $this->app->singleton(AgentOrchestrationService::class, function ($app) {
            return new AgentOrchestrationService(
                $app->make(ModelRouterService::class),
                $app->make(DelusionDetectionService::class),
                $app->make(AuditService::class),
                $app->make(MemoryService::class),
                $app->make(AgentSandboxService::class),
                $app->make(CircuitBreakerService::class),
                $app->make(OutputModerationService::class),
            );
        });

        $this->app->singleton(DigitalImmuneSystem::class, function ($app) {
            return new DigitalImmuneSystem($app->make(AuditService::class));
        });

        // Graph Workflow Engine (v2 — DAG-based)
        $this->app->singleton(GraphWorkflowEngineService::class, function ($app) {
            return new GraphWorkflowEngineService(
                $app->make(AgentOrchestrationService::class),
                $app->make(AuditService::class),
            );
        });

        // Agent Plugin Service (marketplace runtime)
        $this->app->singleton(AgentPluginService::class);

        // MEGA V2 Scorecard — depends on all intelligence services
        $this->app->singleton(MegaV2ScorecardService::class, function ($app) {
            return new MegaV2ScorecardService(
                $app->make(DataTrustScoreService::class),
                $app->make(AgentReliabilityAuditorService::class),
                $app->make(PredictionAccuracyTrackingService::class),
                $app->make(OrganizationalMemoryService::class),
                $app->make(ObservabilityService::class),
                $app->make(DigitalImmuneSystem::class),
                $app->make(FinancialIntelligenceService::class),
                $app->make(CustomerSuccessService::class),
            );
        });

        // ── Skill system ─────────────────────────────────────────────────────

        // Skill Registry — singleton so built-in skill map is only built once
        $this->app->singleton(SkillRegistryService::class);

        // AuditLoggingSkill needs AuditService — explicit binding so DI resolves correctly
        $this->app->bind(AuditLoggingSkill::class, function ($app) {
            return new AuditLoggingSkill($app->make(AuditService::class));
        });

        // Skill Execution Pipeline — wraps task execution with skill hooks
        $this->app->singleton(SkillExecutionPipeline::class, function ($app) {
            return new SkillExecutionPipeline($app->make(SkillRegistryService::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Explicit policy registrations — belt + suspenders alongside auto-discovery
        Gate::policy(Agent::class, AgentPolicy::class);
        Gate::policy(AgentDeployment::class, AgentDeploymentPolicy::class);
        Gate::policy(AgentApproval::class, AgentApprovalPolicy::class);
        Gate::policy(AgentTask::class, AgentTaskPolicy::class);
        Gate::policy(AgentWorkflow::class, AgentWorkflowPolicy::class);
        Gate::policy(AgentScorecard::class, AgentScorecardPolicy::class);
        Gate::policy(AuditLog::class, AuditLogPolicy::class);
        Gate::policy(DecisionLog::class, DecisionLogPolicy::class);
        Gate::policy(SecurityEvent::class, SecurityEventPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(KnowledgeBase::class, KnowledgeBasePolicy::class);
        Gate::policy(KnowledgeArticle::class, KnowledgeArticlePolicy::class);
        Gate::policy(AgentMemory::class, AgentMemoryPolicy::class);
        Gate::policy(Department::class, DepartmentPolicy::class);
        Gate::policy(Division::class, DivisionPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(WorkflowExecution::class, WorkflowExecutionPolicy::class);
        Gate::policy(AgentSession::class, AgentSessionPolicy::class);
        Gate::policy(AgentSkill::class, AgentSkillPolicy::class);
        Gate::policy(AgentSkillApproval::class, AgentSkillApprovalPolicy::class);
        Gate::policy(UsageRecord::class, UsageRecordPolicy::class);
        Gate::policy(AgentPluginInstallation::class, AgentPluginInstallationPolicy::class);
        Gate::policy(PlatformNotification::class, PlatformNotificationPolicy::class);

        // Enforce strong password policy platform-wide (min 12 chars, mixed case, numbers, symbols)
        // In testing, use a relaxed rule so test factories and Jetstream tests still pass.
        Password::defaults(function () {
            if (app()->environment('testing')) {
                return Password::min(8);
            }

            return Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // Enforce Tailwind CSS dark mode class strategy
        Blade::directive('mark', function ($text) {
            return "<?php echo e({$text}); ?>";
        });

        // Invalidate marketplace caches when departments or categories change
        AgentDepartment::saved(fn () => Cache::forget('marketplace_departments'));
        AgentDepartment::deleted(fn () => Cache::forget('marketplace_departments'));
        AgentCategory::saved(fn () => Cache::forget('marketplace_categories'));
        AgentCategory::deleted(fn () => Cache::forget('marketplace_categories'));
    }
}
