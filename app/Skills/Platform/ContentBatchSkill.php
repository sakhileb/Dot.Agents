<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * ContentBatchSkill — batch generation and template building (Layer 5 — Platform Intelligence)
 *
 * Handles the two creation-focused mass content actions extracted from MassContentGenerationSkill:
 *   generate_batch – produce N content items from a template + variable sets
 *   template       – build a parameterised content template from an example
 *
 * Shares helper methods and constants with ContentQualitySkill via ContentGenerationHelper.
 */
class ContentBatchSkill extends ContentGenerationHelper
{
    public function key(): string
    {
        return 'content-batch';
    }

    public function layer(): string
    {
        return 'platform';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'generate_batch';

        return match ($action) {
            'generate_batch' => $this->generateBatch($input),
            'template' => $this->buildTemplate($input),
            default => SkillResult::failed("Unknown content-batch action: [{$action}]"),
        };
    }

    private function generateBatch(array $input): SkillResult
    {
        $template = $input['template'] ?? '';
        $variableSets = $input['variables'] ?? [];
        $contentType = $input['content_type'] ?? 'general';

        if (empty($template)) {
            return SkillResult::failed('A template string with {variable} placeholders is required');
        }

        if (empty($variableSets)) {
            return SkillResult::failed('At least one variable set is required for batch generation');
        }

        $count = count($variableSets);

        if ($count > self::MAX_BATCH_SIZE) {
            return SkillResult::failed(
                "Batch size {$count} exceeds the maximum of ".self::MAX_BATCH_SIZE.' items per execution. Split into smaller batches.'
            );
        }

        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $template, $placeholderMatches);
        $requiredVars = array_unique($placeholderMatches[1] ?? []);

        $generated = [];
        $errors = [];

        foreach ($variableSets as $idx => $vars) {
            $missing = array_diff($requiredVars, array_keys($vars));
            if (! empty($missing)) {
                $errors[] = "Item #{$idx}: missing variables: ".implode(', ', $missing);

                continue;
            }

            $content = $template;
            foreach ($vars as $key => $value) {
                $content = str_replace("{{$key}}", $value, $content);
            }

            $generated[] = [
                'index' => $idx,
                'content' => $content,
                'word_count' => str_word_count($content),
                'variables_used' => array_keys($vars),
                'content_type' => $contentType,
            ];
        }

        $successRate = $count > 0 ? round(count($generated) / $count * 100, 1) : 0;

        return SkillResult::completed(
            [
                'generated_count' => count($generated),
                'requested_count' => $count,
                'error_count' => count($errors),
                'success_rate_pct' => $successRate,
                'content_type' => $contentType,
                'items' => $generated,
                'template_variables' => $requiredVars,
            ],
            $successRate >= 95 ? 95.0 : 75.0,
            $errors,
            $errors ? ['Fix variable set errors and re-run, or filter your input data'] : []
        );
    }

    private function buildTemplate(array $input): SkillResult
    {
        $example = $input['example'] ?? $input['content'] ?? '';
        $contentType = $input['content_type'] ?? 'general';
        $variables = $input['define_variables'] ?? [];

        if (empty($example) && empty($variables)) {
            return SkillResult::failed('An example content piece or a variables list is required to build a template');
        }

        if (! empty($variables) && ! empty($example)) {
            $template = $example;
            foreach ($variables as $var) {
                $template = preg_replace('/\b'.preg_quote($var, '/').'\b/', "{{$var}}", $template, 1);
            }
        } elseif (! empty($variables)) {
            $template = $this->scaffoldTemplate($contentType, $variables);
        } else {
            preg_match_all('/\b[A-Z][a-zA-Z]{2,}\b/', $example, $matches);
            $detected = array_unique($matches[0]);
            $template = $example;
            foreach ($detected as $word) {
                $varName = strtolower($word);
                $template = str_replace($word, "{{$varName}}", $template);
            }
        }

        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $template, $finalVars);
        $vars = array_unique($finalVars[1] ?? []);

        return SkillResult::completed(
            [
                'template' => $template,
                'content_type' => $contentType,
                'variable_count' => count($vars),
                'variables' => $vars,
                'sample_variable_set' => array_combine($vars, array_fill(0, count($vars), '[value]')),
                'note' => 'Review and refine placeholders before using in production batch generation',
            ],
            80.0
        );
    }
}
