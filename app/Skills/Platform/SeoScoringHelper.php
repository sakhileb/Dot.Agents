<?php

namespace App\Skills\Platform;

use App\Skills\BaseSkill;

/**
 * SeoScoringHelper — shared SEO computation methods.
 *
 * Extracted from SeoOptimizationSkill to eliminate duplication between
 * SeoAnalyserSkill and SeoAuditSkill. Extend this class (alongside BaseSkill)
 * to gain access to URL scoring, intent inference, difficulty estimation,
 * and priority scoring without re-implementing the logic.
 *
 * All methods here are pure computation — no DB, no I/O.
 */
abstract class SeoScoringHelper extends BaseSkill
{
    /**
     * Score a URL for SEO hygiene.
     *
     * @param  array<string>  $findings  Mutable findings array (appended to in place)
     * @param  array<string>  $recommendations  Mutable recommendations array (appended to in place)
     */
    protected function scoreUrl(string $url, string $keyword, array &$findings, array &$recommendations): int
    {
        $score = 0;

        if (str_starts_with($url, 'https://')) {
            $score += 4;
        } else {
            $findings[] = 'URL uses HTTP — migrate to HTTPS for ranking benefit';
        }

        $slug = parse_url($url, PHP_URL_PATH) ?? '';
        if (strlen($slug) < 80) {
            $score += 3;
        } else {
            $recommendations[] = 'URL slug is long — keep it under 80 characters';
        }

        if ($keyword && stripos($slug, str_replace(' ', '-', $keyword)) !== false) {
            $score += 3;
        } elseif ($keyword) {
            $recommendations[] = "Include keyword '{$keyword}' in the URL slug";
        }

        return $score;
    }

    /**
     * Classify a keyword's likely search intent.
     *
     * @return string informational | commercial | transactional | navigational
     */
    protected function inferSearchIntent(string $keyword): string
    {
        $informational = ['how', 'what', 'why', 'when', 'guide', 'tutorial', 'learn', 'explain'];
        $commercial = ['best', 'top', 'review', 'compare', 'vs', 'alternative'];
        $transactional = ['buy', 'price', 'cheap', 'order', 'purchase', 'discount', 'deal'];

        if ($this->countKeywords($keyword, $transactional) > 0) {
            return 'transactional';
        }
        if ($this->countKeywords($keyword, $commercial) > 0) {
            return 'commercial';
        }
        if ($this->countKeywords($keyword, $informational) > 0) {
            return 'informational';
        }

        return 'navigational';
    }

    /**
     * Estimate keyword difficulty based on word count (proxy heuristic).
     *
     * @return string low | medium | high
     */
    protected function estimateDifficulty(string $keyword): string
    {
        $wordCount = str_word_count($keyword);
        if ($wordCount >= 4) {
            return 'low';
        }
        if ($wordCount >= 2) {
            return 'medium';
        }

        return 'high';
    }

    /**
     * Compute a relative priority score for a keyword given the topic words.
     *
     * @param  array<string>  $topicWords  Lowercase topic tokens
     */
    protected function priorityScore(string $keyword, array $topicWords): float
    {
        $overlap = count(array_intersect(explode(' ', strtolower($keyword)), $topicWords));
        $lengthBonus = str_word_count($keyword) >= 3 ? 15 : 0;

        return $this->clamp($overlap * 20 + $lengthBonus + rand(0, 20));
    }
}
