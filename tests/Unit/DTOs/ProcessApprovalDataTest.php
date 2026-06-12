<?php

namespace Tests\Unit\DTOs;

use App\DTOs\Governance\ProcessApprovalData;
use Tests\TestCase;

/**
 * Unit tests for ProcessApprovalData DTO.
 *
 * Validates construction, fromArray hydration, and the decision guard
 * that rejects invalid approval decisions.
 */
class ProcessApprovalDataTest extends TestCase
{
    public function test_can_construct_with_valid_approved_decision(): void
    {
        $dto = new ProcessApprovalData(
            approvalId: 1,
            decision: 'approved',
            reviewerNotes: 'LGTM',
        );

        $this->assertSame(1, $dto->approvalId);
        $this->assertSame('approved', $dto->decision);
        $this->assertSame('LGTM', $dto->reviewerNotes);
    }

    public function test_can_construct_with_rejected_decision(): void
    {
        $dto = new ProcessApprovalData(approvalId: 2, decision: 'rejected');

        $this->assertSame('rejected', $dto->decision);
        $this->assertNull($dto->reviewerNotes);
    }

    public function test_can_construct_with_escalated_decision(): void
    {
        $dto = new ProcessApprovalData(approvalId: 3, decision: 'escalated');

        $this->assertSame('escalated', $dto->decision);
    }

    public function test_constructor_throws_for_invalid_decision(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Invalid decision/');

        new ProcessApprovalData(approvalId: 1, decision: 'maybe');
    }

    public function test_from_array_hydrates_all_fields(): void
    {
        $dto = ProcessApprovalData::fromArray([
            'approval_id' => '7',
            'decision' => 'approved',
            'reviewer_notes' => 'All checks passed',
        ]);

        $this->assertSame(7, $dto->approvalId);
        $this->assertSame('approved', $dto->decision);
        $this->assertSame('All checks passed', $dto->reviewerNotes);
    }

    public function test_from_array_reviewer_notes_defaults_to_null(): void
    {
        $dto = ProcessApprovalData::fromArray([
            'approval_id' => '1',
            'decision' => 'rejected',
        ]);

        $this->assertNull($dto->reviewerNotes);
    }
}
