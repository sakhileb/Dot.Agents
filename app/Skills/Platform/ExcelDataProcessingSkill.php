<?php

namespace App\Skills\Platform;

use App\Skills\BaseSkill;
use App\Skills\DTOs\SkillResult;

/**
 * Excel Data Processing Skill (Layer 5 — Platform Intelligence)
 *
 * Provides structured spreadsheet intelligence for agents that need to
 * analyse, transform, or generate tabular data. Inspired by the
 * haris-musa/excel-mcp-server MCP integration — surfaces Excel/CSV
 * operations as first-class agentic capabilities.
 *
 * Actions:
 *   parse     – parse a raw CSV/TSV/JSON payload into a typed table structure
 *   analyze   – compute descriptive statistics and identify patterns
 *   generate  – produce a structured table definition from a natural-language spec
 *   export    – serialise an agent's result set into a reportable format
 */
class ExcelDataProcessingSkill extends BaseSkill
{
    public function key(): string
    {
        return 'excel-data-processing';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Input keys:
     *   action      – parse | analyze | generate | export
     *   data        – raw string payload (CSV/TSV/JSON) or array of rows
     *   spec        – natural-language specification (used by generate)
     *   format      – output format for export: csv | json | table (default: json)
     *   columns     – array of column names to focus analysis on (optional)
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'analyze';

        return match ($action) {
            'parse' => $this->parseData($input),
            'analyze' => $this->analyzeData($input),
            'generate' => $this->generateTable($input),
            'export' => $this->exportData($input),
            default => SkillResult::failed("Unknown excel-data-processing action: [{$action}]"),
        };
    }

    // ── Actions ──────────────────────────────────────────

    /**
     * Parse a raw CSV, TSV, or JSON payload into a typed table structure.
     */
    private function parseData(array $input): SkillResult
    {
        $raw = $input['data'] ?? '';

        if (empty($raw)) {
            return SkillResult::failed('No data payload provided for parsing');
        }

        // If already an array, treat as pre-parsed rows
        if (is_array($raw)) {
            $rows = $raw;
            $headers = array_keys($rows[0] ?? []);
        } else {
            $parsed = $this->detectAndParse($raw);
            $rows = $parsed['rows'];
            $headers = $parsed['headers'];
        }

        $rowCount = count($rows);
        $columnCount = count($headers);

        // Infer column types
        $columnTypes = $this->inferColumnTypes($rows, $headers);

        return SkillResult::completed(
            [
                'headers' => $headers,
                'rows' => $rows,
                'row_count' => $rowCount,
                'column_count' => $columnCount,
                'column_types' => $columnTypes,
                'sample' => array_slice($rows, 0, 3),
            ],
            95.0
        );
    }

    /**
     * Compute descriptive statistics and surface data quality insights.
     */
    private function analyzeData(array $input): SkillResult
    {
        $data = $input['data'] ?? [];
        $focusColumns = $input['columns'] ?? [];

        if (empty($data)) {
            return SkillResult::skipped('No data provided for analysis');
        }

        // Normalise to array of rows
        $rows = is_array($data) ? $data : $this->detectAndParse($data)['rows'];
        $headers = array_keys($rows[0] ?? []);

        if (! empty($focusColumns)) {
            $headers = array_intersect($headers, $focusColumns);
        }

        $stats = [];
        $qualityIssues = [];

        foreach ($headers as $col) {
            $values = array_column($rows, $col);
            $numeric = array_filter($values, 'is_numeric');
            $nulls = count(array_filter($values, fn ($v) => $v === null || $v === ''));

            $colStats = [
                'column' => $col,
                'total_values' => count($values),
                'null_count' => $nulls,
                'null_rate_pct' => count($values) > 0 ? round($nulls / count($values) * 100, 1) : 0,
                'unique_count' => count(array_unique($values)),
            ];

            if (! empty($numeric)) {
                $nums = array_map('floatval', $numeric);
                sort($nums);
                $colStats['numeric'] = [
                    'min' => min($nums),
                    'max' => max($nums),
                    'mean' => round(array_sum($nums) / count($nums), 4),
                    'median' => $this->median($nums),
                    'sum' => round(array_sum($nums), 4),
                ];
            }

            if ($nulls > 0) {
                $qualityIssues[] = "{$nulls} null/empty value(s) in column '{$col}'";
            }

            $stats[$col] = $colStats;
        }

        $healthScore = $this->clamp(100 - count($qualityIssues) * 5);

        return SkillResult::completed(
            [
                'row_count' => count($rows),
                'column_count' => count($headers),
                'statistics' => $stats,
                'data_quality_score' => round($healthScore, 1),
            ],
            90.0,
            $qualityIssues,
            $qualityIssues ? ['Review and clean null values before downstream processing'] : []
        );
    }

