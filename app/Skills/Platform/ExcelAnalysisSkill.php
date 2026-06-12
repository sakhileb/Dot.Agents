<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * ExcelAnalysisSkill — tabular data parsing and analysis (Layer 5 — Platform Intelligence)
 *
 * Handles the two analysis-focused Excel actions extracted from ExcelDataProcessingSkill:
 *   parse   – parse raw CSV/TSV/JSON into a typed table structure
 *   analyze – compute descriptive statistics and data quality metrics
 *
 * Shares parsing/typing helpers with ExcelExportSkill via ExcelProcessingHelper.
 */
class ExcelAnalysisSkill extends ExcelProcessingHelper
{
    public function key(): string
    {
        return 'excel-analysis';
    }

    public function layer(): string
    {
        return 'platform';
    }

    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'parse';

        return match ($action) {
            'parse' => $this->parseData($input),
            'analyze' => $this->analyzeData($input),
            default => SkillResult::failed("Unknown excel-analysis action: [{$action}]"),
        };
    }

    private function parseData(array $input): SkillResult
    {
        $raw = $input['data'] ?? '';

        if (empty($raw)) {
            return SkillResult::failed('No data payload provided for parsing');
        }

        if (is_array($raw)) {
            $rows = $raw;
            $headers = array_keys($rows[0] ?? []);
        } else {
            $parsed = $this->detectAndParse($raw);
            $rows = $parsed['rows'];
            $headers = $parsed['headers'];
        }

        return SkillResult::completed(
            [
                'headers' => $headers,
                'rows' => $rows,
                'row_count' => count($rows),
                'column_count' => count($headers),
                'column_types' => $this->inferColumnTypes($rows, $headers),
                'sample' => array_slice($rows, 0, 3),
            ],
            95.0
        );
    }

    private function analyzeData(array $input): SkillResult
    {
        $data = $input['data'] ?? [];
        $focusColumns = $input['columns'] ?? [];

        if (empty($data)) {
            return SkillResult::skipped('No data provided for analysis');
        }

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
}
