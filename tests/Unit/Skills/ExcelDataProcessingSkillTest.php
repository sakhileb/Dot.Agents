<?php

namespace Tests\Unit\Skills;

use App\Skills\Platform\ExcelDataProcessingSkill;
use Tests\TestCase;

class ExcelDataProcessingSkillTest extends TestCase
{
    private ExcelDataProcessingSkill $skill;

    protected function setUp(): void
    {
        parent::setUp();
        $this->skill = new ExcelDataProcessingSkill;
    }

    // ── Contract ──────────────────────────────────────────

    public function test_key_and_layer_are_correct(): void
    {
        $this->assertSame('excel-data-processing', $this->skill->key());
        $this->assertSame('platform', $this->skill->layer());
    }

    // ── parse action ──────────────────────────────────────
    // Input key is 'data'; output keys: headers, rows, row_count, column_count, column_types, sample

    public function test_parse_csv_data(): void
    {
        $result = $this->skill->execute([
            'action' => 'parse',
            'data' => "name,age,city\nAlice,30,Cape Town\nBob,25,Johannesburg",
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('rows', $result->output);
        $this->assertArrayHasKey('row_count', $result->output);
        $this->assertArrayHasKey('headers', $result->output);
        $this->assertSame(2, $result->output['row_count']);
    }

    public function test_parse_json_data(): void
    {
        $result = $this->skill->execute([
            'action' => 'parse',
            'data' => json_encode([['product' => 'Widget', 'price' => 9.99], ['product' => 'Gadget', 'price' => 19.99]]),
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertSame(2, $result->output['row_count']);
    }

    public function test_parse_fails_with_empty_data(): void
    {
        $result = $this->skill->execute(['action' => 'parse', 'data' => '']);

        $this->assertSame('failed', $result->status);
    }

    // ── analyze action ────────────────────────────────────
    // Input key is 'data' (array or CSV string); output keys: row_count, column_count, statistics, data_quality_score

    public function test_analyze_returns_statistics(): void
    {
        $result = $this->skill->execute([
            'action' => 'analyze',
            'data' => [['score' => 80, 'age' => 25], ['score' => 90, 'age' => 30], ['score' => 70, 'age' => 22]],
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('statistics', $result->output);
        $this->assertArrayHasKey('row_count', $result->output);
        $this->assertSame(3, $result->output['row_count']);
    }

    // ── generate action ───────────────────────────────────
    // Output keys: spec, schema, column_count, sample_row, note

    public function test_generate_produces_schema(): void
    {
        $result = $this->skill->execute([
            'action' => 'generate',
            'spec' => 'A table of employee records with name, department, and salary',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('schema', $result->output);
        $this->assertArrayHasKey('column_count', $result->output);
        $this->assertGreaterThan(0, $result->output['column_count']);
    }

    public function test_generate_fails_without_spec(): void
    {
        $result = $this->skill->execute(['action' => 'generate']);

        $this->assertSame('failed', $result->status);
    }

    // ── export action ─────────────────────────────────────
    // Input key is 'data' (array); output keys: format, row_count, output, byte_size

    public function test_export_to_csv_format(): void
    {
        $result = $this->skill->execute([
            'action' => 'export',
            'data' => [['name' => 'Alice', 'score' => 95], ['name' => 'Bob', 'score' => 87]],
            'format' => 'csv',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertArrayHasKey('output', $result->output);
        $this->assertStringContainsString('Alice', $result->output['output']);
        $this->assertSame(2, $result->output['row_count']);
    }

    public function test_export_to_json_format(): void
    {
        $result = $this->skill->execute([
            'action' => 'export',
            'data' => [['name' => 'Alice', 'score' => 95]],
            'format' => 'json',
        ]);

        $this->assertSame('completed', $result->status);
        $this->assertJson($result->output['output']);
    }

    // ── fallback ──────────────────────────────────────────

    public function test_unknown_action_returns_failed(): void
    {
        $result = $this->skill->execute(['action' => 'nonexistent']);

        $this->assertSame('failed', $result->status);
        $this->assertStringContainsString('nonexistent', $result->output['error']);
    }

    // ── default action (analyze) ──────────────────────────

    public function test_default_action_is_analyze(): void
    {
        $result = $this->skill->execute([
            'data' => [['a' => 1, 'b' => 2], ['a' => 3, 'b' => 4]],
        ]);

        $this->assertNotSame('failed', $result->status);
    }
}
