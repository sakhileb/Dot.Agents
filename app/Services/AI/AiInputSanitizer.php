<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\Models\AgentDeployment;
use App\Services\Governance\AuditService;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Centralised AI input safety layer.
 *
 * All user-supplied strings that will be forwarded to an LLM must pass
 * through this service before reaching the model. Detects prompt injection,
 * PII patterns, and other adversarial inputs. Logs every detected violation
 * as a SecurityEvent so the Digital Immune System can react.
 *
 * Usage:
 *   app(AiInputSanitizer::class)->assertSafe($userInput, $deployment);
 *   // throws if injection detected
 *
 *   $isSafe = app(AiInputSanitizer::class)->isSafe($userInput, $deployment);
 *   // returns bool
 */
class AiInputSanitizer
{
    public function __construct(private readonly AuditService $auditService) {}

    /**
     * Check whether input is safe for forwarding to an LLM.
     * Logs a SecurityEvent on any detection.
     */
    public function isSafe(string $input, ?AgentDeployment $deployment = null): bool
    {
        return ! $this->auditService->detectPromptInjection($input, $deployment)
            && ! $this->containsSystemBypass($input)
            && ! $this->exceedsLengthLimit($input);
    }

    /**
     * Assert the input is safe, throwing an exception if not.
     *
     * @throws AuthorizationException
     */
    public function assertSafe(string $input, ?AgentDeployment $deployment = null): void
    {
        if (! $this->isSafe($input, $deployment)) {
            abort(422, 'Input failed AI safety checks. Request rejected.');
        }
    }

    /**
     * Sanitize input by stripping the most common injection sequences.
     * Use when you want to allow the request but neutralize known vectors.
     * Prefer assertSafe() for untrusted external input.
     */
    public function sanitize(string $input): string
    {
        $stripped = preg_replace('/\[system\]|\<system\>|###\s*system/i', '[REMOVED]', $input);

        return mb_substr(trim($stripped ?? $input), 0, $this->maxLength());
    }

    /**
     * Scan multiple fields at once and return the first unsafe field name,
     * or null if all are safe.
     *
     * @param  array<string, string>  $fields  ['fieldName' => 'value']
     */
    public function firstUnsafeField(array $fields, ?AgentDeployment $deployment = null): ?string
    {
        foreach ($fields as $name => $value) {
            if (! $this->isSafe($value, $deployment)) {
                return $name;
            }
        }

        return null;
    }

    private function containsSystemBypass(string $input): bool
    {
        $patterns = [
            '/^SYSTEM:\s/i',
            '/\|\|.*\|\|/i',
            '/\{\{.*\}\}/i',  // Template injection attempt
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    private function exceedsLengthLimit(string $input): bool
    {
        return mb_strlen($input) > $this->maxLength();
    }

    private function maxLength(): int
    {
        return (int) config('ai.input_max_length', 32000);
    }
}
