<?php

namespace App\Services\AI;

use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Skills\Contracts\SkillContract;
use App\Skills\Core\ContextEngineeringSkill;
use App\Skills\Core\ContextMemorySkill;
use App\Skills\Core\ContextOptimizationSkill;
use App\Skills\Core\MemoryManagementSkill;
use App\Skills\Governance\AuditLoggingSkill;
use App\Skills\Governance\ConfidenceScoringSkill;
use App\Skills\Governance\RiskAssessmentSkill;
use App\Skills\Governance\SelfVerificationSkill;
use App\Skills\Meta\AgentAuditorSkill;
use App\Skills\Meta\AgentEvaluatorSkill;
use App\Skills\Meta\SkillCombinatorSkill;
use App\Skills\Meta\SkillIntrospectionSkill;
use App\Skills\Meta\SuperpowersSkill;
use App\Skills\Platform\AudienceIntelligenceSkill;
use App\Skills\Platform\CampaignIntelligenceSkill;
use App\Skills\Platform\ContentBatchSkill;
use App\Skills\Platform\ContentQualitySkill;
use App\Skills\Platform\ExcelAnalysisSkill;
use App\Skills\Platform\ExcelDataProcessingSkill;
use App\Skills\Platform\ExcelExportSkill;
use App\Skills\Platform\MarketingIntelligenceSkill;
use App\Skills\Platform\MassContentGenerationSkill;
use App\Skills\Platform\SeoAnalyserSkill;
use App\Skills\Platform\SeoAuditSkill;
use App\Skills\Platform\SeoOptimizationSkill;
use App\Skills\Platform\VideoProductionSkill;
use App\Skills\Platform\VideoScriptingSkill;
use App\Skills\Platform\VideoScriptWriterSkill;
use App\Skills\Platform\WorkflowOptimizationSkill;
use App\Skills\Workforce\CollaborationSkill;
use App\Skills\Workforce\DelegationSkill;
use App\Skills\Workforce\TaskDecompositionSkill;
use App\Skills\Workforce\WorkforceOrchestrationSkill;
use Illuminate\Support\Collection;

/**
 * Skill Registry Service
 *
 * The single source of truth for all agentic skill implementations.
 *
 * Responsibilities:
 *   – Auto-register all built-in PHP skill implementations on boot
 *   – Resolve a skill instance by key (checking registry first, then DB)
 *   – Enumerate skills available to or enabled on a given deployment
 */
class SkillRegistryService
{
    /** @var array<string, string>  key → fully-qualified class name */
    private array $registry = [];

    public function __construct()
    {
        $this->bootBuiltInSkills();
    }

    // ── Registration ─────────────────────────────────────

    /** Register a skill implementation for a key. Idempotent. */
    public function register(string $key, string $class): void
    {
        $this->registry[$key] = $class;
    }

    // ── Resolution ───────────────────────────────────────

    /**
     * Resolve a live skill instance by key.
     * Checks the in-memory registry first, then the DB agent_skills table.
     *
     * @throws \RuntimeException when no implementation is found
     */
    public function resolve(string $key): SkillContract
    {
        if (isset($this->registry[$key])) {
            return app($this->registry[$key]);
        }

        $skill = AgentSkill::where('key', $key)->where('is_active', true)->first();

        if ($skill && $skill->class && class_exists($skill->class)) {
            return app($skill->class);
        }

        throw new \RuntimeException("Skill [{$key}] not found or has no PHP implementation.");
    }

    /** Return true when a skill key has a resolvable implementation. */
    public function hasImplementation(string $key): bool
    {
        if (isset($this->registry[$key])) {
            return true;
        }

        $skill = AgentSkill::where('key', $key)->where('is_active', true)->first();

        return $skill && $skill->class && class_exists($skill->class);
    }

    // ── Deployment-scoped queries ─────────────────────────

    /**
     * Return all AgentSkill models assigned and enabled for a deployment.
     */
    public function getEnabledSkills(AgentDeployment $deployment): Collection
    {
        return $deployment->skillAssignments()
            ->where('is_enabled', true)
            ->with('skill')
            ->get()
            ->pluck('skill')
            ->filter();
    }

    /**
     * Return true when a specific skill is enabled on a deployment.
     */
    public function deploymentHasSkill(AgentDeployment $deployment, string $key): bool
    {
        return $deployment->skillAssignments()
            ->where('is_enabled', true)
            ->whereHas('skill', fn ($q) => $q->where('key', $key))
            ->exists();
    }

