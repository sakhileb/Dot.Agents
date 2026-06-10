<?php

namespace App\Services\Skills;

/**
 * Value object returned by SkillExecutionValidator.
 */
readonly class ValidationResult
{
    public function __construct(
        public bool $passed,
        public array $checks,
        public ?string $reason,
    ) {}
}
