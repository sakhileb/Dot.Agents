---
name: pull-request-reviewer
description: "Activate for reviewing any pull request, feature branch, refactor, or hotfix. Automatically checks coding standards, Laravel best practices, security vulnerabilities, performance risks, and test coverage. Use when the user asks to 'review this PR', 'check this branch', 'review my changes', 'audit this feature', or asks for a code review of any kind on the Dot.Agents platform."
license: MIT
metadata:
  author: dotagents
---

# Pull Request Reviewer

Automated AI code review for every PR on the Dot.Agents platform. Applies all six AI Code Review Board lenses from `copilot-instructions.md` before any merge.

## When to Activate

- Any new PR, feature addition, refactor, or hotfix
- When asked to "review code", "check changes", or "audit this PR"
- Before merging into `main` or any release branch

---

## Review Checklist

Run all sections for every PR. Each check must PASS before the PR is approved.

---

## 1. Coding Standards

### Laravel Pint
```bash
vendor/bin/pint --dirty --format agent
```
All files in the diff must pass Pint with zero violations.

### Naming Conventions
- [ ] Classes: `PascalCase`
- [ ] Methods: `camelCase`, verb-first (`deploy`, `createOrganization`, `detectDelusion`)
- [ ] Properties: `camelCase`, descriptive (`isConfidenceThresholdMet`, not `flag`)
- [ ] Routes: `kebab-case` slugs, named with dot notation (`agents.deployments.show`)
- [ ] Database columns: `snake_case`

### File Placement
- [ ] Business logic in `app/Actions/` or `app/Services/` — never in controllers or Livewire
- [ ] All user input through `app/Http/Requests/` Form Requests
- [ ] All typed input/output through `app/DTOs/`
- [ ] All Livewire components in `app/Livewire/`

---

## 2. Laravel Best Practices

### Eloquent & Queries
- [ ] No `SELECT *` — use explicit column lists or specific relations
- [ ] Eager load relations used in loops (`with()`)
- [ ] No raw SQL with user input — use Eloquent or query builder bindings
- [ ] No queries inside Blade templates or Livewire `render()`
- [ ] Use `$request->validated()` exclusively — never `$request->all()`

### Architecture
- [ ] Every state change fires a domain `Event`
- [ ] Events with side effects have a registered `Listener` (queued if heavy)
- [ ] Heavy operations dispatched as `Job` (AI calls, report generation, emails)
- [ ] No hardcoded config values — use `config()` or `env()` through config files

### Livewire-Specific
- [ ] Component under 200 lines
- [ ] Business logic delegated to Action class (not inline)
- [ ] `#[Computed]` for derived data
- [ ] `#[Validate]` on properties rather than `validate()` call
- [ ] `wire:key` set on all loop items
- [ ] `wire:loading` shown for all async actions

---

## 3. Security Issues

### Authorization
- [ ] Every Action starts with `Gate::authorize()` or `$this->authorize()`
- [ ] Corresponding Policy exists for every model touched
- [ ] No `skipAuthorization()` or `withoutGlobalScope()` in production code paths

### Multi-Tenancy
- [ ] Every query on org-owned resources includes `organization_id` scope
- [ ] No cross-tenant data leakage possible (check joins and eager loads)
- [ ] `OrganizationContextMiddleware` applied to all org-scoped routes

### AI Prompt Safety
- [ ] All user-supplied AI input passes through `AuditService::detectPromptInjection()`
- [ ] Injection attempts logged as `SecurityEvent` with type `prompt_injection`
- [ ] No raw user content concatenated directly into AI prompts

### Input Validation
- [ ] Form Request validates all input before it reaches Action layer
- [ ] File uploads validate MIME type, extension, and size
- [ ] No `eval()`, `shell_exec()`, `system()`, or `exec()` calls

---

## 4. Performance Risks

### Query Performance
- [ ] No N+1 queries (check `Model::preventLazyLoading()` violation logs)
- [ ] Queries on large tables use indexed columns in WHERE/ORDER BY
- [ ] Paginate large result sets — never `->get()` on unbounded collections
- [ ] Use `chunk()` or `cursor()` for bulk operations

### Livewire Hydration
- [ ] No large arrays or model collections in `wire:model` properties
- [ ] Use `#[Lazy]` on components not visible on page load
- [ ] Avoid unnecessary full-page Livewire refreshes

### Caching
- [ ] Expensive repeated reads use `Cache::remember()`
- [ ] AI model responses cached where result is deterministic
- [ ] `Cache::lock()` used for concurrent-write scenarios

---

## 5. Test Coverage

### Requirements
- [ ] Every new Action class has a corresponding Feature test in `tests/Feature/Actions/`
- [ ] Tests cover: happy path, failure/exception path, authorization rejection
- [ ] Factories used — never `Model::create()` in tests
- [ ] `RefreshDatabase` or database transactions isolate test state
- [ ] No `dd()`, `dump()`, or `var_dump()` left in production code

### Running Tests
```bash
php artisan test --compact
```
Zero failures required before merge approval.

---

## 6. PR Review Output Format

After completing all checks, output a structured review:

```
## PR Review — [Branch Name]

### Verdict: ✅ APPROVED | ⚠️ CHANGES REQUESTED | 🔴 BLOCKED

### Scores
| Dimension | Score | Status |
|-----------|-------|--------|
| Coding Standards | /100 | |
| Laravel Best Practices | /100 | |
| Security | /100 | |
| Performance | /100 | |
| Test Coverage | /100 | |

### Critical Blockers (must fix before merge)
1. [issue] → [file:line] → [fix]

### Required Changes
1. [issue] → [file:line] → [fix]

### Suggestions (non-blocking)
1. [improvement opportunity]

### Commendations
- [things done well]
```

**Minimum score to approve: 90/100 on all dimensions.**
**Any Critical Blocker = automatic BLOCKED status.**
