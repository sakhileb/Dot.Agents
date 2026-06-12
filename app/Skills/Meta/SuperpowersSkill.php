<?php

namespace App\Skills\Meta;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * SuperpowersSkill — backward-compatible router (Layer 6 — Meta-Agent)
 *
 * Kept for compatibility with existing DB records and API consumers.
 * All logic has been extracted into focused skill classes:
 *   - SkillIntrospectionSkill (key: skill-introspection) — introspect, extend
 *   - SkillCombinatorSkill    (key: skill-combinator)    — combine, augment
 *
 * New deployments should reference the focused skills directly.
 */
class SuperpowersSkill extends BaseSkill
{
    private SkillIntrospectionSkill $introspection;

    private SkillCombinatorSkill $combinator;

    public function __construct()
    {
        $this->introspection = new SkillIntrospectionSkill;
        $this->combinator = new SkillCombinatorSkill;
    }

    public function key(): string
    {
        return 'superpowers';
    }

    public function layer(): string
    {
        return 'meta';
    }

    /**
     * Delegates to SkillIntrospectionSkill or SkillCombinatorSkill based on action.
     *
     * Actions:
     *   introspect, extend → SkillIntrospectionSkill
     *   combine, augment   → SkillCombinatorSkill
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'introspect';

        return match ($action) {
            'introspect', 'extend' => $this->introspection->execute($input, $context),
            default => $this->combinator->execute($input, $context),
        };
    }
}
