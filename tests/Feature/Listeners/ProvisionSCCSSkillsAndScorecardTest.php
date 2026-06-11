<?php

namespace Tests\Feature\Listeners;

use App\Events\AgentDeployed;
use App\Listeners\ProvisionSCCSSkillsAndScorecard;
use App\Models\Agent;
use App\Models\AgentDeployment;
use App\Models\AgentSkill;
use App\Models\AgentSkillAssignment;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProvisionSCCSSkillsAndScorecardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_non_sccs_agent_does_not_provision_skills(): void
    {
        $org = Organization::factory()->create();
        $agent = Agent::factory()->create([
            'slug' => 'finance-analyst-agent',
        ]);
        $deployment = AgentDeployment::factory()->create([
            'agent_id' => $agent->id,
            'organization_id' => $org->id,
        ]);

        // Create some SCCS skills
        AgentSkill::factory()->create([
            'key' => 'sccs.analyze-sentiment',
            'is_active' => true,
        ]);

        $listener = app(ProvisionSCCSSkillsAndScorecard::class);
        $listener->handle(new AgentDeployed($deployment));

        $this->assertEquals(0, AgentSkillAssignment::where('agent_deployment_id', $deployment->id)->count());
    }

    public function test_sccs_agent_gets_all_active_sccs_skills_assigned(): void
    {
        $org = Organization::factory()->create();
        $agent = Agent::factory()->create([
            'slug' => 'social-media-manager-agent',
        ]);
        $deployment = AgentDeployment::factory()->create([
            'agent_id' => $agent->id,
            'organization_id' => $org->id,
        ]);

        // Create 3 active SCCS skills
        AgentSkill::factory()->create(['key' => 'sccs.schedule-social-post', 'is_active' => true]);
        AgentSkill::factory()->create(['key' => 'sccs.analyze-sentiment', 'is_active' => true]);
        AgentSkill::factory()->create(['key' => 'sccs.capture-lead', 'is_active' => true]);
        // One inactive — should NOT be assigned
        AgentSkill::factory()->create(['key' => 'sccs.connect-social-account', 'is_active' => false]);

        $listener = app(ProvisionSCCSSkillsAndScorecard::class);
        $listener->handle(new AgentDeployed($deployment));

        $assigned = AgentSkillAssignment::where('agent_deployment_id', $deployment->id)
            ->where('is_enabled', true)
            ->count();

        $this->assertEquals(3, $assigned);
    }

    public function test_all_sccs_agent_slugs_trigger_provisioning(): void
    {
        $sccsAgentSlugs = [
            'social-media-manager-agent',
            'lead-generation-social-agent',
            'social-customer-support-agent',
            'sales-conversion-agent',
            'brand-reputation-monitor-agent',
        ];

        $skill = AgentSkill::factory()->create(['key' => 'sccs.analyze-sentiment', 'is_active' => true]);

        foreach ($sccsAgentSlugs as $slug) {
            $org = Organization::factory()->create();
            $agent = Agent::factory()->create(['slug' => $slug]);
            $deployment = AgentDeployment::factory()->create([
                'agent_id' => $agent->id,
                'organization_id' => $org->id,
            ]);

            $listener = app(ProvisionSCCSSkillsAndScorecard::class);
            $listener->handle(new AgentDeployed($deployment));

            $this->assertEquals(
                1,
                AgentSkillAssignment::where('agent_deployment_id', $deployment->id)->count(),
                "Slug '{$slug}' should have triggered SCCS skill provisioning"
            );
        }
    }

    public function test_skill_assignment_is_idempotent(): void
    {
        $org = Organization::factory()->create();
        $agent = Agent::factory()->create([
            'slug' => 'social-customer-support-agent',
        ]);
        $deployment = AgentDeployment::factory()->create([
            'agent_id' => $agent->id,
            'organization_id' => $org->id,
        ]);

        AgentSkill::factory()->create(['key' => 'sccs.respond-to-social-message', 'is_active' => true]);

        $listener = app(ProvisionSCCSSkillsAndScorecard::class);
        $event = new AgentDeployed($deployment);

        // Run listener twice — should not create duplicate assignments
        $listener->handle($event);
        $listener->handle($event);

        $this->assertEquals(1, AgentSkillAssignment::where('agent_deployment_id', $deployment->id)->count());
    }

    public function test_listener_handles_missing_agent_gracefully(): void
    {
        // Use an agent with a non-SCCS slug (effectively "no SCCS provisioning should happen")
        $agent = Agent::factory()->create(['slug' => 'unknown-generic-agent']);
        $deployment = AgentDeployment::factory()->create(['agent_id' => $agent->id]);

        $listener = app(ProvisionSCCSSkillsAndScorecard::class);

        // Should not throw, and no skills should be assigned (non-SCCS slug)
        $listener->handle(new AgentDeployed($deployment));

        $this->assertEquals(0, AgentSkillAssignment::where('agent_deployment_id', $deployment->id)->count());
    }
}
