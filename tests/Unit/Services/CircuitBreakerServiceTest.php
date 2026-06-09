<?php

namespace Tests\Unit\Services;

use App\Services\Resilience\CircuitBreakerService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CircuitBreakerServiceTest extends TestCase
{
    private CircuitBreakerService $breaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->breaker = new CircuitBreakerService(
            failureThreshold: 3,
            successThreshold: 2,
            timeoutSeconds: 60
        );
        // Clear all breaker state
        Cache::flush();
    }

    public function test_closed_circuit_passes_calls_through(): void
    {
        $result = $this->breaker->call('test_service', fn () => 'ok');

        $this->assertSame('ok', $result);
    }

    public function test_circuit_opens_after_failure_threshold(): void
    {
        $failingCallable = fn () => throw new \RuntimeException('API down');

        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', $failingCallable);
            } catch (\RuntimeException) {
                // expected
            }
        }

        $status = $this->breaker->status('test_service');
        $this->assertSame('open', $status['state']);
    }

    public function test_open_circuit_throws_when_no_fallback(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/circuit open/');

        // Open the circuit manually by forcing failures
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        // Now a fresh call should fail-fast without executing the callable
        $this->breaker->call('test_service', fn () => 'should not run');
    }

    public function test_open_circuit_returns_fallback(): void
    {
        // Open the circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        $result = $this->breaker->call(
            'test_service',
            fn () => 'should not run',
            fn () => 'fallback_value'
        );

        $this->assertSame('fallback_value', $result);
    }

    public function test_circuit_reset_restores_closed_state(): void
    {
        // Open the circuit
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('test_service', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        $this->assertSame('open', $this->breaker->status('test_service')['state']);

        $this->breaker->reset('test_service');

        $this->assertSame('closed', $this->breaker->status('test_service')['state']);
    }

    public function test_status_returns_expected_structure(): void
    {
        $status = $this->breaker->status('test_service');

        $this->assertArrayHasKey('service', $status);
        $this->assertArrayHasKey('state', $status);
        $this->assertArrayHasKey('failures', $status);
        $this->assertArrayHasKey('successes', $status);
        $this->assertArrayHasKey('threshold', $status);
        $this->assertSame('closed', $status['state']);
        $this->assertSame(0, $status['failures']);
    }

    public function test_different_services_have_independent_circuits(): void
    {
        // Open circuit for service_a
        for ($i = 0; $i < 3; $i++) {
            try {
                $this->breaker->call('service_a', fn () => throw new \RuntimeException('down'));
            } catch (\RuntimeException) {
            }
        }

        // service_b should still be closed
        $result = $this->breaker->call('service_b', fn () => 'b_ok');
        $this->assertSame('b_ok', $result);
        $this->assertSame('closed', $this->breaker->status('service_b')['state']);
        $this->assertSame('open', $this->breaker->status('service_a')['state']);
    }
}
