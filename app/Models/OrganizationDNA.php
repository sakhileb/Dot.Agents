<?php

namespace App\Models;

use App\Models\Concerns\HasOrganizationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Organization DNA — Enterprise Constitutional Engine
 *
 * Stores the organizational mission, vision, values, and principles
 * that ALL agents inherit automatically on every task execution.
 * This is the enterprise constitution that governs all AI behavior.
 */
class OrganizationDNA extends Model
{
    use HasFactory, HasOrganizationScope;

    protected $table = 'organization_dna';

    protected $fillable = [
        'uuid', 'organization_id', 'mission', 'vision', 'values',
        'leadership_principles', 'decision_principles', 'risk_appetite',
        'compliance_requirements', 'strategic_priorities', 'industry_constraints',
        'primary_language', 'operating_regions', 'fiscal_year_start',
        'monthly_ai_budget_usd', 'metadata', 'last_reviewed_at', 'reviewed_by',
    ];

    protected $casts = [
        'values' => 'array',
        'leadership_principles' => 'array',
        'decision_principles' => 'array',
        'compliance_requirements' => 'array',
        'strategic_priorities' => 'array',
        'industry_constraints' => 'array',
        'operating_regions' => 'array',
        'metadata' => 'array',
        'monthly_ai_budget_usd' => 'decimal:2',
        'last_reviewed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::creating(function (self $dna) {
            $dna->uuid ??= (string) Str::uuid();
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Build a compact constitutional context string for agent system prompts.
     * This is injected into every agent's system prompt via PromptBuilderService.
     */
    public function toConstitutionalContext(): string
    {
        $lines = [];

        if ($this->mission) {
            $lines[] = "MISSION: {$this->mission}";
        }
        if ($this->vision) {
            $lines[] = "VISION: {$this->vision}";
        }
        if (! empty($this->leadership_principles)) {
            $lines[] = 'LEADERSHIP PRINCIPLES: '.implode(' | ', $this->leadership_principles);
        }
        if (! empty($this->decision_principles)) {
            $lines[] = 'DECISION PRINCIPLES: '.implode(' | ', $this->decision_principles);
        }
        if ($this->risk_appetite) {
            $lines[] = "RISK APPETITE: {$this->risk_appetite}";
        }
        if (! empty($this->compliance_requirements)) {
            $lines[] = 'COMPLIANCE: '.implode(', ', $this->compliance_requirements);
        }

        return implode("\n", $lines);
    }
}
