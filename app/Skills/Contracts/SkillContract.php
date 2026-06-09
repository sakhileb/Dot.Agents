<?php

namespace App\Skills\Contracts;

use App\Skills\DTOs\SkillResult;

/**
 * Every agentic skill must implement this contract.
 *
 * Skills are invoked by the SkillExecutionPipeline before/after task execution,
 * or on-demand by the WorkforceOrchestrationSkill.
 */
interface SkillContract
{
    /**
     * Execute the skill and return a structured result.
     *
     * @param  array  $input  Skill-specific input payload
     * @param  array  $context  Runtime context: deployment, task, phase, org, etc.
     */
    public function execute(array $input, array $context = []): SkillResult;

    /**
     * The unique identifier for this skill (e.g. 'workforce-orchestration').
     * Must match the `key` column in the agent_skills table.
     */
    public function key(): string;

    /**
     * The layer this skill belongs to.
     * One of: core | enterprise | workforce | governance | platform | meta
     */
    public function layer(): string;
}
