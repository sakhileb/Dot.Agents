<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\ExcelExportSkill;
use Tests\TestCase;

class ExcelExportSkillTest extends TestCase
{
    private ExcelExportSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ExcelExportSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('excel-export', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    public function test_generate_builds_table_schema(): void
    {
        $result = $this->skill->execute([
            'action' => 'generate',
            'spec' => 'sales report with name, revenue, region, quarter',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('schema', $result->output);
        $this->assertArrayHasKey('column_count', $result->output);
    }

    public function test_export_produces_file_payload(): void
    {
        $result = $this->skill->execute([
            'action' => 'export',
            'data' => [
                ['name' => 'Alice', 'score' => 95],
                ['name' => 'Bob',   'score' => 88],
            ],
            'format' => 'csv',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('format', $result->output);
    }

    public function test_generate_fails_without_spec(): void
    {
        $result = $this->skill->execute([
            'action' => 'generate',
            'spec' => '',
        ]);

        $this->assertSame('failed', $result->status);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'upload']);

        $this->assertSame('failed', $result->status);
    }
}
