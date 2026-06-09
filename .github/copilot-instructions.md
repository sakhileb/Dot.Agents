# Dot.Agents — Enterprise AI Workforce Platform
# Laravel Boost Governed Development Instructions

## Platform Identity

Dot.Agents is an **AI-Governed Enterprise Software Factory** where companies hire, deploy, manage, monitor, and govern specialized AI Agents as digital workforce members. This is not a standard Laravel application — it is a **production-grade, multi-tenant, enterprise AI platform** capable of supporting thousands of enterprise customers and millions of users.

**Laravel Boost is the authoritative framework intelligence layer.** All code must pass Boost validation before being accepted.

---

## TECHNOLOGY STACK

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 12.x |
| Auth | Jetstream + Livewire + Teams | 5.5 |
| Components | Livewire | 3.8 |
| CSS | Tailwind CSS | 4.x |
| AI Layer | OpenAI PHP + Prism PHP | latest |
| RBAC | Spatie Laravel Permission | 8.x |
| DB (dev) | SQLite | — |
| DB (prod) | MySQL 8+ | — |
| Testing | PHPUnit | 11.x |
| Code Style | Laravel Pint | latest |
| Intelligence | Laravel Boost MCP | 2.x |

**Brand Colors:** Yellow `#f5be1c` · Purple `#3d2ea0`

---

## LARAVEL BOOST ENFORCEMENT LAYER

### Core Principle
Every feature must pass through the **AI Code Review Board** before implementation. Laravel Boost MCP tools are the authority — use them before all manual approaches.

### Mandatory Before Every Change
1. `search-docs` — verify version-specific API
2. `database-schema` — understand existing structure before migrations
3. `database-query` — inspect data before writing queries
4. `get-absolute-url` — resolve correct URL before sharing
5. `browser-logs` — read errors before debugging manually

### Boost Validation Checkpoints (ALL must score ≥ 90/100)
| Dimension | What It Measures |
|-----------|-----------------|
| Architecture Score | Domain boundaries, service layers, SRP |
| Security Score | AuthZ, AuthN, tenant isolation, injection prevention |
| Performance Score | Query count, N+1, cache usage, Livewire hydration |
| Scalability Score | Queue usage, async patterns, stateless design |
| Maintainability Score | Readability, single responsibility, test coverage |
| UX Score | Accessibility ≥ 95, mobile optimization ≥ 95 |
| Production Readiness Score | Error handling, logging, monitoring |

---

## ENTERPRISE ARCHITECTURE STANDARDS

### Mandatory Directory Structure
```
app/
├── Actions/           ← Single-purpose action classes (REQUIRED for all writes)
│   ├── Agents/
│   ├── Organizations/
│   ├── Governance/
│   └── Billing/
├── DTOs/              ← Data Transfer Objects (typed input/output)
│   ├── Agents/
│   ├── Organizations/
│   └── Governance/
├── Events/            ← Domain events (fired after every state change)
├── Listeners/         ← Event handlers (MUST be queued for heavy operations)
├── Jobs/              ← Queued background work
├── Policies/          ← Authorization (EVERY model needs a Policy)
├── Services/          ← Stateless business services (existing structure)
│   ├── AI/
│   └── Governance/
├── Http/
│   ├── Requests/      ← Form Requests (ALL user input must use Form Requests)
│   └── Middleware/
└── Livewire/          ← Components only — NO business logic
```

### Strict No-Business-Logic Zones
**NEVER put business logic in:**
- Controllers (pass to Actions only)
- Livewire Components (delegate to Actions)
- Blade templates (display only)
- Models (Eloquent queries allowed; business rules → Services/Actions)

### Business Logic MUST live in:
- `app/Actions/` — single-use operations
- `app/Services/` — reusable stateless services
- `app/Jobs/` — async/queued work

---

## AI CODE REVIEW BOARD

Before implementing any feature, mentally apply all six reviewer lenses:

### 1. Principal Laravel Architect
- Are domain boundaries clean? No cross-domain logic leakage?
- Is the service layer properly separated from the presentation layer?
- Are Events fired after every significant state transition?
- Is multi-tenancy enforced at the model/query level via `organization_id` scopes?

### 2. Livewire Specialist
- Is each component single-responsibility?
- Are computed properties (`#[Computed]`) used instead of re-querying?
- Is lazy loading (`#[Lazy]`) applied to expensive components?
- Are Form Objects used for complex form state?
- Is hydration minimized (no massive JSON payloads in wire:model)?
- Business logic delegated to Action classes, NOT inline?

### 3. Tailwind Design Lead
- Mobile-first responsive? (`sm:`, `md:`, `lg:`, `xl:` breakpoints used properly)
- Dark mode applied? (all backgrounds, texts, borders have `dark:` variants)
- Brand tokens used? (`brand-yellow`, `brand-purple` from Tailwind config)
- Accessibility: `aria-*` labels, keyboard navigation, color contrast ≥ 4.5:1?
- Component-driven: reusing existing Blade components before creating new ones?

