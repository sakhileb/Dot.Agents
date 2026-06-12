<?php

namespace App\Skills\Platform;

use App\Skills\DTOs\SkillResult;

/**
 * ExcelDataProcessingSkill — backward-compatible router (Layer 5 — Platform Intelligence)
 *
 * Kept for compatibility with existing DB records and API consumers.
 * All logic has been extracted into focused skill classes:
 *   - ExcelAnalysisSkill (key: excel-analysis) — parse, analyze
 *   - ExcelExportSkill   (key: excel-export)   — generate, export
 *
 * New deployments should reference the focused skills directly.
 */
class ExcelDataProcessingSkill extends ExcelProcessingHelper
{
    private ExcelAnalysisSkill $analysis;

    private ExcelExportSkill $export;

    public function __construct()
    {
        $this->analysis = new ExcelAnalysisSkill;
        $this->export = new ExcelExportSkill;
    }

    public function key(): string
    {
        return 'excel-data-processing';
    }

    public function layer(): string
    {
        return 'platform';
    }

    /**
     * Delegates to ExcelAnalysisSkill or ExcelExportSkill based on action.
     *
     * Actions:
     *   parse, analyze   → ExcelAnalysisSkill
     *   generate, export → ExcelExportSkill
     */
    public function execute(array $input, array $context = []): SkillResult
    {
        $action = $input['action'] ?? 'analyze';

        return match ($action) {
            'parse', 'analyze' => $this->analysis->execute($input, $context),
            default => $this->export->execute($input, $context),
        };
    }
}
