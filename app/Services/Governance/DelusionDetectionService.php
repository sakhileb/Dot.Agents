<?php

namespace App\Services\Governance;

/**
 * Agent Delusion Detection System
 *
 * Analyzes agent outputs for hallucinations, false confidence,
 * data fabrication, and reasoning errors.
 */
class DelusionDetectionService
{
    /**
     * Analyze an agent's output for delusion/hallucination risk.
     *
     * Returns a scored analysis report.
     */
    public function analyze(string $taskInput, array $agentOutput, array $inputData = []): array
    {
        $scores = [];
        $flags = [];

        // 1. Evidence Quality — are claims backed by provided data?
        $evidenceScore = $this->scoreEvidenceQuality($agentOutput, $inputData);
        $scores['evidence_quality'] = $evidenceScore;
        if ($evidenceScore < 60) {
            $flags[] = 'Low evidence quality: claims may not be supported by input data';
        }

        // 2. Assumption Count — more assumptions = higher risk
        $assumptions = $agentOutput['assumptions'] ?? [];
        $assumptionCount = is_array($assumptions) ? count($assumptions) : 0;
        $assumptionScore = max(0, 100 - ($assumptionCount * 15));
        $scores['assumption_penalty'] = $assumptionScore;
        if ($assumptionCount > 3) {
            $flags[] = "High assumption count: {$assumptionCount} assumptions detected";
        }

        // 3. Confidence Calibration — is stated confidence appropriate?
        $statedConfidence = (float) ($agentOutput['confidence'] ?? 75);
        $calibrationScore = $this->scoreConfidenceCalibration($statedConfidence, $agentOutput, $inputData);
        $scores['confidence_calibration'] = $calibrationScore;
        if ($calibrationScore < 50) {
            $flags[] = 'Confidence appears miscalibrated relative to evidence';
        }

        // 4. Source Credibility — are external references verifiable?
        $credibilityScore = $this->scoreSourceCredibility($agentOutput);
        $scores['source_credibility'] = $credibilityScore;
        if ($credibilityScore < 60) {
            $flags[] = 'Unverifiable or suspicious sources referenced';
        }

        // 5. Data Completeness — are there data gaps?
        $completenessScore = $this->scoreDataCompleteness($taskInput, $inputData);
        $scores['data_completeness'] = $completenessScore;
        if ($completenessScore < 50) {
            $flags[] = 'Insufficient data to make reliable conclusions';
        }

        // 6. Contradicting Evidence Detection
        $contradictionRisk = $this->detectContradictions($agentOutput);
        $scores['contradiction_risk'] = $contradictionRisk;
        if ($contradictionRisk > 40) {
            $flags[] = 'Potential contradictions detected in reasoning';
        }

        // Calculate overall delusion risk score (0=safe, 100=dangerous)
        $riskScore = $this->calculateRiskScore($scores, $flags, $statedConfidence);

        // Reality alignment (inverse of risk)
        $realityAlignment = max(0, 100 - $riskScore);

        // Verification score — how easy is the output to verify?
        $verificationScore = $this->calculateVerificationScore($agentOutput);

        return [
            'risk_score' => round($riskScore, 2),
            'reality_alignment' => round($realityAlignment, 2),
            'verification_score' => round($verificationScore, 2),
            'evidence_quality' => round($evidenceScore, 2),
            'evidence_quality_score' => round($evidenceScore, 2),  // alias
            'source_credibility' => round($credibilityScore, 2),
            'assumption_count' => $assumptionCount,
            'flags' => $flags,
            'scores' => $scores,
            'analysis' => $this->generateAnalysisSummary($riskScore, $flags),
            'recommendation' => $this->getRecommendation($riskScore),
        ];
    }

    private function scoreEvidenceQuality(array $output, array $inputData): float
    {
        if (empty($inputData) && empty($output['evidence'] ?? [])) {
            return 40.0; // No data = lower quality
        }

        $evidenceItems = $output['evidence'] ?? [];
        if (empty($evidenceItems)) {
            return 55.0;
        }

        // Check if evidence items reference actual input data keys
        $inputKeys = array_keys($inputData);
        $matchCount = 0;

        foreach ($evidenceItems as $evidence) {
            $evidenceStr = is_string($evidence) ? $evidence : json_encode($evidence);
            foreach ($inputKeys as $key) {
                if (stripos($evidenceStr, $key) !== false) {
                    $matchCount++;
                    break;
                }
            }
        }

        $matchRatio = empty($inputKeys) ? 0.5 : ($matchCount / count($evidenceItems));

        return 50 + ($matchRatio * 50);
    }

