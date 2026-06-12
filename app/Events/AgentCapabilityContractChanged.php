<?php

namespace App\Events;

use App\Models\AgentVersion;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * AgentCapabilityContractChanged
 *
 * Fired when a new agent version is published with breaking capability changes
 * relative to the previously active version.  A "breaking change" is defined as:
 *  - Removing a capability key that existed in the previous version
 *  - Changing the input_type of an existing capability
 *
 * This event triggers a governance review workflow to ensure that all active
 * deployments depending on the changed capabilities are notified and updated.
 */
class AgentCapabilityContractChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly AgentVersion $newVersion,
        public readonly AgentVersion $previousVersion,
    ) {}
}
