<?php

namespace App\Services\Governance;

use App\Models\Organization;
use App\Services\Governance\Scorecard\ScorecardCertifier;
use App\Services\Governance\Scorecard\ScorecardDataCollector;
use App\Services\Governance\Scorecard\ScorecardDomainScorer;
use App\Services\Governance\Scorecard\ScorecardGateEvaluator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * MegaV2ScorecardService — thin orchestrator.
 *
 * Domain scoring → ScorecardDomainScorer
 * Gate evaluation → ScorecardGateEvaluator
 * Certification   → ScorecardCertifier
 */
class MegaV2ScorecardService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly ScorecardDataCollector $dataCollector,
        private readonly ScorecardDomainScorer $domainScorer,
        private readonly ScorecardGateEvaluator $gateEvaluator,
        private readonly ScorecardCertifier $certifier,
    ) {}

    public function generate(Organization $organization): array
    {
        $cacheKey = "mega_v2_scorecard:{$organization->id}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($organization) {
            return $this->compute($organization);
        });
    }

    public function invalidate(Organization $organization): void
    {
        Cache::forget("mega_v2_scorecard:{$organization->id}");
    }

    private function compute(Organization $organization): array
    {
        $data = $this->dataCollector->collect($organization);

        $dataTrust        = $data['dataTrust'];
        $agentReliability = $data['agentReliability'];
        $predictionAcc    = $data['predictionAcc'];
        $orgMemoryScore   = $data['orgMemoryScore'];
        $observability    = $data['observability'];
        $disResult        = $data['disResult'];
        $financialScore   = $data['financialScore'];
        $csScore          = $data['csScore'];

        $technicalScores = $this->domainScorer->computeTechnicalDomains($dataTrust, $observability, $disResult);
        $technicalRaw = $this->domainScorer->weightedAverage($technicalScores);
        $technicalWeighted = round($technicalRaw * 0.60, 2);

        $intelligenceScores = $this->domainScorer->computeIntelligenceDomains($predictionAcc, $agentReliability, $orgMemoryScore, $disResult);
        $intelligenceRaw = $this->domainScorer->weightedAverage($intelligenceScores);
        $intelligenceWeighted = round($intelligenceRaw * 0.30, 2);

        $businessScores = $this->domainScorer->computeBusinessDomains($orgMemoryScore, $financialScore, $csScore);
        $businessRaw = $this->domainScorer->weightedAverage($businessScores);
        $businessWeighted = round($businessRaw * 0.10, 2);

        $finalScore = round($technicalWeighted + $intelligenceWeighted + $businessWeighted, 2);

        $gates = $this->gateEvaluator->evaluate($finalScore, $dataTrust, $agentReliability, $predictionAcc, $observability, $disResult);
        $certification = $this->certifier->certify($finalScore, $gates);

        $result = [
            'organization_id' => $organization->id,
            'organization_name' => $organization->name,
            'final_score' => $finalScore,
            'certification' => $certification['label'],
            'level' => $certification['level'],
            'computed_at' => now()->toIso8601String(),
            'breakdown' => [
                'technical' => ['raw' => $technicalRaw,     'weighted' => $technicalWeighted,     'weight' => '60%', 'domains' => $technicalScores],
                'intelligence' => ['raw' => $intelligenceRaw,  'weighted' => $intelligenceWeighted,  'weight' => '30%', 'domains' => $intelligenceScores],
                'business' => ['raw' => $businessRaw,      'weighted' => $businessWeighted,      'weight' => '10%', 'domains' => $businessScores],
            ],
            'gates' => $gates,
            'gate_pass' => $gates['all_pass'],
            'source_scores' => [
                'data_trust' => $dataTrust['score'],
                'agent_reliability' => $agentReliability['score'],
                'prediction_accuracy' => $predictionAcc['score'],
                'org_memory' => $orgMemoryScore['score'],
                'observability' => $observability['score'],
                'dis_health' => ($disResult['total_agents'] ?? 0) > 0
                    ? round((($disResult['healthy'] ?? 0) / $disResult['total_agents']) * 100, 2)
                    : 100,
                'financial_intelligence' => $financialScore['score'],
                'customer_success' => $csScore['score'],
            ],
        ];

        if (! $gates['all_pass']) {
            Log::warning('[MegaV2] Scorecard gates FAILED', [
                'organization_id' => $organization->id,
                'score' => $finalScore,
                'failed_gates' => array_keys(array_filter($gates, fn ($g) => is_array($g) && ! ($g['pass'] ?? true))),
            ]);
        }

        return $result;
    }
}
