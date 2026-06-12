<?php

namespace Tests\Unit\Models;

use App\Models\AgentVersion;
use Tests\TestCase;

/**
 * Unit tests for AgentVersion::hasBreakingCapabilityChanges().
 *
 * This method is purely computational — no I/O, no DB — so no need for
 * RefreshDatabase. We exercise the model method via a plain (unsaved) instance.
 */
class AgentVersionCapabilityContractTest extends TestCase
{
    private AgentVersion $version;

    protected function setUp(): void
    {
        parent::setUp();
        $this->version = new AgentVersion;
    }

    // ── Empty / first-version cases ───────────────────────────────────────────

    public function test_no_breaking_changes_when_previous_is_empty(): void
    {
        // First version — no prior contract to break
        $result = $this->version->hasBreakingCapabilityChanges([], ['search' => ['input_type' => 'string']]);

        $this->assertFalse($result);
    }

    public function test_no_breaking_changes_when_both_empty(): void
    {
        $result = $this->version->hasBreakingCapabilityChanges([], []);

        $this->assertFalse($result);
    }

    // ── Non-breaking (additive) changes ───────────────────────────────────────

    public function test_no_breaking_changes_when_new_capability_added(): void
    {
        $previous = ['search' => ['input_type' => 'string']];
        $current = [
            'search' => ['input_type' => 'string'],
            'analyze' => ['input_type' => 'array'],  // new — non-breaking
        ];

        $this->assertFalse($this->version->hasBreakingCapabilityChanges($previous, $current));
    }

    public function test_no_breaking_changes_when_capabilities_identical(): void
    {
        $snapshot = [
            'search' => ['input_type' => 'string'],
            'report' => ['input_type' => 'array'],
        ];

        $this->assertFalse($this->version->hasBreakingCapabilityChanges($snapshot, $snapshot));
    }

    // ── Breaking: removed capabilities ───────────────────────────────────────

    public function test_breaking_change_when_capability_removed(): void
    {
        $previous = [
            'search' => ['input_type' => 'string'],
            'report' => ['input_type' => 'array'],
        ];
        $current = [
            'search' => ['input_type' => 'string'],
            // 'report' removed — breaking
        ];

        $this->assertTrue($this->version->hasBreakingCapabilityChanges($previous, $current));
    }

    public function test_breaking_change_when_all_capabilities_removed(): void
    {
        $previous = ['search' => ['input_type' => 'string']];
        $current = [];

        $this->assertTrue($this->version->hasBreakingCapabilityChanges($previous, $current));
    }

    // ── Breaking: input type changes ──────────────────────────────────────────

    public function test_breaking_change_when_input_type_changed(): void
    {
        $previous = ['search' => ['input_type' => 'string']];
        $current = ['search' => ['input_type' => 'array']];  // type changed — breaking

        $this->assertTrue($this->version->hasBreakingCapabilityChanges($previous, $current));
    }

    public function test_no_breaking_change_when_input_type_added_to_capability(): void
    {
        // Previous had no input_type; new adds it — not a breaking removal or type change
        $previous = ['search' => []];
        $current = ['search' => ['input_type' => 'string']];

        // prevInputType is null → no type-change detection fires → non-breaking
        $this->assertFalse($this->version->hasBreakingCapabilityChanges($previous, $current));
    }

    public function test_breaking_change_on_one_of_multiple_capabilities(): void
    {
        $previous = [
            'search' => ['input_type' => 'string'],
            'analyze' => ['input_type' => 'string'],
        ];
        $current = [
            'search' => ['input_type' => 'string'],
            'analyze' => ['input_type' => 'array'],  // only 'analyze' changed
        ];

        $this->assertTrue($this->version->hasBreakingCapabilityChanges($previous, $current));
    }
}
