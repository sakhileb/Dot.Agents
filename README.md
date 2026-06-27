<div align="center">

<img src="public/dot.logos3.png" alt="Dot.Agents" width="300" />

<h1>Dot.Agents</h1>

<p>Enterprise AI Workforce Platform — hire, deploy, manage, monitor, and govern specialised AI agents as digital workforce members.</p>

[![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php&logoColor=white)](https://php.net)
[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![Livewire](https://img.shields.io/badge/Livewire-3.x-4E56A6?style=flat-square)](https://livewire.laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white)](https://postgresql.org)
[![Tests](https://img.shields.io/badge/tests-776%20passing-brightgreen?style=flat-square)](tests/)
[![License](https://img.shields.io/badge/license-MIT-green?style=flat-square)](LICENSE)

</div>

---

## Overview

Dot.Agents is a **production-grade, multi-tenant AI Workforce Operating System**. Organisations hire specialised AI agents from a marketplace, configure their autonomy mode, equip them with skills, and govern every action through a structured approval and audit layer.

This is not a chatbot wrapper — it is a full AI workforce platform with agent memory, skill execution pipelines, multi-agent orchestration, a visual workflow builder, and a governed approval queue.

---

## Core Capabilities

### Agent Lifecycle Management
Deploy agents with one action. Configure autonomy mode, confidence thresholds, custom instructions, and skills per deployment.

| Mode | Behaviour |
|---|---|
| `advisory` | Agent suggests — human decides |
| `semi-autonomous` | Agent acts on high-confidence tasks, escalates the rest |
| `autonomous` | Agent executes independently within defined boundaries |
| `executive_approval` | Every action requires explicit executive sign-off |

### Agent Marketplace
A curated catalogue of specialised agent types — installed via the plugin system without code changes.

### Skill Execution Pipeline
Composable skills registered in `SkillRegistry` and executed through `SkillExecutionPipeline`. Assigned per agent, scoped per organisation.

### Visual Workflow Builder
Node-based workflow graph builder for multi-agent workflows. Stored as directed graphs and executed by `GraphWorkflowEngineService`.

### Multi-Agent Orchestration
`AgentOrchestrationService` coordinates agent chains — routing context, managing token budgets, and propagating results while preserving tenant isolation.

### AI Governance Layer
Every agent action is subject to confidence scoring, delusion detection, and audit logging. A Digital Immune System flags anomalous behaviour for review.

---

## Tech Stack

| Layer | Technology |
|---|---|
| Framework | Laravel 12 + PHP 8.4 |
| Frontend | Livewire 3 + Alpine.js + Tailwind CSS 4 |
| Auth | Jetstream 5 + Sanctum (ecosystem SSO) |
| Database | PostgreSQL 16 (shared infodot instance) |
| AI | Anthropic Claude API |
| WebSockets | Laravel Reverb |
| Search | Laravel Scout |

---

## Quick Start

```bash
git clone https://github.com/sakhileb/Dot.Agents.git && cd Dot.Agents
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate && npm run dev & php artisan serve
```

```bash
bash bin/test.sh   # 776 tests passing
```

---

## Part of the Dot Ecosystem

Dot.Agents connects to [InfoDot](https://github.com/sakhileb/InfoDot) — the central hub. Log in to InfoDot once and navigate here without re-authenticating via `/auth/ecosystem`.

---

MIT — © SK Digital / BluPin Incorporated
