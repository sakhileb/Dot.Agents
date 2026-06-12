<?php

namespace App\Services\Governance\Audit\Contracts;

use App\Models\AgentDeployment;

/**
 * All DWCA phase strategies must implement this contract.
 *
 * Each phase receives an AgentDeployment, runs its checks, and returns
 * a structured result array with: phase, score, passed, checks, failures.
 */
interface DWCAPhaseContract
{
    public function execute(AgentDeployment $deployment): array;
}
