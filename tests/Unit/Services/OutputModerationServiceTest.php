<?php

namespace Tests\Unit\Services;

use App\Services\AI\OutputModerationService;
use Tests\TestCase;

class OutputModerationServiceTest extends TestCase
{
    private OutputModerationService $moderator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->moderator = new OutputModerationService;
    }

    public function test_clean_content_passes(): void
    {
        $result = $this->moderator->scan('Hello! I can help you with your analytics dashboard.');

        $this->assertSame(OutputModerationService::PASS, $result['verdict']);
        $this->assertFalse($result['flagged']);
        $this->assertEmpty($result['flags']);
    }

    public function test_email_address_triggers_pii_flag(): void
    {
        $result = $this->moderator->scan('You can contact support at john.doe@example.com for help.');

        $this->assertArrayHasKey('pii', $result['flags']);
        $this->assertContains('email', $result['flags']['pii']);
        $this->assertSame(OutputModerationService::WARN, $result['verdict']);
        $this->assertTrue($result['flagged']);
    }

    public function test_openai_key_triggers_secret_block(): void
    {
        $result = $this->moderator->scan('Here is the API key: sk-proj-abcdefghijklmnopqrstuvwxyz1234567890');

        $this->assertArrayHasKey('secrets', $result['flags']);
        $this->assertSame(OutputModerationService::BLOCK, $result['verdict']);
    }

    public function test_prompt_leak_triggers_warn(): void
    {
        $result = $this->moderator->scan('My system prompt tells me to only answer about finance topics.');

        $this->assertArrayHasKey('prompt_leak', $result['flags']);
        $this->assertSame(OutputModerationService::WARN, $result['verdict']);
    }

    public function test_scan_and_redact_removes_email(): void
    {
        ['content' => $redacted] = $this->moderator->scanAndRedact(
            'Contact us at admin@example.com for support.'
        );

        $this->assertStringNotContainsString('admin@example.com', $redacted);
        $this->assertStringContainsString('[REDACTED:email]', $redacted);
    }

    public function test_scan_and_redact_removes_secrets(): void
    {
        ['content' => $redacted] = $this->moderator->scanAndRedact(
            'The API key is sk-proj-xyzABCDEFGHIJKLMNOPQRSTUV1234567890'
        );

        $this->assertStringNotContainsString('sk-proj', $redacted);
        $this->assertStringContainsString('[REDACTED:secret]', $redacted);
    }

    public function test_blocked_response_is_user_friendly(): void
    {
        $response = $this->moderator->blockedResponse();

        $this->assertStringContainsString('safety policy', $response);
    }

    public function test_verdict_escalation_block_wins_over_warn(): void
    {
        // Content with both PII (WARN) and secret (BLOCK) — BLOCK must win
        $result = $this->moderator->scan(
            'Send it to user@example.com and use key sk-proj-abcdefghijklmnopqrstuvwxyz12345678'
        );

        $this->assertSame(OutputModerationService::BLOCK, $result['verdict']);
        $this->assertArrayHasKey('pii', $result['flags']);
        $this->assertArrayHasKey('secrets', $result['flags']);
    }
}
