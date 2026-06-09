<div align="center">

<img src="dot.agents.png" alt="Dot.Agents" width="300" />

### Enterprise AI Workforce Platform

**Hire, deploy, manage, monitor, and govern specialized AI Agents as digital workforce members.**

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-4E56A6?style=flat-square)](https://livewire.laravel.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind-4.x-06B6D4?style=flat-square&logo=tailwindcss)](https://tailwindcss.com)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

</div>

---

## What is Dot.Agents?

Dot.Agents is a **production-grade, multi-tenant enterprise platform** that enables organizations to hire, configure, deploy, and govern AI Agents as first-class digital workforce members. Agents operate across four autonomy modes — from advisory to fully autonomous — with every decision logged, scored, and governable.

This is not a chatbot wrapper. It is a full **AI Workforce Operating System** with agent memory, skill execution pipelines, multi-agent orchestration, a visual workflow builder, a governed approval queue, delusion detection, and a self-improving Digital Immune System.

---

## Core Capabilities

### Agent Lifecycle Management
Deploy any agent from the marketplace to an organization with a single action. Configure autonomy mode, confidence thresholds, custom instructions, and active skills per deployment.

| Deployment Mode | Behavior |
|----------------|----------|
| `advisory` | Agent suggests — human decides |
| `semi-autonomous` | Agent acts on high-confidence tasks, escalates the rest |
| `autonomous` | Agent executes independently within defined boundaries |
| `executive_approval` | Every action requires explicit executive sign-off |

### Agent Marketplace
A curated catalog of specialized agent types — categorized by domain, rated by organizations, and installed via the plugin system. Organizations browse, preview, and deploy agents without any code changes.

### Agent Memory & Personas
Agents maintain persistent memory across sessions using the `MemoryService`. Each agent can carry a persona that shapes tone, reasoning style, and domain focus — fully configurable per deployment.

### Skill Execution Pipeline
Agents are equipped with composable skills — discrete capabilities registered in the `SkillRegistry` and executed through the `SkillExecutionPipeline`. Skills are assigned per agent and scoped per organization.

### Visual Workflow Builder
A node-based workflow graph builder (`WorkflowBuilder`) lets organizations design multi-agent workflows without code. Workflows are stored as directed graphs (`WorkflowNode`, `WorkflowConnection`) and executed by the `GraphWorkflowEngineService`.

### Multi-Agent Orchestration
The `AgentOrchestrationService` coordinates chains of agents — routing context, managing token budgets, and propagating results across the orchestration tree while preserving tenant isolation at every node.

---

## AI Governance Layer

Every agent action on the platform is subject to enterprise governance controls.

### Audit Logging
The `AuditService` records every state-changing action to `audit_logs` — capturing user, organization, resource, before/after state, and IP address. Nothing executes without a trace.

### Approval Workflows
When an agent's confidence score falls below the deployment's configured threshold, the task is paused and an `ApprovalRequested` event is fired. The `ApprovalQueue` UI surfaces pending escalations for human review. Execution resumes only after explicit approval or rejection.

### Delusion Detection
The `DelusionDetectionService` scores every agent output (0–100) for hallucination and confabulation risk. Scores above 60 trigger `AgentDriftDetected` events. Scores above 80 automatically pause the deployment.

### Digital Immune System
The `DigitalImmuneSystem` service runs on a schedule, scanning for security anomalies, performance degradations, and behavioural drift across all active agent deployments. Issues are surfaced as `SecurityEvent` records and `PlatformNotification` alerts.

### Prompt Injection Protection
All user-supplied input destined for an AI model is scanned for injection patterns before execution. Detected injection attempts are refused and logged as `SecurityEvent` records with type `prompt_injection`.

### Scorecard System
The `ScorecardService` maintains a 10-dimension health score per agent per time period — tracking accuracy, speed, cost, reliability, safety, compliance, communication, adaptability, collaboration, and innovation. Scores are updated after every task via the `UpdateScorecardOnTaskComplete` listener.

---

## Multi-Tenant Architecture

Every resource on the platform is scoped to an `Organization` (backed by Jetstream Teams). The platform supports:

- Multiple organizations per user (with current-org context switching)
- Division → Department → Team hierarchy within organizations
- Role-based access control via Spatie Laravel Permission
- Global query scopes enforcing `organization_id` isolation on all org-owned models
- Full audit trail per organization

---

## Technology Stack

| Layer | Technology | Version |
|-------|-----------|---------|
| Backend | Laravel | 12.x |
| Auth | Jetstream + Livewire + Teams | 5.5 |
| Reactive UI | Livewire | 3.x |
| CSS | Tailwind CSS | 4.x |
| AI Layer | OpenAI PHP + Prism PHP | latest |
| RBAC | Spatie Laravel Permission | 8.x |
| Testing | PHPUnit | 11.x |
| Code Style | Laravel Pint | latest |
| Intelligence | Laravel Boost MCP | 2.x |
| DB (dev) | SQLite | — |
| DB (prod) | MySQL 8+ | — |

**Brand:** Yellow `#f5be1c` · Purple `#3d2ea0`

---

## Project Structure

```
app/
├── Actions/           # Single-purpose operation classes (one execute() method)
│   ├── Agents/        # Deploy, pause, update, decommission agent deployments
│   ├── Billing/       # Subscription, invoicing, usage metering
│   ├── Fortify/       # Auth: register, update profile, reset password
│   ├── Governance/    # Approval processing, audit recording
│   ├── Jetstream/     # Team creation, member management
│   └── Organizations/ # Org creation, member invitations
├── DTOs/              # Typed readonly input/output objects
├── Events/            # Domain events fired after every state change
├── Listeners/         # Queued event handlers
├── Jobs/              # Background work: AI execution, scoring, DIS, notifications
├── Livewire/          # UI components (no business logic)
│   ├── Agents/        # Chat interface, deployment manager, scorecard viewer
│   ├── Billing/       # Subscription management
│   ├── Dashboard/     # Agent dashboard
│   ├── Governance/    # Approval queue, audit log viewer
│   ├── Marketplace/   # Agent marketplace browser
│   ├── Organizations/ # Org management
│   ├── Security/      # Security event monitor
│   └── Workflows/     # Visual workflow builder
├── Models/            # Eloquent models (37 models across all domains)
├── Policies/          # Authorization policies (one per model)
└── Services/
    ├── AI/            # Orchestration, memory, model routing, skill pipeline, workflows
    └── Governance/    # Audit, delusion detection, DIS, scorecard
```

---

## Getting Started

### Requirements

- PHP 8.4+
- Composer 2+
- Node.js 20+
- SQLite (development) or MySQL 8+ (production)

### Installation

```bash
# Clone the repository
git clone https://github.com/sakhileb/Dot.Agents.git
cd Dot.Agents

# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Build assets
npm run build

# Start development server
composer run dev
```

### Required Environment Variables

```env
APP_NAME="Dot.Agents"
APP_URL=http://localhost

# AI Providers (at least one required)
OPENAI_API_KEY=
ANTHROPIC_API_KEY=

# Queue (Redis recommended for production)
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

---

## Development Workflow

Every feature follows a 13-step workflow defined in `.github/copilot-instructions.md`:

```
Migration → Model + Factory → Policy → Form Request → Action →
Event → Listener → Job (if async) → Livewire Component → Tests → Pint
```

### Running Tests

```bash
php artisan test --compact                        # Full suite
php artisan test --compact tests/Feature/Actions/ # Actions only
php artisan test --compact --coverage --min=80    # With coverage gate
```

### Code Style

```bash
vendor/bin/pint --dirty --format agent
```

---

## AI Engineering Intelligence

This repository uses a suite of GitHub Copilot skills that turn the AI assistant into an engineering intelligence layer. Skills auto-activate based on context:

| Skill | Domain |
|-------|--------|
| `repository-analysis` | Architecture health, dead code, circular deps |
| `pull-request-reviewer` | Automated PR review across all quality dimensions |
| `laravel-architecture-reviewer` | Service layer, Jetstream, DTOs, enterprise readiness |
| `agent-quality-auditor` | Agent contracts, governance, performance certification |
| `security-auditor` | OWASP Top 10, tenant isolation, prompt injection, CVEs |
| `test-coverage-analyzer` | Coverage gaps, test generation |
| `documentation-generator` | API docs, agent specs, architecture diagrams |
| `technical-debt-tracker` | Debt scoring, code smells, refactoring roadmaps |
| `cicd-intelligence` | GitHub Actions, Docker, deployment safety |
| `engineering-ceo` | Full platform health score (0–100) |

---

## Security

- All routes require authentication (`auth:sanctum`) and email verification
- Every Action class authorizes via `Gate::authorize()` before execution
- All org-owned resources are scoped to `organization_id` at the query level
- User-supplied AI input is scanned for prompt injection before execution
- No secrets are stored in code — all credentials loaded from environment variables
- `composer audit` and `npm audit` are run on every CI pipeline

To report a security vulnerability, please open a private security advisory on GitHub.

---

## License

Dot.Agents is open-sourced software licensed under the [MIT license](LICENSE).
