<?php

namespace App\Skills\Platform;

use App\Skills\BaseSkill;

/**
 * ContentGenerationHelper — shared helpers for mass content generation.
 *
 * Extracted from MassContentGenerationSkill to eliminate duplication between
 * ContentBatchSkill and ContentQualitySkill. Contains template scaffolding,
 * channel adaptation, and format rules.
 */
abstract class ContentGenerationHelper extends BaseSkill
{
    /** Hard cap on batch size per execution to prevent runaway tasks. */
    protected const MAX_BATCH_SIZE = 500;

    /** Minimum uniqueness ratio to pass quality validation. */
    protected const MIN_UNIQUENESS_RATIO = 0.80;

    /** Build a scaffold template string from variable names and content type. */
    protected function scaffoldTemplate(string $contentType, array $variables): string
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

    /**
     * Adapt a batch of content items for a specific distribution channel.
     *
     * @param  array<int, array|string>  $batch
     * @return array<int, array>
     */
    protected function adaptForChannel(array $batch, string $channel): array
    {
        return array_map(function ($item) use ($channel) {
            $content = is_array($item) ? ($item['content'] ?? '') : (string) $item;

            return match ($channel) {
                'social' => ['content' => substr($content, 0, 280), 'channel' => 'social'],
                'email' => ['content' => $content, 'subject' => substr($content, 0, 60), 'channel' => 'email'],
                'blog' => ['content' => $content, 'channel' => 'blog'],
                default => ['content' => $content, 'channel' => $channel],
            };
        }, $batch);
    }

    /** Return channel-specific publishing format constraints. */
    protected function formatRules(string $channel): array
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
