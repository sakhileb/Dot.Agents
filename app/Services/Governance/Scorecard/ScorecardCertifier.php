<?php

namespace App\Services\Governance\Scorecard;

/**
 * ScorecardCertifier
 *
 * Determines the MEGA V2 certification label and maturity level from a numeric score.
 *
 * Levels:
 *  100       → Self-Governing Digital Organization (Level 5)
 *  98–99     → Autonomous Enterprise Ready         (Level 5)
 *  95–97     → Enterprise Production Ready         (Level 5)
 *  90–94     → Enterprise Ready                    (Level 4)
 *  80–89     → Conditional Pass                    (Level 3)
 *  < 80      → High Risk                           (Level 2)
 *  Gate fail → High Risk — Gate Failure            (Level 1)
 *
 * This class is purely computational — no I/O, no cache, no side-effects.
 */
class ScorecardCertifier
{
    /**
     * Return the certification label and maturity level for the given score.
     * If any gate has failed, the certification is overridden to GATE FAILURE.
     *
     * @return array{label: string, level: int}
     */
    public function certify(float $score, array $gates): array
    {
        if (! $gates['all_pass']) {
            return ['label' => 'HIGH RISK — GATE FAILURE', 'level' => 1];
        }

        return match (true) {
            $score >= 100 => ['label' => 'SELF-GOVERNING DIGITAL ORGANIZATION', 'level' => 5],
            $score >= 98 => ['label' => 'AUTONOMOUS ENTERPRISE READY', 'level' => 5],
            $score >= 95 => ['label' => 'ENTERPRISE PRODUCTION READY', 'level' => 5],
            $score >= 90 => ['label' => 'ENTERPRISE READY', 'level' => 4],
            $score >= 80 => ['label' => 'CONDITIONAL PASS', 'level' => 3],
            default => ['label' => 'HIGH RISK', 'level' => 2],
        };
    }
}
