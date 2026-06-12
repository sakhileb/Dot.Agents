<?php

namespace App\Skills\Platform;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Mass Content Generation Skill (Layer 5 — Platform Intelligence)
 *
 * Enables agents to produce, validate, and distribute content at scale.
 * Inspired by massgen/massgen — surfaces batch content generation as
 * a first-class agentic capability with quality guardrails built in.
 *
 * Actions:
 *   generate_batch   – produce N content items from a single template + variable sets
 *   template         – build a parameterised content template from an example
 *   validate_quality – score a batch for quality, consistency, and uniqueness
 *   distribute       – produce a distribution manifest (per-channel content plan)
 */
class MassContentGenerationSkill extends BaseSkill
{
    /** Hard cap on batch size per execution to prevent runaway tasks. */
    private const MAX_BATCH_SIZE = 500;

    /** Minimum uniqueness ratio to pass quality validation. */
    private const MIN_UNIQUENESS_RATIO = 0.80;

    public function key(): string
    {
        return 'mass-content-generation';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Input keys:
     *   action          – generate_batch | template | validate_quality | distribute
     *   template        – string template with {variable} placeholders
     *   variables       – array of variable-set objects [{key: value, ...}]
     *   content_type    – blog | email | social | product_description | ad_copy
     *   items           – array of content strings (for validate_quality)
     *   channels        – array of channel names (for distribute)
     *   batch           – array of generated content items (for distribute)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'generate_batch';

        return match ($action) {
            'generate_batch' => $this->generateBatch($input),
            'template' => $this->buildTemplate($input),
            'validate_quality' => $this->validateQuality($input),
            'distribute' => $this->buildDistributionManifest($input),
            default => SkillResult::failed("Unknown mass-content-generation action: [{$action}]"),
        };
    }

    // ── Actions ──────────────────────────────────────────

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

        // Extract variable names from template
        preg_match_all('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', $template, $placeholderMatches);
        $requiredVars = array_unique($placeholderMatches[1] ?? []);

        $generated = [];
        $errors = [];