    /**
     * Return all registered skill keys (built-in only).
     */
    public function registeredKeys(): array
    {
        return array_keys($this->registry);
    }

    // ── Boot ─────────────────────────────────────────────

    /**
     * Auto-register all built-in Day One + Meta-Agent skill implementations.
     */
    private function bootBuiltInSkills(): void
    {
        $builtIns = [
            // ── Day One Skills ──────────────────────────
            'workforce-orchestration' => WorkforceOrchestrationSkill::class,
            'task-decomposition' => TaskDecompositionSkill::class,
            'delegation' => DelegationSkill::class,
            'collaboration' => CollaborationSkill::class,
            'self-verification' => SelfVerificationSkill::class,
            'confidence-scoring' => ConfidenceScoringSkill::class,
            'audit-logging' => AuditLoggingSkill::class,
            'risk-assessment' => RiskAssessmentSkill::class,
            'workflow-optimization' => WorkflowOptimizationSkill::class,
            'memory-management' => MemoryManagementSkill::class,
            // ── Meta-Agent Skills ───────────────────────
            'agent-evaluator' => AgentEvaluatorSkill::class,
            'agent-auditor' => AgentAuditorSkill::class,
            // ── Community-Sourced Skills ────────────────
            // excel-data-processing   (haris-musa/excel-mcp-server)
            'excel-data-processing' => ExcelDataProcessingSkill::class,
            // marketing-intelligence  (coreyhaines31/marketingskills)
            'marketing-intelligence' => MarketingIntelligenceSkill::class,
            // seo-optimization        (agricidaniel/claude-seo) — router kept for backward compat
            'seo-optimization' => SeoOptimizationSkill::class,
            // seo-analyser           — extracted from seo-optimization (content analysis)
            'seo-analyser' => SeoAnalyserSkill::class,
            // seo-audit              — extracted from seo-optimization (technical checklist)
            'seo-audit' => SeoAuditSkill::class,
            // video-scripting         (remotion-dev/remotion) — router kept for backward compat
            'video-scripting' => VideoScriptingSkill::class,
            // video-script-writer    — extracted from video-scripting (script, storyboard)
            'video-script-writer' => VideoScriptWriterSkill::class,
            // video-production       — extracted from video-scripting (scene_breakdown, render_config)
            'video-production' => VideoProductionSkill::class,
            // context-engineering     (muratcankoylan/agent-skills-for-context-engineering) — router
            'context-engineering' => ContextEngineeringSkill::class,
            // context-optimization   — extracted from context-engineering (optimize, compress)
            'context-optimization' => ContextOptimizationSkill::class,
            // context-memory         — extracted from context-engineering (prioritize, inject)
            'context-memory' => ContextMemorySkill::class,
            // mass-content-generation (massgen/massgen) — router kept for backward compat
            'mass-content-generation' => MassContentGenerationSkill::class,
            // content-batch          — extracted from mass-content-generation (generate_batch, template)
            'content-batch' => ContentBatchSkill::class,
            // content-quality        — extracted from mass-content-generation (validate_quality, distribute)
            'content-quality' => ContentQualitySkill::class,
            // campaign-intelligence  — extracted from marketing-intelligence (analyze_campaign, content_brief)
            'campaign-intelligence' => CampaignIntelligenceSkill::class,
            // audience-intelligence  — extracted from marketing-intelligence (segment_audience, measure_roi)
            'audience-intelligence' => AudienceIntelligenceSkill::class,
            // superpowers             (obra/superpowers) — router kept for backward compat
            'superpowers' => SuperpowersSkill::class,
            // skill-introspection    — extracted from superpowers (introspect, extend)
            'skill-introspection' => SkillIntrospectionSkill::class,
            // skill-combinator       — extracted from superpowers (combine, augment)
            'skill-combinator' => SkillCombinatorSkill::class,
            // excel-analysis         — extracted from excel-data-processing (parse, analyze)
            'excel-analysis' => ExcelAnalysisSkill::class,
            // excel-export           — extracted from excel-data-processing (generate, export)
            'excel-export' => ExcelExportSkill::class,
        ];

        foreach ($builtIns as $key => $class) {
            $this->registry[$key] = $class;
        }
    }
}
