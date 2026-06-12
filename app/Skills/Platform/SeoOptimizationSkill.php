<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * SeoOptimizationSkill — backward-compatible router (Layer 5 — Platform Intelligence)
 *
 * This class is kept for compatibility with existing DB records, deployments,
 * and API consumers that reference the 'seo-optimization' skill key.
 *
 * All logic has been extracted into focused skill classes:
 *   - SeoAnalyserSkill  (key: seo-analyser)  — analyze, keyword_research, content_score
 *   - SeoAuditSkill     (key: seo-audit)      — audit
 *
 * New deployments should reference the focused skills directly.
 *
 * @see SeoAnalyserSkill
 * @see SeoAuditSkill
 */
class SeoOptimizationSkill extends SeoScoringHelper
{
    private SeoAnalyserSkill $analyser;

    private SeoAuditSkill $auditor;

    public function __construct()
    {
        $this->analyser = new SeoAnalyserSkill;
        $this->auditor = new SeoAuditSkill;
    }

    public function key(): string
    {
        return 'seo-optimization';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Delegates to SeoAnalyserSkill or SeoAuditSkill based on the action.
     *
     * Actions:
     *   analyze         → SeoAnalyserSkill::execute
     *   keyword_research → SeoAnalyserSkill::execute
     *   content_score   → SeoAnalyserSkill::execute
     *   audit           → SeoAuditSkill::execute
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'analyze';

        if ($action === 'audit') {
            return $this->auditor->execute($input, $context);
        }

        // analyze | keyword_research | content_score → SeoAnalyserSkill
        // Unknown actions are also forwarded so SeoAnalyserSkill returns the
        // canonical error message.
        return $this->analyser->execute($input, $context);
    }
}
