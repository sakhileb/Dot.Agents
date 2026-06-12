<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * SeoAnalyserSkill — content-oriented SEO analysis (Layer 5 — Platform Intelligence)
 *
 * Handles the three analysis-focused SEO actions extracted from SeoOptimizationSkill:
 *   analyze         – full on-page SEO audit of a content piece
 *   keyword_research – identify and rank target keywords for a topic
 *   content_score   – score existing content against SEO best practices
 *
 * Shares URL/intent/difficulty/priority computation with SeoAuditSkill via
 * the SeoScoringHelper base class.
 */
class SeoAnalyserSkill extends SeoScoringHelper
{
    /** Ideal title length range in characters. */
    private const TITLE_MIN = 50;

    private const TITLE_MAX = 60;

    /** Ideal meta description length range in characters. */
    private const META_MIN = 150;

    private const META_MAX = 160;

    /** Keyword density range (%) — outside this range triggers a finding. */
    private const KEYWORD_DENSITY_MIN = 0.5;

    private const KEYWORD_DENSITY_MAX = 3.0;

    public function key(): string
    {
        return 'seo-analyser';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Input keys:
     *   action          – analyze | keyword_research | content_score
     *   title           – page/post title
     *   meta_description – meta description string
     *   content         – body content string
     *   url             – page URL
     *   primary_keyword – target keyword for analysis
     *   topic           – topic string (for keyword_research)
     *   keywords        – array of candidate keywords (for keyword_research)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'analyze';

        return match ($action) {
            'analyze' => $this->analyzePage($input),
            'keyword_research' => $this->keywordResearch($input),
            'content_score' => $this->contentScore($input),
            default => SkillResult::failed("Unknown seo-analyser action: [{$action}]"),
        };
    }

    private function analyzePage(array $input): SkillResult
    {
        $title = $input['title'] ?? '';
        $meta = $input['meta_description'] ?? '';
        $content = $input['content'] ?? '';
        $keyword = strtolower($input['primary_keyword'] ?? '');

        if (empty($title) && empty($content)) {
            return SkillResult::failed('At least title or content is required for SEO analysis');
        }

        $checks = [];
        $findings = [];
        $recommendations = [];
        $totalScore = 0;
        $maxScore = 0;

        // ── Title tag ──────────────────────────────────
        $titleLen = strlen($title);
        $titleScore = 0;
        $maxScore += 20;
        if ($titleLen >= self::TITLE_MIN && $titleLen <= self::TITLE_MAX) {
            $titleScore = 20;
        } elseif ($titleLen > 0) {
            $titleScore = 10;
            $recommendations[] = "Title is {$titleLen} chars — target ".self::TITLE_MIN.'–'.self::TITLE_MAX.' chars';
        } else {
            $findings[] = 'Missing title tag';
            $recommendations[] = 'Add a descriptive title tag ('.self::TITLE_MIN.'–'.self::TITLE_MAX.' chars)';
        }
        if ($keyword && $title && stripos($title, $keyword) !== false) {
            $titleScore = min(20, $titleScore + 5);
        } elseif ($keyword && $title) {
            $findings[] = "Primary keyword '{$keyword}' not found in title";
        }
        $checks['title'] = ['score' => $titleScore, 'max' => 20, 'length' => $titleLen];
        $totalScore += $titleScore;

        // ── Meta description ────────────────────────────
        $metaLen = strlen($meta);
        $metaScore = 0;
        $maxScore += 15;
        if ($metaLen >= self::META_MIN && $metaLen <= self::META_MAX) {
            $metaScore = 15;
        } elseif ($metaLen > 0) {
            $metaScore = 7;
            $recommendations[] = "Meta description is {$metaLen} chars — target ".self::META_MIN.'–'.self::META_MAX.' chars';
        } else {
            $findings[] = 'Missing meta description';
            $recommendations[] = 'Add a compelling meta description ('.self::META_MIN.'–'.self::META_MAX.' chars)';
        }
        $checks['meta_description'] = ['score' => $metaScore, 'max' => 15, 'length' => $metaLen];
        $totalScore += $metaScore;

        // ── Content keyword density ─────────────────────
        $contentScore = 0;
        $maxScore += 25;
        $wordCount = str_word_count($content);
        $density = 0.0;

        if ($wordCount > 0 && $keyword) {
            $kwOccurrences = substr_count(strtolower($content), $keyword);
            $density = round($kwOccurrences / $wordCount * 100, 2);

            if ($density >= self::KEYWORD_DENSITY_MIN && $density <= self::KEYWORD_DENSITY_MAX) {
                $contentScore = 25;
            } elseif ($density > 0) {
                $contentScore = 12;
                if ($density < self::KEYWORD_DENSITY_MIN) {
                    $recommendations[] = "Keyword density {$density}% is low — aim for ".self::KEYWORD_DENSITY_MIN.'–'.self::KEYWORD_DENSITY_MAX.'%';
                } else {
                    $findings[] = "Keyword density {$density}% may look like keyword stuffing";
                    $recommendations[] = 'Reduce keyword frequency and use semantic synonyms';
                }
            }
        } elseif (! $keyword) {
            $contentScore = 15;
        }

        $checks['content'] = ['score' => $contentScore, 'max' => 25, 'word_count' => $wordCount, 'keyword_density_pct' => $density];
        $totalScore += $contentScore;

        // ── Content length ──────────────────────────────
        $lengthScore = 0;
        $maxScore += 20;
        if ($wordCount >= 1500) {
            $lengthScore = 20;
        } elseif ($wordCount >= 800) {
            $lengthScore = 14;
        } elseif ($wordCount >= 300) {
            $lengthScore = 8;
            $recommendations[] = 'Content is short — aim for 800+ words for competitive topics';
        } else {
            $findings[] = "Content too short ({$wordCount} words) for meaningful SEO";
        }
        $checks['content_length'] = ['score' => $lengthScore, 'max' => 20, 'word_count' => $wordCount];
        $totalScore += $lengthScore;

        // ── URL structure ───────────────────────────────
        $url = $input['url'] ?? '';
        $urlScore = 0;
        $maxScore += 10;
        if ($url) {
            $urlScore = $this->scoreUrl($url, $keyword, $findings, $recommendations);
        } else {
            $urlScore = 5;
        }
        $checks['url'] = ['score' => $urlScore, 'max' => 10, 'url' => $url];
        $totalScore += $urlScore;

        $finalScore = $maxScore > 0 ? round($totalScore / $maxScore * 100, 1) : 0;

        return SkillResult::completed(
            [
                'seo_score' => $finalScore,
                'grade' => $this->grade($finalScore),
                'checks' => $checks,
                'primary_keyword' => $keyword ?: null,
                'word_count' => $wordCount,
            ],
            90.0,
            $findings,
            $recommendations
        );
    }

