<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * ContentQualitySkill — quality validation and distribution planning (Layer 5 — Platform Intelligence)
 *
 * Handles the two quality-focused mass content actions extracted from MassContentGenerationSkill:
 *   validate_quality – score a batch for quality, consistency, and uniqueness
 *   distribute       – produce a per-channel distribution manifest
 *
 * Shares helper methods and constants with ContentBatchSkill via ContentGenerationHelper.
 */
class ContentQualitySkill extends ContentGenerationHelper
{
    public function key(): string
    {
        return 'content-quality';
    }

    public function layer(): string
    {
        return 'platform';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'validate_quality';

        return match ($action) {
            'validate_quality' => $this->validateQuality($input),
            'distribute' => $this->buildDistributionManifest($input),
            default => SkillResult::failed("Unknown content-quality action: [{$action}]"),
        };
    }

    private function validateQuality(array $input): SkillResult
    {
        $items = $input['items'] ?? [];

        if (empty($items)) {
            return SkillResult::failed('An array of content items is required for quality validation');
        }

        $contentStrings = array_map(fn ($item) => is_array($item) ? ($item['content'] ?? '') : (string) $item, $items);

        $fingerprints = array_map(fn ($c) => md5(strtolower(preg_replace('/\s+/', ' ', $c))), $contentStrings);
        $uniqueCount = count(array_unique($fingerprints));
        $uniquenessRatio = count($fingerprints) > 0 ? round($uniqueCount / count($fingerprints), 3) : 1.0;

        $wordCounts = array_map('str_word_count', $contentStrings);
        $avgWords = count($wordCounts) > 0 ? array_sum($wordCounts) / count($wordCounts) : 0;
        $minWords = min($wordCounts);
        $maxWords = max($wordCounts);
        $lengthVariancePct = $avgWords > 0 ? round(($maxWords - $minWords) / $avgWords * 100, 1) : 0;

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
                'items' => array_slice($channelItems, 0, 5),
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
}