    private function scoreConfidenceCalibration(float $stated, array $output, array $inputData): float
    {
        $evidenceCount = count($output['evidence'] ?? []);
        $assumptionCount = count($output['assumptions'] ?? []);
        $riskCount = count($output['risks'] ?? []);

        // High confidence with many assumptions and risks is suspicious
        if ($stated > 85 && $assumptionCount > 2 && $riskCount > 2) {
            return 30.0; // Overconfident
        }

        if ($stated > 90 && empty($inputData)) {
            return 20.0; // No data but very confident = hallucination risk
        }

        if ($stated >= 60 && $stated <= 85) {
            return 85.0; // Well-calibrated range
        }

        return 70.0;
    }

    private function scoreSourceCredibility(array $output): float
    {
        // Check for unverifiable claims (fake statistics, made-up references)
        $reasoning = $output['reasoning'] ?? '';

        // Patterns that suggest fabricated data
        $suspiciousPatterns = [
            '/\d+\.?\d*%/', // Percentage claims without source
            '/according to .{5,50} study/i',
            '/research shows/i',
        ];

        $suspicionCount = 0;
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $reasoning)) {
                $suspicionCount++;
            }
        }

        return max(40, 100 - ($suspicionCount * 15));
    }

    private function scoreDataCompleteness(string $taskInput, array $inputData): float
    {
        if (empty($inputData)) {
            // No structured data provided — assess based on task type
            $dataKeywords = ['analyze', 'calculate', 'report', 'forecast', 'audit'];
            foreach ($dataKeywords as $kw) {
                if (stripos($taskInput, $kw) !== false) {
                    return 35.0; // Analysis task with no data is risky
                }
            }

            return 60.0; // Non-analytical task might be ok without data
        }

        return 80.0; // Data provided
    }

    private function detectContradictions(array $output): float
    {
        $reasoning = $output['reasoning'] ?? '';
        $risks = $output['risks'] ?? [];
        $recommendations = $output['recommendations'] ?? [];

        // Simple heuristic: if risks exist but recommendations ignore them, flag it
        if (! empty($risks) && empty($recommendations)) {
            return 60.0;
        }

        return 10.0; // Low contradiction risk
    }

    private function calculateRiskScore(array $scores, array $flags, float $confidence): float
    {
        $flagPenalty = count($flags) * 8;

        $avgScore = ! empty($scores) ? array_sum($scores) / count($scores) : 50;
        $baseRisk = max(0, 100 - $avgScore);

        // Boost risk if agent is very confident despite low evidence
        if ($confidence > 90 && ($scores['evidence_quality'] ?? 50) < 50) {
            $baseRisk = min(100, $baseRisk + 25);
        }

        return min(100, $baseRisk + $flagPenalty);
    }

    private function calculateVerificationScore(array $output): float
    {
        // Higher score = easier to verify
        $hasEvidence = ! empty($output['evidence']);
        $hasReasoning = ! empty($output['reasoning']);
        $hasAssumptions = ! empty($output['assumptions']); // transparent = good

        $score = 50;
        if ($hasEvidence) {
            $score += 25;
        }
        if ($hasReasoning) {
            $score += 15;
        }
        if ($hasAssumptions) {
            $score += 10; // Being transparent about assumptions is positive
        }

        return min(100, $score);
    }

    private function generateAnalysisSummary(float $riskScore, array $flags): string
    {
        $level = match (true) {
            $riskScore >= 80 => 'CRITICAL DELUSION RISK',
            $riskScore >= 60 => 'HIGH DELUSION RISK',
            $riskScore >= 40 => 'MODERATE CONCERN',
            $riskScore >= 20 => 'LOW RISK',
            default => 'SAFE',
        };

        $summary = "Delusion Detection: {$level} (Score: {$riskScore}/100). ";

        if (! empty($flags)) {
            $summary .= 'Issues detected: '.implode('; ', $flags).'.';
        } else {
            $summary .= 'No significant issues detected.';
        }

        return $summary;
    }

    private function getRecommendation(float $riskScore): string
    {
        return match (true) {
            $riskScore >= 80 => 'BLOCK: Requires thorough human review before any action',
            $riskScore >= 60 => 'ESCALATE: Send for mandatory human approval',
            $riskScore >= 40 => 'REVIEW: Flag for human review before implementation',
            $riskScore >= 20 => 'MONITOR: Proceed with caution, log for review',
            default => 'PROCEED: Low risk, standard monitoring applies',
        };
    }
}
