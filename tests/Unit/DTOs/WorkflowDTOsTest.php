<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Workflows\DeleteWorkflowData;
use App\DTOs\Workflows\UpdateWorkflowStatusData;
use Tests\TestCase;

class WorkflowDTOsTest extends TestCase
{
    public function test_delete_workflow_data_stores_id(): void
    {
        $dto = DeleteWorkflowData::fromId(42);
        $this->assertSame(42, $dto->workflowId);
    }

    public function test_update_workflow_status_publish_factory(): void
    {
        $dto = UpdateWorkflowStatusData::publish();
        $this->assertSame(UpdateWorkflowStatusData::STATUS_ACTIVE, $dto->status);
        $this->assertNull($dto->reason);
    }

    public function test_update_workflow_status_unpublish_factory(): void
    {
        $dto = UpdateWorkflowStatusData::unpublish('reverting for review');
        $this->assertSame(UpdateWorkflowStatusData::STATUS_DRAFT, $dto->status);
        $this->assertSame('reverting for review', $dto->reason);
    }
}
