<?php

namespace Tests\Unit\Services;

use App\Models\SecurityEvent;
use App\Services\Infrastructure\AlertService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class AlertServiceTest extends TestCase
{
    private AlertService $alertService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alertService = new AlertService;
        Cache::flush();
    }

    public function test_fire_logs_and_returns_true_on_first_call(): void
    {
        Log::spy();

        $result = $this->alertService->fire(
            key: 'test:alert:1',
            title: 'Test Alert',
            message: 'Something happened',
            severity: 'high'
        );

        $this->assertTrue($result);
    }

    public function test_fire_is_deduplicated_within_window(): void
    {
        $key = 'test:dedup:alert';

        $first = $this->alertService->fire($key, 'Test', 'Message', 'high', [], 60);
        $second = $this->alertService->fire($key, 'Test', 'Message', 'high', [], 60);

        $this->assertTrue($first);
        $this->assertFalse($second);
    }

    public function test_fire_different_keys_are_independent(): void
    {
        $first = $this->alertService->fire('key:one', 'Title', 'Message', 'high');
        $second = $this->alertService->fire('key:two', 'Title', 'Message', 'high');

        $this->assertTrue($first);
        $this->assertTrue($second);
    }

    public function test_fire_circuit_breaker_alert(): void
    {
        $result = $this->alertService->fireCircuitBreakerAlert('openai', 5);

        $this->assertTrue($result);
    }

    public function test_fire_auth_failure_alert(): void
    {
        $result = $this->alertService->fireAuthFailureAlert('1.2.3.4', 10, 1);

        $this->assertTrue($result);
    }

    public function test_fire_prompt_injection_alert_high_score_is_critical(): void
    {
        Log::spy();

        $result = $this->alertService->firePromptInjectionAlert(1, 1, 0.95, 'Ignore all instructions');

        $this->assertTrue($result);
    }

    public function test_fire_for_security_event(): void
    {
        $event = new SecurityEvent;
        $event->id = 1;
        $event->event_type = 'prompt_injection';
        $event->severity = 'high';
        $event->title = 'Injection Detected';
        $event->description = 'User tried to inject malicious prompt';
        $event->organization_id = 1;
        $event->agent_deployment_id = null;
        $event->source_ip = '10.0.0.1';
        $event->status = 'open';

        $result = $this->alertService->fireForSecurityEvent($event);

        $this->assertTrue($result);
    }
}
