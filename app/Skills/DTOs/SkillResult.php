<?php

namespace App\Skills\DTOs;

/**
 * Immutable result returned by every skill execution.
 *
 * status values:
 *   completed  — skill ran and produced output
 *   failed     — skill errored; see error in output
 *   skipped    — skill determined it had nothing to do
 *   delegated  — workforce orchestration delegated to sub-agents
 */
readonly class SkillResult
{
    public function __construct(
        public string $status,
        public array $output = [],
        public float $confidence = 100.0,
        public array $findings = [],    // issues / flags raised by the skill
        public array $recommendations = [],    // actionable next steps
        public array $metadata = [],
    ) {}

    // ── Static constructors ──────────────────────────────

    public static function completed(
        array $output,
        float $confidence = 100.0,
        array $findings = [],
        array $recommendations = []
    ): self {
        return new self('completed', $output, $confidence, $findings, $recommendations);
    }

    public static function failed(string $error, array $metadata = []): self
    {
        return new self('failed', ['error' => $error], 0.0, [], [], $metadata);
    }

    public static function skipped(string $reason): self
    {
        return new self('skipped', ['reason' => $reason], 100.0);
    }

    public static function delegated(array $output, float $confidence = 85.0, array $findings = []): self
    {
        return new self('delegated', $output, $confidence, $findings);
    }

    // ── Helpers ──────────────────────────────────────────

    public function passed(): bool
    {
        return in_array($this->status, ['completed', 'skipped', 'delegated'], true);
    }

    public function hasCriticalFindings(): bool
    {
        return ! empty($this->findings);
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'output' => $this->output,
            'confidence' => $this->confidence,
            'findings' => $this->findings,
            'recommendations' => $this->recommendations,
            'metadata' => $this->metadata,
        ];
    }

    /** Merge this result with additional metadata. */
    public function withMetadata(array $extra): self
    {
        return new self(
            $this->status,
            $this->output,
            $this->confidence,
            $this->findings,
            $this->recommendations,
            array_merge($this->metadata, $extra),
        );
    }
}
