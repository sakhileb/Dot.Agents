<?php

namespace Tests\Unit\Services;

use App\Models\AgentDeployment;
use App\Services\Governance\Audit\DWCAPhaseRunner;
use App\Services\Governance\Audit\Phase01AgentDiscovery;
use App\Services\Governance\Audit\Phase02SkillAudit;
use App\Services\Governance\Audit\Phase04AgentQuality;
use App\Services\Governance\Audit\Phase06Governance;
use App\Services\Governance\Audit\Phase07DelusionRisk;
use App\Services\Governance\Audit\Phase08Memory;
use App\Services\Governance\Audit\Phase12Performance;
use App\Services\Governance\Audit\Phase13Scorecard;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Unit tests for DWCAPhaseRunner.
 *
 * Tests verify that the runner correctly delegates to phase strategies and
 * assembles the composite scores array from phase results.
 */
class DWCAPhaseRunnerTest extends TestCase
{
    private function makePhaseStub(string $phase, int $score): mixed
    {
        $mock = Mockery::mock($phase);
        $mock->shouldReceive('execute')
            ->andReturn(['phase' => $phase, 'score' => $score, 'failures' => []]);

        return $mock;
    }

    private function makeRunner(int $score = 80): DWCAPhaseRunner
    {
        return new DWCAPhaseRunner(
            phase01: $this->makePhaseStub(Phase01AgentDiscovery::class, $score),
            phase02: $this->makePhaseStub(Phase02SkillAudit::class, $score),
            phase04: $this->makePhaseStub(Phase04AgentQuality::class, $score),
            phase06: $this->makePhaseStub(Phase06Governance::class, $score),
            phase07: $this->makePhaseStub(Phase07DelusionRisk::class, $score),
            phase08: $this->makePhaseStub(Phase08Memory::class, $score),
            phase12: $this->makePhaseStub(Phase12Performance::class, $score),
            phase13: $this->makePhaseStub(Phase13Scorecard::class, $score),
        );
    }

    #[Test]
    public function run_returns_all_eight_phase_keys(): void
    {
        $deployment = Mockery::mock(AgentDeployment::class);
        $runner = $this->makeRunner();

        $result = $runner->run($deployment);

        $this->assertArrayHasKey('phase1_discovery', $result);
        $this->assertArrayHasKey('phase2_skill_audit', $result);
        $this->assertArrayHasKey('phase4_quality', $result);
        $this->assertArrayHasKey('phase6_governance', $result);
        $this->assertArrayHasKey('phase7_delusion', $result);
        $this->assertArrayHasKey('phase8_memory', $result);
        $this->assertArrayHasKey('phase12_performance', $result);
        $this->assertArrayHasKey('phase13_scorecard', $result);
    }

    #[Test]
    public function scores_returns_eight_numeric_values(): void
    {
        $deployment = Mockery::mock(AgentDeployment::class);
        $runner = $this->makeRunner(75);

        $phases = $runner->run($deployment);
        $scores = $runner->scores($phases);

        $this->assertCount(8, $scores);
        foreach ($scores as $score) {
            $this->assertSame(75, $score);
        }
    }

    #[Test]
    public function scores_preserves_phase_order(): void
    {
        $deployment = Mockery::mock(AgentDeployment::class);

        // Assign distinct scores per phase to verify ordering
        $runner = new DWCAPhaseRunner(
            phase01: tap(Mockery::mock(Phase01AgentDiscovery::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p01', 'score' => 10, 'failures' => []])),
            phase02: tap(Mockery::mock(Phase02SkillAudit::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p02', 'score' => 20, 'failures' => []])),
            phase04: tap(Mockery::mock(Phase04AgentQuality::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p04', 'score' => 30, 'failures' => []])),
            phase06: tap(Mockery::mock(Phase06Governance::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p06', 'score' => 40, 'failures' => []])),
            phase07: tap(Mockery::mock(Phase07DelusionRisk::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p07', 'score' => 50, 'failures' => []])),
            phase08: tap(Mockery::mock(Phase08Memory::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p08', 'score' => 60, 'failures' => []])),
            phase12: tap(Mockery::mock(Phase12Performance::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p12', 'score' => 70, 'failures' => []])),
            phase13: tap(Mockery::mock(Phase13Scorecard::class), fn ($m) => $m->shouldReceive('execute')->andReturn(['phase' => 'p13', 'score' => 80, 'failures' => []])),
        );

        $phases = $runner->run($deployment);
        $scores = $runner->scores($phases);

        $this->assertSame([10, 20, 30, 40, 50, 60, 70, 80], $scores);
    }
}