    /**
     * Generate a structured table definition from a natural-language specification.
     */
    private function generateTable(array $input): SkillResult
    {
        $spec = $input['spec'] ?? '';

        if (empty($spec)) {
            return SkillResult::failed('No table specification provided');
        }

        // Derive column names from spec keywords
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

    /**
     * Serialise a structured result set into the requested output format.
     */
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
            default => SkillResult::failed("Unsupported export format: [{$format}]"),
        };

        if ($exported instanceof SkillResult) {
            return $exported;
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

    // ── Helpers ──────────────────────────────────────────

    private function detectAndParse(string $raw): array
    {
        $raw = trim($raw);

        // JSON array
        if (str_starts_with($raw, '[') || str_starts_with($raw, '{')) {
            $decoded = json_decode($raw, true);
            $rows = is_array($decoded) ? (isset($decoded[0]) ? $decoded : [$decoded]) : [];

            return ['rows' => $rows, 'headers' => array_keys($rows[0] ?? [])];
        }

        // CSV / TSV
        $delimiter = str_contains($raw, "\t") ? "\t" : ',';
        $lines = array_filter(explode("\n", $raw));
        $headers = str_getcsv(array_shift($lines), $delimiter);
        $rows = array_map(fn ($l) => array_combine($headers, str_getcsv($l, $delimiter) + array_fill(0, count($headers), null)), $lines);

        return ['rows' => array_values(array_filter($rows)), 'headers' => $headers];
    }

    private function inferColumnTypes(array $rows, array $headers): array
    {
        $types = [];
        foreach ($headers as $col) {
            $values = array_filter(array_column($rows, $col));
            $numericCount = count(array_filter($values, 'is_numeric'));
            $total = count($values);

            $types[$col] = $total > 0 && $numericCount / $total > 0.8 ? 'numeric' : 'string';
        }

        return $types;
    }

    private function inferColumnsFromKeywords(array $keywords): array
    {
        $columnMap = [
            'revenue' => 'float', 'cost' => 'float', 'profit' => 'float', 'amount' => 'float',
            'price' => 'float', 'total' => 'float', 'budget' => 'float', 'spend' => 'float',
            'date' => 'date', 'time' => 'datetime', 'created' => 'datetime', 'updated' => 'datetime',
            'count' => 'integer', 'quantity' => 'integer', 'id' => 'integer', 'number' => 'integer',
            'name' => 'string', 'title' => 'string', 'description' => 'string', 'status' => 'string',
            'email' => 'string', 'phone' => 'string', 'address' => 'string', 'category' => 'string',
        ];

        $columns = [['name' => 'id', 'type' => 'integer']]; // always include id

        foreach ($keywords as $word) {
            if (isset($columnMap[$word]) && ! collect($columns)->pluck('name')->contains($word)) {
                $columns[] = ['name' => $word, 'type' => $columnMap[$word]];
            }
        }

        return $columns;
    }

    private function median(array $sortedNums): float
    {
        $count = count($sortedNums);
        if ($count === 0) {
            return 0.0;
        }
        $mid = (int) floor($count / 2);

        return $count % 2 === 0
            ? ($sortedNums[$mid - 1] + $sortedNums[$mid]) / 2
            : (float) $sortedNums[$mid];
    }

    private function toCsv(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }
        $lines = [implode(',', array_keys($rows[0]))];
        foreach ($rows as $row) {
            $lines[] = implode(',', array_map(fn ($v) => '"'.str_replace('"', '""', (string) $v).'"', $row));
        }

        return implode("\n", $lines);
    }

    private function toMarkdownTable(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }
        $headers = array_keys($rows[0]);
        $header = '| '.implode(' | ', $headers).' |';
        $divider = '| '.implode(' | ', array_fill(0, count($headers), '---')).' |';
        $body = implode("\n", array_map(
            fn ($r) => '| '.implode(' | ', array_values($r)).' |',
            $rows
        ));

        return implode("\n", [$header, $divider, $body]);
    }
}
