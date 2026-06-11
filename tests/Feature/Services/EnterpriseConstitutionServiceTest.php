<?php

namespace Tests\Feature\Services;

use App\Models\Organization;
use App\Models\OrganizationDNA;
use App\Services\Governance\EnterpriseConstitutionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class EnterpriseConstitutionServiceTest extends TestCase
{
    use RefreshDatabase;

    private EnterpriseConstitutionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(EnterpriseConstitutionService::class);
    }

    public function test_get_dna_returns_null_when_not_configured(): void
    {
        $org = Organization::factory()->create();
        $result = $this->service->getDNA($org->id);

        $this->assertNull($result);
    }

    public function test_set_dna_creates_new_record(): void
    {
        $org = Organization::factory()->create();
        $dna = $this->service->setDNA($org->id, [
            'mission' => 'Drive innovation through AI.',
            'risk_appetite' => 'moderate',
            'values' => ['Innovation', 'Integrity'],
        ]);

        $this->assertInstanceOf(OrganizationDNA::class, $dna);
        $this->assertEquals('Drive innovation through AI.', $dna->mission);
        $this->assertEquals(['Innovation', 'Integrity'], $dna->values);
    }

    public function test_set_dna_updates_existing_record(): void
    {
        $org = Organization::factory()->create();
        $this->service->setDNA($org->id, ['mission' => 'Version 1']);
        $updated = $this->service->setDNA($org->id, ['mission' => 'Version 2']);

        $this->assertEquals('Version 2', $updated->mission);
        $this->assertEquals(1, OrganizationDNA::where('organization_id', $org->id)->count());
    }

    public function test_set_dna_clears_cache(): void
    {
        $org = Organization::factory()->create();
        $cacheKey = "org_dna_{$org->id}";
        Cache::put($cacheKey, 'stale', 3600);

        $this->service->setDNA($org->id, ['mission' => 'Fresh mission']);

        $this->assertNull(Cache::get($cacheKey));
    }

    public function test_build_constitutional_context_returns_null_when_no_dna(): void
    {
        $org = Organization::factory()->create();
        $context = $this->service->buildConstitutionalContext($org->id);

        $this->assertNull($context);
    }

    public function test_build_constitutional_context_includes_mission_and_principles(): void
    {
        $org = Organization::factory()->create();
        $this->service->setDNA($org->id, [
            'mission' => 'Empower customers through AI.',
            'risk_appetite' => 'conservative',
            'leadership_principles' => ['Customer First', 'Data Driven'],
            'compliance_requirements' => ['GDPR'],
        ]);

        $context = $this->service->buildConstitutionalContext($org->id);

        $this->assertStringContainsString('Empower customers through AI.', $context);
        $this->assertStringContainsString('Customer First', $context);
        $this->assertStringContainsString('conservative', $context);
        $this->assertStringContainsString('GDPR', $context);
    }

    public function test_validate_alignment_returns_aligned_when_no_dna(): void
    {
        $org = Organization::factory()->create();
        $result = $this->service->validateAlignment($org->id, 'send_email', []);

        $this->assertTrue($result['aligned']);
        $this->assertEmpty($result['violations']);
    }

    public function test_validate_alignment_detects_risk_appetite_violation(): void
    {
        $org = Organization::factory()->create();
        $this->service->setDNA($org->id, ['risk_appetite' => 'conservative']);

        $result = $this->service->validateAlignment($org->id, 'high_risk_action', [
            'risk_score' => 50,
        ]);

        $this->assertFalse($result['aligned']);
        $this->assertNotEmpty($result['violations']);
        $this->assertEquals('high', $result['risk_level']);
    }

    public function test_validate_alignment_detects_gdpr_violation(): void
    {
        $org = Organization::factory()->create();
        $this->service->setDNA($org->id, [
            'compliance_requirements' => ['GDPR'],
            'risk_appetite' => 'moderate',
        ]);

        $result = $this->service->validateAlignment($org->id, 'process_customer_data', [
            'processes_pii' => true,
            'gdpr_compliant' => false,
        ]);

        $this->assertFalse($result['aligned']);
        $this->assertEquals('critical', $result['risk_level']);
    }

    public function test_get_risk_appetite_score_returns_correct_numeric_values(): void
    {
        $org = Organization::factory()->create();

        $scores = [
            'conservative' => 25,
            'moderate' => 50,
            'calculated' => 60,
            'aggressive' => 75,
        ];

        foreach ($scores as $appetite => $expectedScore) {
            $this->service->setDNA($org->id, ['risk_appetite' => $appetite]);
            Cache::forget("org_dna_{$org->id}");

            $score = $this->service->getRiskAppetiteScore($org->id);
            $this->assertEquals($expectedScore, $score, "Risk appetite '{$appetite}' should map to score {$expectedScore}");
        }
    }

    public function test_initialize_defaults_creates_dna_with_sensible_values(): void
    {
        $org = Organization::factory()->create(['name' => 'Acme Corp']);
        $dna = $this->service->initializeDefaults($org);

        $this->assertInstanceOf(OrganizationDNA::class, $dna);
        $this->assertStringContainsString('Acme Corp', $dna->mission);
        $this->assertEquals('moderate', $dna->risk_appetite);
        $this->assertNotEmpty($dna->values);
        $this->assertNotEmpty($dna->leadership_principles);
    }
}
