<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * SeoAuditSkill — technical SEO page audit (Layer 5 — Platform Intelligence)
 *
 * Handles the technical-audit action extracted from SeoOptimizationSkill:
 *   audit – technical SEO checklist for a page/site (HTTPS, canonical, meta, etc.)
 *
 * Shares URL/intent/difficulty/priority computation with SeoAnalyserSkill via
 * the SeoScoringHelper base class.
 */
class SeoAuditSkill extends SeoScoringHelper
{
    public function key(): string
    {
        return 'seo-audit';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Input keys:
     *   action              – audit (default and only action)
     *   url                 – page URL (used for HTTPS check)
     *   title               – page title (checked for presence)
     *   meta_description    – meta description (checked for presence)
     *   content             – body content (checked for heading hierarchy proxy)
     *   has_structured_data – bool, whether JSON-LD / schema markup is present
     *   has_canonical       – bool, whether a canonical URL tag exists
     *   mobile_friendly     – bool, whether page passes mobile-friendly test
     *   page_speed_ok       – bool, whether Core Web Vitals pass
     *   images_have_alt     – bool, whether all images carry alt attributes
     *   has_internal_links  – bool, whether page contains internal links
     *   heading_hierarchy_ok – bool, explicit H1→H2→H3 nesting check
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'audit';

        return match ($action) {
            'audit' => $this->technicalAudit($input),
            default => SkillResult::failed("Unknown seo-audit action: [{$action}]"),
        };
    }

    private function technicalAudit(array $input): SkillResult
    {
        $url = $input['url'] ?? '';
        $meta = $input['meta_description'] ?? '';
        $title = $input['title'] ?? '';
        $content = $input['content'] ?? '';

        $checklist = [
            'title_tag' => ! empty($title),
            'meta_description' => ! empty($meta),
            'structured_data' => (bool) ($input['has_structured_data'] ?? false),
            'canonical_url' => (bool) ($input['has_canonical'] ?? ! empty($url)),
            'mobile_friendly' => (bool) ($input['mobile_friendly'] ?? true),
            'https' => $url ? str_starts_with($url, 'https://') : null,
            'page_speed_ok' => isset($input['page_speed_ok']) ? (bool) $input['page_speed_ok'] : null,
            'image_alt_tags' => isset($input['images_have_alt']) ? (bool) $input['images_have_alt'] : null,
            'internal_links' => isset($input['has_internal_links']) ? (bool) $input['has_internal_links'] : null,
            'heading_hierarchy' => (bool) ($input['heading_hierarchy_ok'] ?? ! empty($content)),
        ];

        $passed = count(array_filter($checklist, fn ($v) => $v === true));
        $total = count(array_filter($checklist, fn ($v) => $v !== null));
        $auditScore = $total > 0 ? round($passed / $total * 100, 1) : 0;

        $failures = array_keys(array_filter($checklist, fn ($v) => $v === false));

        return SkillResult::completed(
            [
                'audit_score' => $auditScore,
                'checks_passed' => $passed,
                'checks_total' => $total,
                'checklist' => $checklist,
                'failed_checks' => $failures,
                'grade' => $this->grade($auditScore),
            ],
            92.0,
            array_map(fn ($f) => "Technical SEO issue: {$f} check failed", $failures),
            array_map(fn ($f) => "Fix: address {$f} to improve crawlability and ranking signals", $failures)
        );
    }
}
