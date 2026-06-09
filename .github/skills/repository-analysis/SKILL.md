---
name: repository-analysis
description: "Activate for any task involving repository-wide analysis: project architecture review, technical debt detection, duplicate code identification, dead code elimination, circular dependency analysis, or general codebase health assessment. Use when the user asks to 'analyze the codebase', 'find dead code', 'detect circular dependencies', 'assess technical debt', or wants a structural overview of the Dot.Agents platform."
license: MIT
metadata:
  author: dotagents
---

# Repository Analysis

AI Engineering Intelligence Layer — understands the full structure of the Dot.Agents platform to identify weaknesses, duplication, and architectural drift before they become problems.

## When to Activate

- User asks for a codebase analysis, architecture overview, or health check
- User mentions technical debt, dead code, duplicate logic, or circular dependencies
- Pre-refactoring reviews to understand scope
- Onboarding a new feature domain and needing context on existing patterns

---

## 1. Project Architecture Analysis

### Goal
Understand domain boundaries, layer separation, and adherence to the enterprise directory structure defined in `copilot-instructions.md`.

### Steps

1. Map all top-level directories in `app/` against the canonical structure:
   ```
   app/Actions/ · app/DTOs/ · app/Events/ · app/Listeners/
   app/Jobs/ · app/Policies/ · app/Services/ · app/Livewire/
   app/Models/ · app/Http/Requests/ · app/Http/Controllers/
   ```
2. Identify any business logic placed in forbidden zones:
   - Controllers (should only call Actions)
   - Livewire components (should delegate to Actions)
   - Blade templates (display only)
   - Models (Eloquent relations OK; business rules → Services/Actions)
3. Verify domain separation: `Agents/`, `Organizations/`, `Governance/`, `Billing/` sub-namespaces exist in Actions, DTOs, and Services.
4. Report missing domain layers (e.g., an Action domain with no corresponding DTO).

### Output Format
```
Architecture Score: [0-100]
✅ Correct: [list]
⚠️  Drift detected: [list with file paths]
🔴 Violations: [list with file paths and rule broken]
```

---

## 2. Technical Debt Detection

### Patterns to Flag

| Debt Type | Detection Signal |
|-----------|-----------------|
| God class | Class > 300 lines OR > 15 public methods |
| Massive Livewire component | Component > 200 lines |
| Missing Form Request | `$request->all()` or `$request->input()` in controllers |
| Inline validation | `$request->validate([])` directly in controller method |
| Missing Policy | Model with no corresponding Policy in `app/Policies/` |
| Missing Event | State-changing Action with no `event()` call |
| Missing DTO | Action accepting raw arrays instead of typed DTOs |
| Hardcoded org ID | `organization_id = 1` or similar literal |
| Missing tenant scope | Query on org-owned model without `organization_id` filter |

### How to Search
```bash
# God classes
find app/ -name "*.php" -exec wc -l {} + | sort -rn | head -20

# Missing policies
php artisan tinker --execute="echo implode(PHP_EOL, array_keys(app('gate')->policies()));"

# Raw request usage
grep -rn "request->all()\|request->input(" app/Http/Controllers/ app/Livewire/
```

---

## 3. Duplicate Code Detection

### Approach
1. Look for identical method bodies across Action classes in the same domain.
2. Look for repeated Eloquent scopes that should be global scopes or traits.
3. Look for copy-pasted validation rule arrays across Form Requests in the same domain.
4. Look for repeated `organization_id` filter logic that should be a global scope.

### Remediation Pattern
```php
// BEFORE — duplicated tenant filter in 3 actions
AgentDeployment::where('organization_id', $orgId)->...

// AFTER — global scope on model
class AgentDeployment extends Model
{
    protected static function booted(): void
    {
        static::addGlobalScope('organization', function (Builder $query) {
            if ($orgId = session('current_organization_id')) {
                $query->where('organization_id', $orgId);
            }
        });
    }
}
```

---

## 4. Dead Code Detection

### Signals
- Public methods in Service/Action classes never called by any controller, Livewire component, or Job
- Events defined in `app/Events/` with no registered Listener in `EventServiceProvider`
- Jobs defined in `app/Jobs/` never dispatched anywhere
- Policies registered but the corresponding model no longer exists
- Routes in `routes/web.php` or `routes/api.php` pointing to non-existent controllers

### Verification
```bash
# Unregistered events (no listener)
grep -rn "class.*Event" app/Events/ | awk -F'class ' '{print $2}' | awk '{print $1}'
# Then compare against EventServiceProvider $listen array

# Orphaned jobs (never dispatched)
grep -rn "class.*Job" app/Jobs/ | awk -F'class ' '{print $2}' | awk '{print $1}' > /tmp/jobs.txt
grep -rn "dispatch\|dispatchSync\|onQueue" app/ | grep -Ff /tmp/jobs.txt
```

---

## 5. Circular Dependency Detection

### High-Risk Patterns in Dot.Agents
- Service A injected into Service B, and Service B injected into Service A
- Action class constructing another Action class that constructs the original
- Livewire component dispatching an event that triggers a Listener that calls a Livewire method

### Detection
```bash
# Check service constructor injections
grep -rn "__construct" app/Services/ app/Actions/ | grep -v "//\|*"
```

Use Laravel's container to dump the full resolution graph:
```php
// In tinker
$container = app();
// Inspect bindings for circular resolution errors
```

---

## Reporting Standard

All analysis output must follow this structure:
```
## Repository Analysis Report — [DATE]

### Summary Scores
| Dimension | Score | Status |
|-----------|-------|--------|
| Architecture Compliance | /100 | |
| Technical Debt Level | /100 | |
| Code Duplication | /100 | |
| Dead Code | /100 | |
| Dependency Health | /100 | |

### Critical Issues (fix immediately)
1. [issue] → [file:line] → [recommended fix]

### Warnings (address in next sprint)
1. [issue] → [file:line] → [recommended fix]

### Recommendations
- [strategic improvement]
```
