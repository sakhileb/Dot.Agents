---
name: technical-debt-tracker
description: "Activate when tracking, scoring, or prioritizing technical debt on the Dot.Agents platform. Maintains debt scores, risk scores, and priority scores across architectural, code quality, security, and test coverage dimensions. Use when the user asks to 'track technical debt', 'score debt', 'prioritize refactoring', 'assess risk', 'find code smells', 'detect god classes', 'find duplicate logic', or 'find unused dependencies'."
license: MIT
metadata:
  author: dotagents
---

# Technical Debt Tracker

Systematic identification, scoring, and prioritization of technical debt across Dot.Agents. Ensures debt is visible, quantified, and addressed before it becomes a production liability.

## When to Activate

- Quarterly or monthly debt reviews
- Before planning a refactoring sprint
- When a codebase area is feeling hard to maintain
- When the user mentions "technical debt", "code smells", "refactor", or "cleanup"
- When new complexity is introduced without corresponding cleanup

---

## 1. Debt Categories & Scoring

### Debt Taxonomy

| Category | Description | Max Points |
|----------|-------------|------------|
| Architecture Debt | Layers violated, domain boundaries crossed | 30 |
| Code Quality Debt | God classes, complexity, duplication | 25 |
| Security Debt | Missing auth, weak isolation, unscanned input | 20 |
| Test Coverage Debt | Untested Actions, zero coverage paths | 15 |
| Documentation Debt | Missing specs, outdated docs, no PHPDoc | 10 |

**Total Debt Score: 0 (no debt) → 100 (critical debt)**

Scores are **debt burden** — higher means more debt. Target: < 20 overall.

---

## 2. Architecture Debt Detection

### Patterns to Flag

#### Layer Violations (5 points each)
```bash
# Business logic in controllers
grep -rn "Eloquent\|->save()\|->create(\|->update(" app/Http/Controllers/

# Business logic in Livewire components
grep -rn "->create(\|->save()\|->delete()" app/Livewire/

# Missing Actions (queries direct in routes/controllers)
grep -rn "DB::\|Model::" routes/
```

#### Missing Infrastructure (3 points each)
- Action class exists but no corresponding DTO → typed input missing
- Event fired but no Listener registered → side effects lost
- Job defined but never dispatched → dead code OR sync anti-pattern
- Model with no Policy → authorization gap

#### Domain Boundary Violations (4 points each)
- `Agents` Action importing from `Billing` namespace
- `Governance` Service calling `Livewire` components directly
- Circular dependency between Services

---

## 3. Code Quality Debt Detection

### God Classes
A class is a "God Class" if it exceeds:
- 300 lines → mild (2 points)
- 500 lines → moderate (4 points)
- 800+ lines → severe (8 points)

```bash
# Find large classes
find app/ -name "*.php" -exec wc -l {} + | sort -rn | head -30
```

### Massive Livewire Components
Livewire limit is 200 lines per `copilot-instructions.md`.
```bash
find app/Livewire -name "*.php" -exec wc -l {} + | sort -rn | awk '$1 > 200 {print}'
```

### Overly Complex Methods
Cyclomatic complexity > 10 indicates a method needs refactoring.
```bash
# Use PHPStan or a static analysis tool for complexity
vendor/bin/phpstan analyse app/ --level=5
```

### Duplicate Logic Detection
```bash
# Identical validation arrays in Form Requests
grep -A20 "public function rules()" app/Http/Requests/ | sort | uniq -d

# Copy-pasted organization scope
grep -rn "where('organization_id'" app/ | wc -l
# > 5 instances without a global scope = debt
```

### Unused Dependencies
```bash
composer unused
```
Flag any package installed but unused in application code.

### Long Parameter Lists (> 5 params = debt signal)
```bash
grep -rn "public function execute(" app/Actions/ | grep -E "\$[a-z]+, \$[a-z]+, \$[a-z]+, \$[a-z]+, \$[a-z]+"
```
More than 4 parameters → should use a DTO.

---

## 4. Security Debt Detection

### High-Risk Debt Items (5 points each)
```bash
# Actions without authorization
grep -rn "public function execute(" app/Actions/ -A5 | grep -v "Gate::authorize\|authorize("

# Livewire with no policy check
grep -rn "public function " app/Livewire/ -A3 | grep -v "authorize\|can("

# Queries on org-owned models without tenant scope
grep -rn "AgentDeployment::query()\|AgentTask::query()" app/ | grep -v "organization_id\|forOrganization"
```