        foreach ($variableSets as $idx => $vars) {
            // Validate all required vars are present
            $missing = array_diff($requiredVars, array_keys($vars));
            if (! empty($missing)) {
                $errors[] = "Item #{$idx}: missing variables: ".implode(', ', $missing);

                continue;
            }

            // Render the template
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

        $successRate = count($variableSets) > 0
            ? round(count($generated) / count($variableSets) * 100, 1)
            : 0;

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

        // If specific variables provided, wrap them in {}
        if (! empty($variables) && ! empty($example)) {
            $template = $example;
            foreach ($variables as $var) {
                // Replace first occurrence of word-boundary match with {var}
                $template = preg_replace('/\b'.preg_quote($var, '/').'\b/', "{{$var}}", $template, 1);
            }
        } elseif (! empty($variables)) {
            // Generate a scaffold template from variable names
            $template = $this->scaffoldTemplate($contentType, $variables);
        } else {
            // Auto-detect likely variables from the example (proper nouns, capitalised words)
            preg_match_all('/\b[A-Z][a-zA-Z]{2,}\b/', $example, $matches);
            $detected = array_unique($matches[0]);
            $template = $example;
            foreach ($detected as $word) {
                $varName = strtolower($word);
                $template = str_replace($word, "{{$varName}}", $template);
            }
        }

        // Extract final variable list
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

    private function validateQuality(array $input): SkillResult
    {
        $items = $input['items'] ?? [];

        if (empty($items)) {
            return SkillResult::failed('An array of content items is required for quality validation');
        }

        $contentStrings = array_map(fn ($item) => is_array($item) ? ($item['content'] ?? '') : (string) $item, $items);

        // Uniqueness check: compare normalised fingerprints
        $fingerprints = array_map(fn ($c) => md5(strtolower(preg_replace('/\s+/', ' ', $c))), $contentStrings);
        $uniqueCount = count(array_unique($fingerprints));
        $uniquenessRatio = count($fingerprints) > 0 ? round($uniqueCount / count($fingerprints), 3) : 1.0;

        // Length consistency
        $wordCounts = array_map('str_word_count', $contentStrings);
        $avgWords = count($wordCounts) > 0 ? array_sum($wordCounts) / count($wordCounts) : 0;
        $minWords = min($wordCounts);
        $maxWords = max($wordCounts);
        $lengthVariancePct = $avgWords > 0 ? round(($maxWords - $minWords) / $avgWords * 100, 1) : 0;

        // Empty / malformed items
        $emptyCount = count(array_filter($contentStrings, fn ($c) => strlen(trim($c)) < 10));

        $findings = [];
        $recommendations = [];

        if ($uniquenessRatio < self::MIN_UNIQUENESS_RATIO) {
            $duplicateCount = count($fingerprints) - $uniqueCount;
            $findings[] = "{$duplicateCount} duplicate item(s) detected (uniqueness {$uniquenessRatio})";
            $recommendations[] = 'Review variable sets for duplicate combinations';
        }

        if ($emptyCount > 0) {
            $findings[] = "{$emptyCount} item(s) are empty or too short";
            $recommendations[] = 'Remove or re-generate items shorter than 10 characters';
        }

        if ($lengthVariancePct > 50) {
            $findings[] = "High length variance ({$lengthVariancePct}%) — content consistency may suffer";
            $recommendations[] = 'Normalise variable set content lengths for a more consistent batch';
        }

        $qualityScore = $this->clamp(
            ($uniquenessRatio * 50) +
            ($emptyCount === 0 ? 30 : 10) +
            ($lengthVariancePct < 30 ? 20 : 5)
        );

        return SkillResult::completed(
            [
                'item_count' => count($items),
                'unique_count' => $uniqueCount,
                'duplicate_count' => count($fingerprints) - $uniqueCount,
                'uniqueness_ratio' => $uniquenessRatio,
                'empty_count' => $emptyCount,
                'avg_word_count' => round($avgWords, 1),
                'min_word_count' => $minWords,
                'max_word_count' => $maxWords,
                'length_variance_pct' => $lengthVariancePct,
                'quality_score' => round($qualityScore, 1),
                'grade' => $this->grade($qualityScore),
                'passed' => $qualityScore >= 70 && empty($findings),
            ],
            90.0,
            $findings,
            $recommendations
        );
    }

    private function buildDistributionManifest(array $input): SkillResult
    {
        $batch = $input['batch'] ?? $input['items'] ?? [];
        $channels = $input['channels'] ?? ['blog', 'email', 'social'];
        $contentType = $input['content_type'] ?? 'general';

        if (empty($batch)) {
            return SkillResult::failed('A batch of content items is required to build a distribution manifest');
        }

        $manifest = [];
        $totalItems = count($batch);

        foreach ($channels as $channel) {
            $channelItems = $this->adaptForChannel($batch, $channel);
            $manifest[$channel] = [
                'channel' => $channel,
                'item_count' => count($channelItems),
                'format_rules' => $this->formatRules($channel),
                'items' => array_slice($channelItems, 0, 5), // Include sample of 5
                'full_count' => count($channelItems),
            ];
        }

        return SkillResult::completed(
            [
                'source_item_count' => $totalItems,
                'channel_count' => count($channels),
                'channels' => array_keys($manifest),
                'manifest' => $manifest,
                'estimated_publish_items' => $totalItems * count($channels),
            ],
            88.0,
            [],
            ['Review per-channel adaptations before scheduling — some manual refinement may be needed for high-stakes channels like email']
        );
    }

    // ── Helpers ──────────────────────────────────────────

    private function scaffoldTemplate(string $contentType, array $variables): string
    {
        $placeholders = implode(', ', array_map(fn ($v) => "{{$v}}", $variables));

        return match ($contentType) {
            'product_description' => 'Introducing {{'.($variables[0] ?? 'product_name')."}}: {$placeholders}. Perfect for {{".($variables[1] ?? 'audience').'}}.',
            'email' => 'Hi {{'.($variables[0] ?? 'first_name')."}},\n\nWe wanted to share something special about {$placeholders}.\n\nBest regards,\nThe Team",
            'social' => "🔥 {$placeholders} — {{".($variables[0] ?? 'cta').'}} #{{'.($variables[1] ?? 'hashtag').'}}',
            'ad_copy' => '{{'.($variables[0] ?? 'headline')."}} | {$placeholders} | {{".($variables[count($variables) - 1] ?? 'cta').'}}',
            default => implode(' ', array_map(fn ($v) => "{{$v}}", $variables)).'.',
        };
    }

    private function adaptForChannel(array $batch, string $channel): array
    {
        return array_map(function (array $item) use ($channel) {
            $content = is_array($item) ? ($item['content'] ?? '') : (string) $item;

            return match ($channel) {
                'social' => ['content' => substr($content, 0, 280), 'channel' => 'social'],
                'email' => ['content' => $content, 'subject' => substr($content, 0, 60), 'channel' => 'email'],
                'blog' => ['content' => $content, 'channel' => 'blog'],
                default => ['content' => $content, 'channel' => $channel],
            };
        }, $batch);
    }

    private function formatRules(string $channel): array
    {
        return match ($channel) {
            'social' => ['max_length' => 280, 'supports_hashtags' => true, 'supports_emoji' => true],
            'email' => ['max_subject_length' => 60, 'plain_text' => true, 'html_supported' => true],
            'blog' => ['min_words' => 300, 'seo_title_required' => true, 'meta_description_required' => true],
            'ad_copy' => ['headline_max' => 30, 'description_max' => 90, 'cta_required' => true],
            default => ['no_specific_constraints' => true],
        };
    }
}