### 4. Security Architect
- Is `authorize()` called at the start of every action?
- Are all Policies registered in `AuthServiceProvider`?
- Is tenant isolation enforced? (every query scoped to `organization_id`)
- Is user input validated via Form Requests before touching data?
- Is prompt injection detection running on ALL user-supplied AI input?
- No raw SQL with user input? Use Eloquent or `DB::select()` with bindings?

### 5. DevOps Architect
- Are expensive operations (AI calls, report generation) dispatched as Jobs?
- Is the queue configured for at-least-once delivery semantics?
- Are rate limits applied to AI-facing endpoints?
- Are sensitive config values read from `.env`, never hardcoded?
- Is the `digital_immune_system` running on a schedule?

### 6. QA Director
- Does every Action class have a PHPUnit Feature test?
- Do tests cover happy path, failure path, and authorization?
- Are factories used — never direct DB writes in tests?
- Is the test database isolated (SQLite in-memory or transactions)?

---

## ENTERPRISE DEVELOPMENT WORKFLOW

Every feature request MUST follow this 13-step workflow:

```
STEP 1  → Analyze requirements and identify domain
STEP 2  → Architecture design (domain, services, events)
STEP 3  → Database schema (use database-schema MCP tool first)
STEP 4  → Generate migration (php artisan make:migration)
STEP 5  → Generate model + factory (php artisan make:model --factory)
STEP 6  → Generate Policy (php artisan make:policy --model)
STEP 7  → Generate Form Request (php artisan make:request)
STEP 8  → Generate Action class (php artisan make:class app/Actions/...)
STEP 9  → Generate Events + Listeners (php artisan make:event / make:listener)
STEP 10 → Generate Job if async (php artisan make:job)
STEP 11 → Generate Livewire component (php artisan make:livewire)
STEP 12 → Write PHPUnit tests (php artisan make:test)
STEP 13 → Run Pint (vendor/bin/pint --dirty --format agent) + run tests
```

**No step may be skipped. No code merged without step 13 passing.**

---

## BACKEND STANDARDS

### Action Classes
```php
// CORRECT — Action class pattern
namespace App\Actions\Agents;

use App\DTOs\Agents\DeployAgentData;
use App\Models\AgentDeployment;
use App\Events\AgentDeployed;

class DeployAgentAction
{
    public function execute(DeployAgentData $data): AgentDeployment
    {
        // 1. Authorize
        Gate::authorize('deploy', [$data->agent, $data->organization]);

        // 2. Create
        $deployment = AgentDeployment::create($data->toArray());

        // 3. Fire event
        event(new AgentDeployed($deployment));

        return $deployment;
    }
}
```

### DTOs
```php
// CORRECT — Typed DTO
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
        return new self(...$data);
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
```

### Events
```php
// Fire events after every significant state change
event(new AgentDeployed($deployment));
event(new TaskCompleted($task));
event(new ApprovalRequired($approval));
event(new SecurityEventDetected($event));
```

### Form Requests
```php
// ALL user input must pass through a Form Request
// Authorize in authorize(), validate in rules()
// Transform input in passedValidation() before returning to action
```

---

## LIVEWIRE 3 STANDARDS

### Component Rules
- Max 200 lines per component (enforce single responsibility)
- Use `#[Computed]` attribute for derived data (avoids re-querying)
- Use `#[Lazy]` for components not visible on page load
- Use `#[Validate]` rules on properties instead of `validate()` calls
- Use Livewire Form Objects (`extends Form`) for multi-field forms

### Forbidden Patterns
```php
// ❌ WRONG — Business logic in Livewire
public function deploy(): void
{
    AgentDeployment::create([...]); // NEVER
}

// ✅ CORRECT — Delegate to Action
public function deploy(): void
{
    $this->authorize('deploy-agents');
    app(DeployAgentAction::class)->execute(
        DeployAgentData::fromRequest($this->form->toArray())
    );
    $this->dispatch('agent-deployed');
}
```

---

## JETSTREAM TEAM / MULTI-TENANT ARCHITECTURE

### Organization Hierarchy
```
Organization (maps to Jetstream Team)
├── Divisions
├── Departments
├── Teams (sub-groups)
├── Users (via organization_user pivot)
└── AgentDeployments (scoped to org)
```

### Tenant Isolation Rules
- EVERY query against org-owned resources MUST include `->where('organization_id', $orgId)`
- Use the `OrganizationContextMiddleware` which sets `session('current_organization_id')`
- Never expose data from one org to another — tested via PHPUnit isolation tests
- Global scopes on models like `AgentDeployment`, `AgentTask`, `AuditLog` should auto-scope by default when org context is active

---

## SECURITY STANDARDS

### Authentication
- All platform routes protected by `auth:sanctum` + `verified` + `org.context` middleware
- Passkey support via Jetstream

### Authorization
- Every Action calls `Gate::authorize()` or `$this->authorize()` first
- Policies registered for ALL models with agent deployment operations
- Spatie permissions used for role-based feature access

### AI Security
- ALL user input to AI agents MUST pass through `AuditService::detectPromptInjection()`
- Flag and refuse requests scoring above injection threshold
- Log all prompt injection attempts as `SecurityEvent` with type `prompt_injection`

