<?php

namespace App\Services\Social;

use App\DTOs\Social\SentimentAnalysisData;
use App\Events\NegativeSentimentDetected;
use App\Models\SocialAccount;
use App\Models\SocialReview;
use App\Models\SocialSentimentScore;
use Illuminate\Support\Facades\Log;

/**
 * Reputation Monitoring Service.
 *
 * Scans connected social accounts for brand mentions, reviews,
 * and emerging negative trends. Triggers escalation events when
 * brand risk thresholds are exceeded.
 */
class ReputationMonitoringService
{
    public function __construct(
        private readonly SentimentAnalysisService $sentimentService,
    ) {}

    /**
     * Scan a social account for new reviews and mentions.
     * In production this calls the platform's Webhooks/API.
     * Here it evaluates pending unscored reviews.
     */
    public function scanMentions(SocialAccount $account): void
    {
        $this->evaluatePendingReviews($account);
        $this->detectNegativeTrends($account);
    }

    /**
     * Score any unscored reviews for the account and flag escalations.
     */
    private function evaluatePendingReviews(SocialAccount $account): void
    {
        $unscoredReviews = SocialReview::withoutGlobalScope('organization')
            ->where('social_account_id', $account->id)
            ->where('sentiment', 'neutral')
            ->whereNotNull('review_text')
            ->limit(50)
            ->get();

        foreach ($unscoredReviews as $review) {
            try {
                $data = new SentimentAnalysisData(
                    organizationId: $account->organization_id,
                    subjectType: 'review',
                    text: $review->review_text,
                    platform: $account->platform,
                    socialAccountId: $account->id,
                );

                $score = $this->sentimentService->analyze($data);

                $review->update([
                    'sentiment' => $score->sentiment,
                    'sentiment_score' => $score->score,
                    'requires_escalation' => $score->requires_escalation,
                ]);
            } catch (\Throwable $e) {
                Log::warning('ReputationMonitoringService: failed to score review', [
                    'review_id' => $review->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Detect emerging negative trends (3+ angry/frustrated scores in 24h).
     */
    private function detectNegativeTrends(SocialAccount $account): void
    {
        $recentNegativeCount = SocialSentimentScore::withoutGlobalScope('organization')
            ->where('organization_id', $account->organization_id)
            ->where('social_account_id', $account->id)
            ->whereIn('sentiment', ['frustrated', 'angry'])
            ->where('requires_escalation', true)
            ->where('escalation_handled', false)
            ->where('scored_at', '>=', now()->subHours(24))
            ->count();

        if ($recentNegativeCount >= 3) {
            Log::warning('ReputationMonitoringService: negative trend detected', [
                'organization_id' => $account->organization_id,
                'account_id' => $account->id,
                'count_24h' => $recentNegativeCount,
            ]);

            // Fire the most recent unhandled escalation event
            $latestScore = SocialSentimentScore::withoutGlobalScope('organization')
                ->where('social_account_id', $account->id)
                ->where('requires_escalation', true)
                ->where('escalation_handled', false)
                ->latest('scored_at')
                ->first();

            if ($latestScore) {
                event(new NegativeSentimentDetected($latestScore));
            }
        }
    }

    /**
     * Calculate an overall brand health score (0-100) for an organization.
     */
    public function calculateBrandHealthScore(int $organizationId): float
    {
        $reviewAvg = SocialReview::withoutGlobalScope('organization')
            ->where('organization_id', $organizationId)
            ->where('reviewed_at', '>=', now()->subDays(30))
            ->avg('sentiment_score') ?? 50.0;

        $sentimentAvg = SocialSentimentScore::withoutGlobalScope('organization')
            ->where('organization_id', $organizationId)
            ->where('scored_at', '>=', now()->subDays(30))
            ->avg('score') ?? 50.0;

        $unhandledEscalations = SocialSentimentScore::withoutGlobalScope('organization')
            ->where('organization_id', $organizationId)
            ->where('requires_escalation', true)
            ->where('escalation_handled', false)
            ->where('scored_at', '>=', now()->subDays(7))
            ->count();

        $baseScore = ($reviewAvg * 0.5) + ($sentimentAvg * 0.5);
        $penalty = min(20, $unhandledEscalations * 2);

        return max(0, round($baseScore - $penalty, 2));
    }
}
