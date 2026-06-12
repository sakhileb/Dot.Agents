<?php

namespace Tests\Unit\Services;

use App\Services\Infrastructure\ObservabilityService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Unit tests for ObservabilityService.
 *
 * Covers: histogram statistics computation, percentile calculation,
 * and the observabilityScore() dimensions that are purely deterministic.
 */
class ObservabilityServiceTest extends TestCase
{
    private ObservabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = app(ObservabilityService::class);
    }

    // ── histogramStats ────────────────────────────────────────────────────────

    public function test_histogram_stats_returns_zero_struct_when_no_data(): void
    {
        $stats = $this->service->histogramStats('nonexistent.metric');

        $this->assertSame(0, $stats['count']);
        $this->assertSame(0, $stats['min']);
        $this->assertSame(0, $stats['max']);
        $this->assertSame(0, $stats['mean']);
        $this->assertSame(0, $stats['p50']);
        $this->assertSame(0, $stats['p95']);
        $this->assertSame(0, $stats['p99']);
    }

    public function test_histogram_stats_returns_required_keys(): void
    {
        $this->service->recordHistogram('test.latency', 100.0);
        $stats = $this->service->histogramStats('test.latency');

        foreach (['count', 'min', 'max', 'mean', 'p50', 'p95', 'p99'] as $key) {
            $this->assertArrayHasKey($key, $stats, "Missing histogram key: {$key}");
        }
    }

    public function test_histogram_stats_correct_min_max_mean(): void
    {
        foreach ([10.0, 20.0, 30.0, 40.0, 50.0] as $v) {
            $this->service->recordHistogram('latency', $v);
        }

        $stats = $this->service->histogramStats('latency');

        $this->assertSame(5, $stats['count']);
        $this->assertSame(10.0, $stats['min']);
        $this->assertSame(50.0, $stats['max']);
        $this->assertSame(30.0, $stats['mean']);
    }

    public function test_histogram_stats_p50_is_median(): void
    {
        foreach ([10.0, 20.0, 30.0, 40.0, 50.0] as $v) {
            $this->service->recordHistogram('p50_test', $v);
        }

        $stats = $this->service->histogramStats('p50_test');

        // Median of [10, 20, 30, 40, 50] = 30
        $this->assertSame(30.0, $stats['p50']);
    }

    public function test_record_histogram_accumulates_values(): void
    {
        $this->service->recordHistogram('accum', 5.0);
        $this->service->recordHistogram('accum', 10.0);
        $this->service->recordHistogram('accum', 15.0);

        $stats = $this->service->histogramStats('accum');

        $this->assertSame(3, $stats['count']);
    }

    // ── errorRate ─────────────────────────────────────────────────────────────

    public function test_error_rate_returns_zero_when_no_data(): void
    {
        $this->assertSame(0.0, $this->service->errorRate('new.operation'));
    }

    public function test_error_rate_is_zero_with_only_successes(): void
    {
        // Register 5 successes for the operation via endSpan
        $handle = $this->service->startSpan('my_op');
        $this->service->endSpan($handle, 'my_op', true);

        $this->assertSame(0.0, $this->service->errorRate('my_op'));
    }

    // ── observabilityScore dimensions ─────────────────────────────────────────

    public function test_observability_score_returns_required_keys(): void
    {
        $result = $this->service->observabilityScore();

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('details', $result);
        $this->assertArrayHasKey('sentry_configured', $result);
    }

    public function test_observability_score_is_between_0_and_100(): void
    {
        $result = $this->service->observabilityScore();

        $this->assertGreaterThanOrEqual(0, $result['score']);
        $this->assertLessThanOrEqual(100, $result['score']);
    }

    public function test_observability_score_includes_structured_logging_and_correlation(): void
    {
        // Structured logging (+20) and correlation IDs (+15) are always awarded
        $result = $this->service->observabilityScore();

        $this->assertGreaterThanOrEqual(35, $result['score'],
            'Score must be at least 35 (structured logging + correlation IDs always awarded).'
        );
    }

    public function test_observability_score_detail_keys_present(): void
    {
        $result = $this->service->observabilityScore();

        $this->assertArrayHasKey('sentry', $result['details']);
        $this->assertArrayHasKey('structured_logging', $result['details']);
        $this->assertArrayHasKey('correlation_ids', $result['details']);
        $this->assertArrayHasKey('error_rate', $result['details']);
        $this->assertArrayHasKey('latency_p95_ms', $result['details']);
    }

    public function test_observability_score_awards_25_for_zero_error_rate(): void
    {
        // No errors recorded → error rate = 0 → +25 pts
        $result = $this->service->observabilityScore();

        $this->assertSame(25, $result['details']['error_rate']['score'],
            'Zero error rate should award maximum 25 pts for this dimension.'
        );
    }
}
