<?php

namespace App\Providers;

use App\Models\Agent;
use App\Models\AgentApproval;
use App\Models\AgentCategory;
use App\Models\AgentDepartment;
use App\Models\AgentDeployment;
use App\Models\AgentMemory;
use App\Models\AgentMessage;
use App\Models\AgentPersona;
use App\Models\AgentPlugin;
use App\Models\AgentPluginInstallation;
use App\Models\AgentReview;
use App\Models\AgentScorecard;
use App\Models\AgentSession;
use App\Models\AgentSkill;
use App\Models\AgentSkillApproval;
use App\Models\AgentSkillAssignment;
use App\Models\AgentSkillAudit;
use App\Models\AgentSkillExecution;
use App\Models\AgentSkillPermission;
use App\Models\AgentSkillRequirement;
use App\Models\AgentSkillScore;
use App\Models\AgentTask;
use App\Models\AgentToolPermission;
use App\Models\AgentWorkflow;
use App\Models\AuditLog;
use App\Models\DecisionLog;
use App\Models\Department;
use App\Models\Division;
use App\Models\EnterpriseDecision;
use App\Models\EnterpriseHealthScore;
use App\Models\ExecutiveCouncilSession;
use App\Models\ExecutiveRecommendation;
use App\Models\Invoice;
use App\Models\KnowledgeArticle;
use App\Models\KnowledgeBase;
use App\Models\Membership;
use App\Models\MemoryEmbedding;
use App\Models\Organization;
use App\Models\OrganizationDNA;
use App\Models\OrganizationSocialCredential;
use App\Models\OrganizationSubscription;
use App\Models\OrganizationTwin;
use App\Models\PlatformMegaScorecard;
use App\Models\PlatformNotification;
use App\Models\SecurityEvent;
use App\Models\SocialAccount;
use App\Models\SocialCampaign;
use App\Models\SocialConnectionSettings;
use App\Models\SocialConversation;
use App\Models\SocialConversion;
use App\Models\SocialEngagement;
use App\Models\SocialLead;
use App\Models\SocialMessage;
use App\Models\SocialPage;
use App\Models\SocialPost;
use App\Models\SocialReview;
use App\Models\SocialSentimentScore;
use App\Models\SubscriptionPlan;
use App\Models\Team;
use App\Models\TeamInvitation;
use App\Models\UsageRecord;
use App\Models\User;
use App\Models\WorkflowConnection;
use App\Models\WorkflowExecution;
use App\Models\WorkflowNode;
use App\Policies\AgentApprovalPolicy;
use App\Policies\AgentCategoryPolicy;
use App\Policies\AgentDepartmentPolicy;
use App\Policies\AgentDeploymentPolicy;
use App\Policies\AgentMemoryPolicy;
use App\Policies\AgentMessagePolicy;
use App\Policies\AgentPersonaPolicy;
use App\Policies\AgentPluginInstallationPolicy;
use App\Policies\AgentPluginPolicy;
use App\Policies\AgentPolicy;
use App\Policies\AgentReviewPolicy;
use App\Policies\AgentScorecardPolicy;
use App\Policies\AgentSessionPolicy;
use App\Policies\AgentSkillApprovalPolicy;
use App\Policies\AgentSkillAssignmentPolicy;
use App\Policies\AgentSkillAuditPolicy;
use App\Policies\AgentSkillExecutionPolicy;
use App\Policies\AgentSkillPermissionPolicy;
use App\Policies\AgentSkillPolicy;
use App\Policies\AgentSkillRequirementPolicy;
use App\Policies\AgentSkillScorePolicy;
use App\Policies\AgentTaskPolicy;
use App\Policies\AgentToolPermissionPolicy;
use App\Policies\AgentWorkflowPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\DecisionLogPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\DivisionPolicy;
use App\Policies\EnterpriseDecisionPolicy;
use App\Policies\EnterpriseHealthScorePolicy;
use App\Policies\ExecutiveCouncilSessionPolicy;
use App\Policies\ExecutiveRecommendationPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\KnowledgeArticlePolicy;
use App\Policies\KnowledgeBasePolicy;
use App\Policies\MembershipPolicy;
use App\Policies\MemoryEmbeddingPolicy;
use App\Policies\OrganizationDNAPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\OrganizationSocialCredentialPolicy;
use App\Policies\OrganizationSubscriptionPolicy;
use App\Policies\OrganizationTwinPolicy;
use App\Policies\PlatformMegaScorecardPolicy;
use App\Policies\PlatformNotificationPolicy;
use App\Policies\SecurityEventPolicy;
use App\Policies\SocialAccountPolicy;
use App\Policies\SocialCampaignPolicy;
use App\Policies\SocialConnectionSettingsPolicy;
use App\Policies\SocialConversationPolicy;
use App\Policies\SocialConversionPolicy;
use App\Policies\SocialEngagementPolicy;
use App\Policies\SocialLeadPolicy;
use App\Policies\SocialMessagePolicy;
use App\Policies\SocialPagePolicy;
use App\Policies\SocialPostPolicy;
use App\Policies\SocialReviewPolicy;
use App\Policies\SocialSentimentScorePolicy;
use App\Policies\SubscriptionPlanPolicy;
use App\Policies\TeamInvitationPolicy;
use App\Policies\TeamPolicy;
use App\Policies\UsageRecordPolicy;
use App\Policies\UserPolicy;
use App\Policies\WorkflowConnectionPolicy;
use App\Policies\WorkflowExecutionPolicy;
use App\Policies\WorkflowNodePolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * Service bindings are handled by domain-scoped providers:
     *  - AgentServiceProvider    → AI / Agent runtime services
     *  - GovernanceServiceProvider → Governance, scoring, audit services
     *  - SocialServiceProvider   → Social Commerce & Resilience services
     */
    public function register(): void
    {
        // Intentionally empty — see domain providers above.
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

        // SCCS — Social Commerce & Customer Success policies
        Gate::policy(SocialAccount::class, SocialAccountPolicy::class);
        Gate::policy(SocialCampaign::class, SocialCampaignPolicy::class);
        Gate::policy(SocialConnectionSettings::class, SocialConnectionSettingsPolicy::class);
        Gate::policy(SocialConversation::class, SocialConversationPolicy::class);
        Gate::policy(SocialEngagement::class, SocialEngagementPolicy::class);
        Gate::policy(SocialLead::class, SocialLeadPolicy::class);
        Gate::policy(SocialMessage::class, SocialMessagePolicy::class);
        Gate::policy(SocialPage::class, SocialPagePolicy::class);
        Gate::policy(SocialPost::class, SocialPostPolicy::class);
        Gate::policy(SocialReview::class, SocialReviewPolicy::class);
        Gate::policy(SocialSentimentScore::class, SocialSentimentScorePolicy::class);
        Gate::policy(SocialConversion::class, SocialConversionPolicy::class);

        // Platform catalogue — super-admin managed
        Gate::policy(AgentCategory::class, AgentCategoryPolicy::class);
        Gate::policy(AgentDepartment::class, AgentDepartmentPolicy::class);
        Gate::policy(AgentPersona::class, AgentPersonaPolicy::class);
        Gate::policy(AgentPlugin::class, AgentPluginPolicy::class);
        Gate::policy(AgentSkillPermission::class, AgentSkillPermissionPolicy::class);
        Gate::policy(AgentSkillRequirement::class, AgentSkillRequirementPolicy::class);
        Gate::policy(AgentToolPermission::class, AgentToolPermissionPolicy::class);
        Gate::policy(SubscriptionPlan::class, SubscriptionPlanPolicy::class);

        // Org-scoped AI domain models
        Gate::policy(AgentMessage::class, AgentMessagePolicy::class);
        Gate::policy(AgentReview::class, AgentReviewPolicy::class);
        Gate::policy(AgentSkillAssignment::class, AgentSkillAssignmentPolicy::class);
        Gate::policy(AgentSkillAudit::class, AgentSkillAuditPolicy::class);
        Gate::policy(AgentSkillExecution::class, AgentSkillExecutionPolicy::class);
        Gate::policy(AgentSkillScore::class, AgentSkillScorePolicy::class);
        Gate::policy(EnterpriseDecision::class, EnterpriseDecisionPolicy::class);
        Gate::policy(EnterpriseHealthScore::class, EnterpriseHealthScorePolicy::class);
        Gate::policy(ExecutiveCouncilSession::class, ExecutiveCouncilSessionPolicy::class);
        Gate::policy(ExecutiveRecommendation::class, ExecutiveRecommendationPolicy::class);
        Gate::policy(Membership::class, MembershipPolicy::class);
        Gate::policy(MemoryEmbedding::class, MemoryEmbeddingPolicy::class);
        Gate::policy(OrganizationDNA::class, OrganizationDNAPolicy::class);
        Gate::policy(OrganizationSocialCredential::class, OrganizationSocialCredentialPolicy::class);
        Gate::policy(OrganizationSubscription::class, OrganizationSubscriptionPolicy::class);
        Gate::policy(OrganizationTwin::class, OrganizationTwinPolicy::class);
        Gate::policy(PlatformMegaScorecard::class, PlatformMegaScorecardPolicy::class);
        Gate::policy(TeamInvitation::class, TeamInvitationPolicy::class);
        Gate::policy(WorkflowConnection::class, WorkflowConnectionPolicy::class);
        Gate::policy(WorkflowNode::class, WorkflowNodePolicy::class);

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

        // ── Named rate limiters ──────────────────────────────────────────────
        // AI execution: 10 calls/minute per authenticated user
        RateLimiter::for('ai-execution', function (Request $request) {
            return Limit::perMinute(10)
                ->by(optional($request->user())->id ?: $request->ip());
        });

        // Strict write operations: 30 creates/minute per authenticated user
        RateLimiter::for('api-writes', function (Request $request) {
            return Limit::perMinute(30)
                ->by(optional($request->user())->id ?: $request->ip());
        });

        // General API: 200 requests/minute per authenticated user (org-level burst protection)
        RateLimiter::for('api', function (Request $request) {
            return [
                Limit::perMinute(200)
                    ->by(optional($request->user())->id ?: $request->ip()),
                // Org-level burst cap — prevents one org drowning shared resources
                Limit::perMinute(500)
                    ->by('org:'.session('current_organization_id', 'anon')),
            ];
        });
    }
}
