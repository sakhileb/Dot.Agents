<?php

namespace App\Skills;

use App\Skills\Contracts\SkillContract;

/**
 * Base class for all agentic skills.
 *
 * Provides config management and shared utilities so concrete skills
 * can focus entirely on their domain logic.
 */
abstract class BaseSkill implements SkillContract
{
    protected array $config = [];

    // ── Config helpers ───────────────────────────────────

    public function withConfig(array $config): static
    {
        $this->config = array_merge($this->config, $config);

        return $this;
    }

    protected function cfg(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    // ── Shared utilities ─────────────────────────────────

    /**
     * Simple dot-notation resolver for nested arrays.
     * e.g. resolve('step_0.output.summary', $context)
     */
    protected function resolve(string $path, array $data): mixed
    {
        foreach (explode('.', $path) as $segment) {
            if (! is_array($data) || ! array_key_exists($segment, $data)) {
                return null;
            }
            $data = $data[$segment];
        }

        return $data;
    }

    /**
     * Count keyword occurrences across a content string (case-insensitive).
     */
    protected function countKeywords(string $content, array $keywords): int
    {
        $content = strtolower($content);
        $count = 0;
        foreach ($keywords as $kw) {
            $count += substr_count($content, strtolower($kw));
        }

        return $count;
    }

    /**
     * Clamp a score between 0 and 100.
     */
    protected function clamp(float $value, float $min = 0.0, float $max = 100.0): float
    {
        return max($min, min($max, $value));
    }

    /**
     * Resolve a letter grade from a numeric score.
     */
    protected function grade(float $score): string
    {
        return match (true) {
            $score >= 90 => 'A',
            $score >= 80 => 'B',
            $score >= 70 => 'C',
            $score >= 60 => 'D',
            default => 'F',
        };
    }

    /**
     * Serialize any value to a JSON-safe string for keyword analysis.
     */
    protected function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        return (string) json_encode($value);
    }
}
