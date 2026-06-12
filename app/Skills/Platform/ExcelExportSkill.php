<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * ExcelExportSkill — table generation and data export (Layer 5 — Platform Intelligence)
 *
 * Handles the two export-focused Excel actions extracted from ExcelDataProcessingSkill:
 *   generate – produce a structured table definition from a natural-language spec
 *   export   – serialise a result set to CSV, JSON, or Markdown table
 *
 * Shares parsing/serialisation helpers with ExcelAnalysisSkill via ExcelProcessingHelper.
 */
class ExcelExportSkill extends ExcelProcessingHelper
{
    public function key(): string
    {
        return 'excel-export';
    }

    public function layer(): string
    {
        return 'platform';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'export';

        return match ($action) {
            'generate' => $this->generateTable($input),
            'export' => $this->exportData($input),
            default => SkillResult::failed("Unknown excel-export action: [{$action}]"),
        };
    }

    private function generateTable(array $input): SkillResult
    {
        $spec = $input['spec'] ?? '';

        if (empty($spec)) {
            return SkillResult::failed('No table specification provided');
        }

        $keywords = preg_split('/[\s,;]+/', strtolower($spec));
        $domainColumns = $this->inferColumnsFromKeywords($keywords);

        $schema = array_map(fn ($col) => [
            'name' => $col['name'],
            'type' => $col['type'],
            'nullable' => true,
            'description' => ucfirst(str_replace('_', ' ', $col['name'])),
        ], $domainColumns);

        return SkillResult::completed(
            [
                'spec' => $spec,
                'schema' => $schema,
                'column_count' => count($schema),
                'sample_row' => array_combine(
                    array_column($schema, 'name'),
                    array_fill(0, count($schema), null)
                ),
                'note' => 'Schema inferred from specification — review and refine before production use',
            ],
            75.0,
            [],
            ['Validate inferred column types against your actual data source before using this schema']
        );
    }

    private function exportData(array $input): SkillResult
    {
        $data = $input['data'] ?? [];
        $format = $input['format'] ?? 'json';

        if (empty($data)) {
            return SkillResult::failed('No data provided for export');
        }

        $rows = is_array($data) ? $data : $this->detectAndParse($data)['rows'];

        $exported = match ($format) {
            'csv' => $this->toCsv($rows),
            'table' => $this->toMarkdownTable($rows),
            'json' => json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            default => null,
        };

        if ($exported === null) {
            return SkillResult::failed("Unsupported export format: [{$format}]");
        }

        return SkillResult::completed(
            [
                'format' => $format,
                'row_count' => count($rows),
                'output' => $exported,
                'byte_size' => strlen($exported),
            ],
            98.0
        );
    }
}