### Medium-Risk Debt Items (2 points each)
- Missing rate limiting on any external-facing route
- `$request->all()` usage (should be `$request->validated()`)
- Missing CSRF on any state-changing form
- AI input not scanned for injection

---

## 5. Test Coverage Debt

### Scoring
- Untested Action class: 3 points each
- Untested Service class: 2 points each
- No tenant isolation test: 5 points
- No prompt injection test: 5 points
- Test using `Model::create()` directly (not factory): 1 point each

### Measurement
```bash
php artisan test --coverage --min=80
```
Every percentage below 80% overall = 1 debt point.

---

## 6. Debt Register

Maintain the debt register in `.github/DEBT_REGISTER.md`. Format:

```markdown
# Technical Debt Register — Dot.Agents

**Last Updated:** [DATE]
**Overall Debt Score:** [X]/100 [LOW/MEDIUM/HIGH/CRITICAL]

## Priority 1: Critical (fix this sprint)
| ID | Category | Description | File | Debt Points | Effort | Owner |
|----|----------|-------------|------|-------------|--------|-------|
| D-001 | Security | Action `DeployAgentAction` missing `Gate::authorize()` | app/Actions/Agents/DeployAgentAction.php | 5 | 30min | — |

## Priority 2: High (fix next sprint)
| ID | Category | Description | File | Debt Points | Effort | Owner |
|----|----------|-------------|------|-------------|--------|-------|

## Priority 3: Medium (backlog)
| ID | Category | Description | File | Debt Points | Effort | Owner |
|----|----------|-------------|------|-------------|--------|-------|

## Priority 4: Low (track)
| ID | Category | Description | File | Debt Points | Effort | Owner |
|----|----------|-------------|------|-------------|--------|-------|

## Debt Trend
| Date | Score | Status |
|------|-------|--------|
| [DATE] | [X] | [improving/stable/worsening] |
```

---

## 7. Refactoring Recommendations

### Cleaner Architecture
When an Action does too much, split it:
```
// BEFORE: DeployAndNotifyAgentAction (100+ lines)
// AFTER:
// → DeployAgentAction (creates deployment, fires event)
// → SendDeploymentNotificationJob (queued, handles notification)
```

### Reusable Services
When the same multi-step logic appears in 2+ Actions, extract to a Service:
```php
// BEFORE — same AI call pattern in 3 Actions
// AFTER — AgentExecutionService::run(AgentTaskData $data): AgentResult
```

### Reusable Traits
When the same Eloquent scope appears on 3+ models, extract to a trait:
```php
trait HasOrganizationScope
{
    protected static function bootHasOrganizationScope(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            if ($orgId = session('current_organization_id')) {
                $query->where('organization_id', $orgId);
            }
        });
    }
}
```

### Reusable Actions
When a sub-operation appears in multiple workflows, make it its own Action:
```
// ValidateTenantAccessAction — called by all multi-tenant Actions
// RecordAuditLogAction — called after any state change
// SendPlatformNotificationAction — called by all notification paths
```

---

## 8. Debt Report Output Format

```
## Technical Debt Report — [DATE]

### Debt Score: [X]/100 — [LOW/MEDIUM/HIGH/CRITICAL]

### Score Breakdown
| Category | Points | Max | Status |
|----------|--------|-----|--------|
| Architecture Debt | X | 30 | ✅/⚠️/🔴 |
| Code Quality Debt | X | 25 | ✅/⚠️/🔴 |
| Security Debt | X | 20 | ✅/⚠️/🔴 |
| Test Coverage Debt | X | 15 | ✅/⚠️/🔴 |
| Documentation Debt | X | 10 | ✅/⚠️/🔴 |

### Debt Trend
[DATE -2]: X → [DATE -1]: X → [DATE]: X ([improving/worsening])

### Top 5 Priority Items
1. [D-ID] [Category] — [Description] — [X pts] — [Effort] — [fix]
2. ...

### Refactoring Roadmap
Sprint 1: [items]
Sprint 2: [items]
Sprint 3: [items]
```
