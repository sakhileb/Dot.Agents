<?php

namespace App\Skills\Core;

use App\Skills\DTOs\SkillResult;

/**
 * ContextEngineeringSkill — backward-compatible router (Layer 1 — Core)
 *
 * Kept for compatibility with existing DB records and API consumers.
 * All logic has been extracted into focused skill classes:
 *   - ContextOptimizationSkill (key: context-optimization) — optimize, compress
 *   - ContextMemorySkill       (key: context-memory)       — prioritize, inject
 *
 * New deployments should reference the focused skills directly.
 */
class ContextEngineeringSkill extends ContextHelper
{
    private ContextOptimizationSkill $optimization;

    private ContextMemorySkill $memory;

    public function __construct()
    {
        $this->optimization = new ContextOptimizationSkill;
        $this->memory = new ContextMemorySkill;
    }

    public function key(): string
    {
        return 'context-engineering';
    }

    public function layer(): string
    {
        return 'core';
    }

    /**
     * Delegates to ContextOptimizationSkill or ContextMemorySkill based on action.
     *
     * Actions:
     *   optimize, compress   → ContextOptimizationSkill
     *   prioritize, inject   → ContextMemorySkill
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'optimize';

        return match ($action) {
            'optimize', 'compress' => $this->optimization->execute($input, $context),
            default => $this->memory->execute($input, $context),
        };
    }
}