### Data Protection
- No PII in logs
- Sensitive agent configs encrypted at rest
- API keys loaded from `.env` only

---

## TAILWIND 4 + DESIGN SYSTEM

### Brand Tokens (defined in tailwind.config.js)
```
brand-yellow: #f5be1c
brand-yellow-light: #fde989
brand-purple: #3d2ea0
brand-purple-mid: #5b48c8
```

### Required for Every UI Component
- Mobile-first classes applied (`sm:`, `md:`, `lg:`)
- Dark mode variants (`dark:bg-*`, `dark:text-*`, `dark:border-*`)
- ARIA attributes (`aria-label`, `role`, `aria-expanded` for modals/dropdowns)
- Loading states for all async actions (`wire:loading`)
- Empty states for all lists (never blank screens)

---

## TESTING STANDARDS (PHPUnit 11)

### Test Structure
```
tests/
├── Feature/
│   ├── Actions/          ← One test per Action class
│   ├── Governance/       ← AuditLog, ApprovalQueue, Delusion detection
│   ├── Security/         ← Tenant isolation, prompt injection
│   └── Billing/          ← Subscription, usage tracking
└── Unit/
    ├── Services/         ← Service class unit tests
    └── DTOs/             ← DTO validation tests
```

### Test Requirements
- Every Action class → Feature test
- Every Governance service → Feature test
- Tenant isolation → dedicated Security test class
- Prompt injection → Security test
- Use `RefreshDatabase` trait
- Use factories, never `Model::create()` directly in tests

### Running Tests
```bash
php artisan test --compact                               # All tests
php artisan test --compact tests/Feature/Actions/       # Actions only
php artisan test --compact --filter=DeployAgent         # Single action
vendor/bin/pint --dirty --format agent                  # Format after changes
```

---

## SELF-IMPROVING ENGINEERING SYSTEM

The platform records and learns from:
- Failed deployments → `agent_deployments.status = 'failed'`  
- Security incidents → `security_events` table
- Performance bottlenecks → `agent_tasks.latency_ms` outliers
- Testing failures → stored in `platform_notifications`
- Agent errors → `decision_logs.delusion_risk_score > 60`

The `DigitalImmuneSystem` service runs on a schedule to auto-remediate and improve agent reliability. The `ScorecardService` tracks all 10 performance dimensions per agent per period.

---

## DOMAIN GLOSSARY

| Term | Meaning |
|------|---------|
| Organization | A company using the platform (maps to Jetstream Team) |
| AgentDeployment | An instance of an Agent hired by an Organization |
| DelusionRisk | Score (0-100) measuring AI hallucination/confabulation likelihood |
| DIS | Digital Immune System — automated threat/drift detection |
| Confidence Threshold | Minimum AI confidence below which human approval is required |
| Deployment Mode | advisory / semi-autonomous / autonomous / executive_approval |
| Scorecard | 10-dimension health score (0-100) per agent per time period |

---

## QUICK REFERENCE: ARTISAN COMMANDS

```bash
# Architecture
php artisan make:class app/Actions/Agents/DeployAgentAction
php artisan make:class app/DTOs/Agents/DeployAgentData
php artisan make:event AgentDeployed
php artisan make:listener HandleAgentDeployed --event=AgentDeployed
php artisan make:job ProcessAgentTask
php artisan make:policy AgentDeploymentPolicy --model=AgentDeployment
php artisan make:request DeployAgentRequest

# Testing
php artisan make:test Feature/Actions/DeployAgentActionTest
php artisan test --compact --filter=DeployAgent

# Code quality
vendor/bin/pint --dirty --format agent
```

## SKILLS AVAILABLE

All domain skills are in `.github/skills/`. Activate the relevant skill before working in that domain:

| Skill | Domain |
|-------|--------|
| `laravel-best-practices` | Architecture, Eloquent, routing, validation |
| `livewire-development` | Livewire 3 components, forms, computed properties |
| `tailwindcss-development` | UI, dark mode, responsive design |
| `fortify-development` | Auth, 2FA, passkeys |
| `dotagents-architecture` | Platform-specific patterns, tenant isolation |
| `dotagents-governance` | AI governance, audit logs, delusion detection |
| `dotagents-agents` | AI agent orchestration, memory, scoring |
| `repository-analysis` | Codebase health, technical debt, dead code, circular deps |
| `pull-request-reviewer` | PR review: standards, security, performance, test coverage |
| `laravel-architecture-reviewer` | Service layer, Livewire structure, Jetstream, DTOs, enterprise readiness |
| `agent-quality-auditor` | Agent contracts, governance hooks, interoperability, performance |
| `security-auditor` | OWASP Top 10, tenant isolation, prompt injection, secrets, CVEs |
| `test-coverage-analyzer` | Coverage gaps, test generation (Feature, Unit, Security) |
| `documentation-generator` | API docs, agent specs, workflow diagrams, DB schema docs |
| `technical-debt-tracker` | Debt scoring, code smells, refactoring recommendations |
| `cicd-intelligence` | GitHub Actions, deployment workflows, Docker, queue config |
| `engineering-ceo` | Platform health score (0–100) across all dimensions |
