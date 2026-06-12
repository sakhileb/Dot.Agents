<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\SeoOptimizationSkill;
use Tests\TestCase;

class SeoOptimizationSkillTest extends TestCase
{
    private SeoOptimizationSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new SeoOptimizationSkill;
    }

    // ── Contract ──────────────────────────────────────────

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('seo-optimization', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    // ── analyze ───────────────────────────────────────────

    public function test_analyze_returns_seo_score(): void
    {
        $result = $this->skill->execute([
            'action' => 'analyze',
            'title' => 'Best Laravel Development Practices for Enterprise Apps',
            'meta_description' => 'Discover the top Laravel best practices used by enterprise teams to build scalable, maintainable applications in 2025 and beyond.',
            'content' => str_repeat('Laravel is a powerful PHP framework for building web applications. ', 50),
            'primary_keyword' => 'Laravel',
            'url' => 'https://example.com/laravel-best-practices',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('seo_score', $result->output);
        $this->assertArrayHasKey('grade', $result->output);
        $this->assertArrayHasKey('checks', $result->output);
        $this->assertGreaterThan(0, $result->output['seo_score']);
    }

    public function test_analyze_flags_missing_title(): void
    {
        $result = $this->skill->execute([
            'action' => 'analyze',
            'title' => '',
            'content' => 'Some content here.',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertContains('Missing title tag', $result->findings);
    }

    public function test_analyze_fails_with_no_title_or_content(): void
    {
        $result = $this->skill->execute([
            'action' => 'analyze',
            'title' => '',
            'content' => '',
        ]);

        $this->assertSame('failed', $result->status);
    }

    // ── keyword_research ──────────────────────────────────

    public function test_keyword_research_returns_keyword_list(): void
    {
        $result = $this->skill->execute([
            'action' => 'keyword_research',
            'topic' => 'artificial intelligence enterprise software',
            'keywords' => ['AI agents', 'enterprise AI', 'AI workforce'],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('keywords', $result->output);
        $this->assertArrayHasKey('keyword_count', $result->output);
        $this->assertGreaterThan(0, $result->output['keyword_count']);
    }

    public function test_keyword_research_fails_without_topic_or_seeds(): void
    {
        $result = $this->skill->execute(['action' => 'keyword_research']);

        $this->assertSame('failed', $result->status);
    }

    // ── audit ─────────────────────────────────────────────

    public function test_technical_audit_returns_checklist(): void
    {
        $result = $this->skill->execute([
            'action' => 'audit',
            'title' => 'Example Page',
            'meta_description' => 'An example meta description for the page.',
            'url' => 'https://example.com/page',
            'mobile_friendly' => true,
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('audit_score', $result->output);
        $this->assertArrayHasKey('checklist', $result->output);
        $this->assertArrayHasKey('checks_passed', $result->output);
        // HTTPS should pass
        $this->assertTrue($result->output['checklist']['https']);
    }

    // ── content_score ─────────────────────────────────────

    public function test_content_score_scores_content(): void
    {
        $content = str_repeat('Enterprise AI agents transform business operations. ', 40);

        $result = $this->skill->execute([
            'action' => 'content_score',
            'content' => $content,
            'primary_keyword' => 'AI agents',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('content_score', $result->output);
        $this->assertArrayHasKey('readability_score', $result->output);
        $this->assertArrayHasKey('word_count', $result->output);
    }

    public function test_content_score_fails_with_empty_content(): void
    {
        $result = $this->skill->execute([
            'action' => 'content_score',
            'content' => '',
        ]);

        $this->assertSame('failed', $result->status);
    }

    // ── fallback ──────────────────────────────────────────

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'unknown_seo_action']);

        $this->assertSame('failed', $result->status);
        $this->assertStringContainsString('unknown_seo_action', $result->output['error']);
    }
}
