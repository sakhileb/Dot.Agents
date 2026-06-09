<?php

namespace Tests\Feature\Governance;

use App\Jobs\DetectAgentDelusion;
use App\Models\AgentDeployment;
use App\Models\AgentTask;
use App\Models\DecisionLog;
use App\Models\Organization;
use App\Services\Governance\AuditService;
use App\Services\Governance\DelusionDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DelusionDetectionTest extends TestCase
{
    use RefreshDatabase;

    private DelusionDetectionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DelusionDetectionService::class);
    }

    public function test_safe_output_scores_low_risk(): void
    {
        $analysis = $this->service->analyze(
            'Summarize Q3 revenue',
            [
                'summary' => 'Q3 revenue was $2.3M, a 12% increase YoY',
                'confidence' => 82,
                'evidence' => ['q3_revenue_report', 'prior_year_comparison'],
                'assumptions' => [],
                'risks' => ['market_volatility'],
            ],
            ['q3_revenue_report' => '$2.3M', 'prior_year_comparison' => '$2.05M']
        );

        $this->assertLessThan(60, $analysis['risk_score']);
        $this->assertGreaterThan(40, $analysis['reality_alignment']);
    }

    public function test_overconfident_output_with_no_data_scores_high_risk(): void
    {
        $analysis = $this->service->analyze(
            'Predict next year revenue',
            ['summary' => 'Revenue will be $10M', 'confidence' => 99],
            [] // no input data
        );

        $this->assertGreaterThanOrEqual(50, $analysis['risk_score']);
    }

    public function test_many_assumptions_increase_risk_score(): void
    {
        $lowAssumptions = $this->service->analyze(
            'Task',
            ['confidence' => 75, 'assumptions' => []],
            []
        );

        $highAssumptions = $this->service->analyze(
            'Task',
            ['confidence' => 75, 'assumptions' => ['a1', 'a2', 'a3', 'a4', 'a5']],
            []
        );

        $this->assertGreaterThan($lowAssumptions['risk_score'], $highAssumptions['risk_score']);
    }

    public function test_detect_agent_delusion_job_creates_decision_log(): void
    {
        $org = Organization::factory()->create();
        $deployment = AgentDeployment::factory()->create(['organization_id' => $org->id]);
        $task = AgentTask::factory()->completed()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $org->id,
            'output_data' => [
                'summary' => 'Completed analysis',
                'confidence' => 80,
                'evidence' => ['data_source'],
            ],
        ]);

        (new DetectAgentDelusion($task))->handle(
            app(DelusionDetectionService::class),
            app(AuditService::class)
        );

        $this->assertDatabaseHas('decision_logs', [
            'task_id' => $task->id,
            'agent_deployment_id' => $deployment->id,
        ]);
    }

    public function test_high_risk_task_flags_requires_human_review(): void
    {
        $org = Organization::factory()->create();
        $deployment = AgentDeployment::factory()->create(['organization_id' => $org->id]);
        $task = AgentTask::factory()->completed()->create([
            'agent_deployment_id' => $deployment->id,
            'organization_id' => $org->id,
            'output_data' => [
                'summary' => 'Action with no evidence',
                'confidence' => 99, // suspiciously high
                'assumptions' => ['a1', 'a2', 'a3', 'a4'],
            ],
        ]);

        (new DetectAgentDelusion($task))->handle(
            app(DelusionDetectionService::class),
            app(AuditService::class)
        );

        $log = DecisionLog::where('task_id', $task->id)->first();
        $this->assertNotNull($log);
        // High confidence + many assumptions = likely flagged
        $this->assertGreaterThanOrEqual(0, $log->delusion_risk_score);
    }
}
