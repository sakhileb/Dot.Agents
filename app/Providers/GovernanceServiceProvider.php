<?php

namespace App\Providers;

use App\Services\AI\AgentCertificationService;
use App\Services\Governance\AgentReliabilityAuditorService;
use App\Services\Governance\AgentReputationService;
use App\Services\Governance\AuditService;
use App\Services\Governance\CustomerSuccessService;
use App\Services\Governance\DataTrustScoreService;
use App\Services\Governance\DelusionDetectionService;
use App\Services\Governance\DigitalImmuneSystem;
use App\Services\Governance\DWCAAuditService;
use App\Services\Governance\FinancialIntelligenceService;
use App\Services\Governance\MegaV2ScorecardService;
use App\Services\Governance\OrganizationalMemoryService;
use App\Services\Governance\PredictionAccuracyTrackingService;
use App\Services\Governance\Scorecard\ScorecardCertifier;
use App\Services\Governance\Scorecard\ScorecardDomainScorer;
use App\Services\Governance\Scorecard\ScorecardGateEvaluator;
use App\Services\Governance\ScorecardService;
use App\Services\Infrastructure\ObservabilityService;
use Illuminate\Support\ServiceProvider;

class GovernanceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AuditService::class);
        $this->app->singleton(DelusionDetectionService::class);
        $this->app->singleton(ScorecardService::class);
        $this->app->singleton(DataTrustScoreService::class);
        $this->app->singleton(PredictionAccuracyTrackingService::class);
        $this->app->singleton(OrganizationalMemoryService::class);
        $this->app->singleton(FinancialIntelligenceService::class);
        $this->app->singleton(CustomerSuccessService::class);
        $this->app->singleton(AgentReputationService::class);

        $this->app->singleton(AgentReliabilityAuditorService::class, function ($app) {
            return new AgentReliabilityAuditorService(
                $app->make(AgentReputationService::class),
            );
        });

        $this->app->singleton(DigitalImmuneSystem::class, function ($app) {
            return new DigitalImmuneSystem($app->make(AuditService::class));
        });

        $this->app->singleton(MegaV2ScorecardService::class, function ($app) {
            return new MegaV2ScorecardService(
                $app->make(DataTrustScoreService::class),
                $app->make(AgentReliabilityAuditorService::class),
                $app->make(PredictionAccuracyTrackingService::class),
                $app->make(OrganizationalMemoryService::class),
                $app->make(ObservabilityService::class),
                $app->make(DigitalImmuneSystem::class),
                $app->make(FinancialIntelligenceService::class),
                $app->make(CustomerSuccessService::class),
                $app->make(ScorecardDomainScorer::class),
                $app->make(ScorecardGateEvaluator::class),
                $app->make(ScorecardCertifier::class),
            );
        });

        $this->app->singleton(DWCAAuditService::class, function ($app) {
            return new DWCAAuditService(
                $app->make(AgentCertificationService::class),
                $app->make(AuditService::class),
                $app->make(DelusionDetectionService::class),
                $app->make(DigitalImmuneSystem::class),
            );
        });
    }
}
