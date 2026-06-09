---
name: laravel-architecture-reviewer
description: "Activate when validating or auditing the Laravel architecture of Dot.Agents. Covers service layer architecture, Livewire component structure, Jetstream multi-tenancy implementation, repository patterns, DTO usage, Action class compliance, and enterprise readiness (scalability, multi-tenancy, security, maintainability). Use when the user asks to 'validate the architecture', 'review the service layer', 'check Jetstream setup', 'validate DTOs', or requests an enterprise readiness review."
license: MIT
metadata:
  author: dotagents
---

# Laravel Architecture Reviewer

Validates Dot.Agents against enterprise Laravel architecture standards. Covers service layer design, Livewire structure, Jetstream multi-tenancy, repository patterns, DTO correctness, and four enterprise readiness dimensions.

## When to Activate

- Architectural reviews before major releases
- New domain additions (e.g., adding a new `Billing` or `Reporting` domain)
- Onboarding reviews to verify standards compliance
- Any task mentioning "architecture", "service layer", "Jetstream", "DTOs", or "repository pattern"

---

## 1. Service Layer Architecture

### Correct Layer Responsibilities

| Layer | Responsibility | Wrong If... |
|-------|---------------|-------------|
| `app/Http/Controllers/` | Receive request, call Action/Service, return response | Contains business logic, queries, or conditional branching |
| `app/Actions/` | Single-use operation (one public `execute()` method) | Contains multiple unrelated operations or UI logic |
| `app/Services/` | Reusable stateless business services | Holds state, contains Eloquent model mutations |
| `app/DTOs/` | Typed, immutable input/output | Mutable, or contains business logic |
| `app/Events/` | Describe what happened | Contains side effects or calls to services |
| `app/Listeners/` | React to events | Performs synchronous heavy work (must be queued) |
| `app/Jobs/` | Queued async work | Called synchronously for time-sensitive operations |

### Validation Checks
- [ ] Every Action class has exactly ONE public method: `execute()`
- [ ] Actions accept DTOs as typed parameters — not raw arrays or `Request` objects
- [ ] Services are stateless (no instance properties mutated between calls)
- [ ] Listeners implement `ShouldQueue` for any operation >50ms
- [ ] Controllers contain no `if`/`switch` business logic — delegate entirely

---

## 2. Livewire Component Structure

### Single-Responsibility Check
Each Livewire component must do ONE thing. Audit every component for:
- [ ] Under 200 lines (hard limit per `copilot-instructions.md`)
- [ ] One domain concern (e.g., `CreateAgentDeployment` not `ManageAgentsDashboard`)
- [ ] No inline database queries — use `#[Computed]` with eager loading
- [ ] Business logic delegated to Action class
- [ ] Form state in a `Form` object (extends `Livewire\Form`) for >3 fields

### Component Inventory Command
```bash
find app/Livewire -name "*.php" -exec wc -l {} + | sort -rn
```
Flag any component over 200 lines for immediate refactor.

### Livewire Validation Pattern (Correct)
```php
// ✅ CORRECT
#[Validate('required|string|min:3')]
public string $name = '';

// ❌ WRONG — inline validate() call
public function save(): void
{
    $this->validate(['name' => 'required|string|min:3']);
}
```

---

## 3. Jetstream Implementation

### Organization (Team) Architecture
Dot.Agents maps `Organization` → Jetstream `Team`. Validate:

- [ ] `Organization` model extends or uses Jetstream's team structure
- [ ] `current_team_id` session key maps to `current_organization_id`
- [ ] `OrganizationContextMiddleware` sets `current_organization_id` in session for all authenticated routes
- [ ] Role assignment uses Spatie permissions, NOT Jetstream's built-in roles (Spatie is the RBAC authority)
- [ ] Team switching triggers `OrganizationSwitched` event (audited)

### Middleware Stack Audit
```php
// routes/web.php — all org-scoped routes must have:
Route::middleware(['auth', 'verified', 'org.context'])->group(function () {
    // All agent/governance/billing routes here
});
```

### User–Organization Pivot
- [ ] `organization_user` pivot table has `role` column (or deferred to Spatie)
- [ ] Users can belong to multiple organizations
- [ ] `current_organization_id` changes on org switch

---

## 4. Repository Patterns

### Dot.Agents Pattern Decision
This platform uses **Action classes + direct Eloquent** (no Repository interface pattern). This is intentional. Validate that:

