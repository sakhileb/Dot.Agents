<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\ExcelAnalysisSkill;
use Tests\TestCase;

class ExcelAnalysisSkillTest extends TestCase
{
    private ExcelAnalysisSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ExcelAnalysisSkill;
    }

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('excel-analysis', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    public function test_parse_returns_row_and_column_count(): void
    {
        $result = $this->skill->execute([
            'action' => 'parse',
            'data' => [
                ['name' => 'Alice', 'revenue' => 5000],
                ['name' => 'Bob',   'revenue' => 8000],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('row_count', $result->output);
        $this->assertArrayHasKey('column_count', $result->output);
        $this->assertSame(2, $result->output['row_count']);
    }

    public function test_parse_fails_without_data(): void
    {
        $result = $this->skill->execute(['action' => 'parse', 'data' => '']);

        $this->assertSame('failed', $result->status);
    }

    public function test_analyze_returns_statistics(): void
    {
        $result = $this->skill->execute([
            'action' => 'analyze',
            'data' => [
                ['name' => 'Alice', 'revenue' => 5000],
                ['name' => 'Bob',   'revenue' => 8000],
                ['name' => 'Carol', 'revenue' => 3000],
            ],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('statistics', $result->output);
    }

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'bad_action']);

        $this->assertSame('failed', $result->status);
    }
}