    private function keywordResearch(array $input): SkillResult
    {
        $topic = $input['topic'] ?? '';
        $seed = $input['keywords'] ?? [];

        if (empty($topic) && empty($seed)) {
            return SkillResult::failed('A topic or seed keywords are required for keyword research');
        }

        $topicWords = array_filter(explode(' ', strtolower($topic)));
        $candidates = array_unique(array_merge($seed, $topicWords));

        $keywords = [];
        foreach ($candidates as $kw) {
            if (strlen($kw) < 3) {
                continue;
            }
            $keywords[] = [
                'keyword' => $kw,
                'type' => count(explode(' ', $kw)) > 2 ? 'long_tail' : 'head',
                'intent' => $this->inferSearchIntent($kw),
                'difficulty' => $this->estimateDifficulty($kw),
                'priority' => $this->priorityScore($kw, $topicWords),
            ];
        }

        usort($keywords, fn ($a, $b) => $b['priority'] <=> $a['priority']);

        return SkillResult::completed(
            [
                'topic' => $topic,
                'keyword_count' => count($keywords),
                'keywords' => array_slice($keywords, 0, 20),
                'primary_recommendation' => $keywords[0]['keyword'] ?? $topic,
                'long_tail_count' => count(array_filter($keywords, fn ($k) => $k['type'] === 'long_tail')),
            ],
            75.0,
            [],
            ['Validate keyword volumes using Google Search Console or a third-party SEO tool before finalising your strategy']
        );
    }

    private function contentScore(array $input): SkillResult
    {
        $content = $input['content'] ?? '';
        $keyword = strtolower($input['primary_keyword'] ?? '');

        if (empty($content)) {
            return SkillResult::failed('Content string is required for content_score');
        }

        $wordCount = str_word_count($content);
        $sentences = max(1, preg_match_all('/[.!?]+/', $content, $m));
        $avgWordsPerSentence = round($wordCount / $sentences, 1);
        $paragraphCount = max(1, substr_count($content, "\n\n") + 1);
        $avgWordsPerParagraph = round($wordCount / $paragraphCount, 1);

        $readabilityScore = $this->clamp(100 - ($avgWordsPerSentence - 15) * 2);

        $density = $keyword && $wordCount > 0
            ? round(substr_count(strtolower($content), $keyword) / $wordCount * 100, 2)
            : null;

        $overallScore = round(
            ($readabilityScore * 0.3) +
            ($this->clamp(min($wordCount / 10, 100)) * 0.4) +
            ($density !== null ? $this->clamp($density >= 0.5 && $density <= 3.0 ? 100 : 50) * 0.3 : 70 * 0.3),
            1
        );

        return SkillResult::completed(
            [
                'content_score' => $overallScore,
                'grade' => $this->grade($overallScore),
                'word_count' => $wordCount,
                'sentence_count' => $sentences,
                'avg_words_per_sentence' => $avgWordsPerSentence,
                'avg_words_per_paragraph' => $avgWordsPerParagraph,
                'readability_score' => round($readabilityScore, 1),
                'keyword_density_pct' => $density,
            ],
            88.0
        );
    }
}