- [ ] No `Repository` interfaces or classes exist (use Eloquent directly in Actions)
- [ ] Reusable query logic lives in **Model local scopes** or **global scopes**, not repositories
- [ ] Complex queries are encapsulated in the Model or a dedicated `QueryBuilder` class if needed

### Correct Scope Pattern
```php
// ✅ CORRECT — Local scope on Model
class AgentDeployment extends Model
{
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeForOrganization(Builder $query, int $orgId): Builder
    {
        return $query->where('organization_id', $orgId);
    }
}

// Usage in Action
AgentDeployment::active()->forOrganization($orgId)->get();
```

---

## 5. DTO Usage Validation

### DTO Requirements
Every DTO must be:
- [ ] `readonly` class (PHP 8.2+)
- [ ] In the correct domain namespace (`App\DTOs\Agents\`, `App\DTOs\Governance\`, etc.)
- [ ] Has a `fromRequest(array $data): self` static factory method
- [ ] Has a `toArray(): array` method
- [ ] Uses typed constructor properties — no `mixed` or `array` types for business fields

### DTO Correctness Example
```php
// ✅ CORRECT
namespace App\DTOs\Agents;

readonly class DeployAgentData
{
    public function __construct(
        public int $agentId,
        public int $organizationId,
        public string $name,
        public string $deploymentMode,
        public float $confidenceThreshold = 75.0,
        public ?string $customInstructions = null,
    ) {}

    public static function fromRequest(array $data): self
    {
        return new self(
            agentId: (int) $data['agent_id'],
            organizationId: (int) $data['organization_id'],
            name: $data['name'],
            deploymentMode: $data['deployment_mode'],
            confidenceThreshold: (float) ($data['confidence_threshold'] ?? 75.0),
            customInstructions: $data['custom_instructions'] ?? null,
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
```

---

## 6. Enterprise Readiness Review

### 6.1 Scalability Assessment
- [ ] All AI calls dispatched as queued Jobs (never synchronous in HTTP requests)
- [ ] Queue workers configured with appropriate concurrency in `config/queue.php`
- [ ] No synchronous `sleep()` or polling — use events and listeners
- [ ] Rate limiting on all AI-facing endpoints (`throttle:ai`)
- [ ] Database queries use pagination for list endpoints

**Score signal:** Sync AI call in a controller = 0/100. All AI async = 100/100.

### 6.2 Multi-Tenancy Assessment
- [ ] Global scopes or explicit `organization_id` on ALL org-owned models
- [ ] No query crosses tenant boundary (no `whereNull('organization_id')` on scoped models)
- [ ] Tests include a cross-tenant isolation test: User A cannot see User B's data
- [ ] Soft-delete considers tenant scope (deleted records still isolated)

**Score signal:** Any cross-tenant data leak = 0/100 (automatic failure).

### 6.3 Security Assessment
- [ ] `Gate::authorize()` at the start of every Action's `execute()` method
- [ ] All Policies registered in `AuthServiceProvider`
- [ ] `AuditService::detectPromptInjection()` called on all user AI input
- [ ] No hardcoded credentials or API keys in code (use `config()` referencing `.env`)
- [ ] CSRF protection on all state-changing routes
- [ ] Content-Security-Policy headers configured

### 6.4 Maintainability Assessment
- [ ] Every domain has consistent layer coverage: Action + DTO + Event + Test
- [ ] PHPDoc on all public Action `execute()` methods
- [ ] No feature over 400 total lines across all its files (Action + DTO + Event + Listener)
- [ ] Pint passes with zero violations
- [ ] Test suite coverage ≥ 80% (measured via `php artisan test --coverage`)

---

## Review Output Format

```
## Laravel Architecture Review — [Feature/Domain Name]

### Enterprise Readiness Scores
| Dimension | Score | Status |
|-----------|-------|--------|
| Service Layer Architecture | /100 | |
| Livewire Component Structure | /100 | |
| Jetstream Implementation | /100 | |
| DTO Usage | /100 | |
| Scalability | /100 | |
| Multi-Tenancy | /100 | |
| Security | /100 | |
| Maintainability | /100 | |

### Violations (must fix)
1. [violation] → [file:line] → [fix]

### Warnings (should fix)
1. [warning] → [file:line] → [fix]

### Architecture Recommendations
- [strategic recommendation]
```
