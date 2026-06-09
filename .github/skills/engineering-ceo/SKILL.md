---
name: engineering-ceo
description: "Activate for a comprehensive platform health assessment across ALL dimensions of Dot.Agents. This is the highest-level skill — it reviews the entire repository and produces a platform health score (0–100) covering architecture, security, scalability, maintainability, testability, documentation, and production readiness. Also tracks deployment success, production incidents, missing features, and architectural weaknesses. Use when the user asks for a 'platform review', 'health score', 'engineering review', 'platform assessment', 'CEO report', or 'overall status'."
license: MIT
metadata:
  author: dotagents
---

# Engineering CEO Agent

The highest-level platform intelligence on Dot.Agents. Reviews the entire repository through all seven dimensions and produces an authoritative Platform Health Score (0–100). Also identifies strategic opportunities, tracks architectural evolution, and maintains release intelligence.

## When to Activate

- Monthly or quarterly platform-wide reviews
- Before major version releases
- When the user asks for an overall platform status or health score
- When making strategic architectural decisions
- After major features are merged to assess platform maturity

---

## Activation Protocol

When this skill activates, invoke the other skills in sequence:

```
1. repository-analysis      → Architecture Score
2. laravel-architecture-reviewer → Laravel Architecture Score
3. security-auditor         → Security Score
4. test-coverage-analyzer   → Testability Score
5. technical-debt-tracker   → Maintainability Score
6. cicd-intelligence        → Production Readiness Score
7. documentation-generator  → Documentation Score (assess gaps)
8. agent-quality-auditor    → Agent Quality Score (platform differentiator)
```

Aggregate all subscores into the final Platform Health Score.

---

## 1. Platform Health Scorecard

### Dimension Weights

| Dimension | Weight | Basis Skill |
|-----------|--------|-------------|
| Architecture | 15% | `repository-analysis` + `laravel-architecture-reviewer` |
| Security | 20% | `security-auditor` |
| Scalability | 15% | `laravel-architecture-reviewer` (scalability section) |
| Maintainability | 15% | `technical-debt-tracker` |
| Testability | 15% | `test-coverage-analyzer` |
| Documentation | 5% | `documentation-generator` |
| Production Readiness | 10% | `cicd-intelligence` |
| Agent Quality | 5% | `agent-quality-auditor` |

**Platform Health Score = weighted average of all dimensions (0–100)**

### Score Interpretation
| Score | Status | Meaning |
|-------|--------|---------|
| 90–100 | 🟢 EXCELLENT | Enterprise production-ready, industry-leading quality |
| 75–89 | 🟡 GOOD | Production-ready, minor improvements recommended |
| 60–74 | 🟠 FAIR | Production-ready with conditions, technical debt accumulating |
| 40–59 | 🔴 POOR | Significant risks, address before next major release |
| 0–39 | ⛔ CRITICAL | Multiple critical failures, do not deploy to production |

---

## 2. Architecture Assessment

Apply `repository-analysis` and `laravel-architecture-reviewer` skills.

### Architecture Health Indicators
```
✅ Healthy signals:
- All 13 development workflow steps followed for new features
- Clean domain separation: Agents, Organizations, Governance, Billing
- Every Action has a DTO and an Event
- No business logic in controllers, Livewire, or templates
- Global scopes enforcing tenant isolation on all org-owned models

🔴 Risk signals:
- Business logic in controllers or Livewire components
- Missing domain layers (Action without DTO, or Event without Listener)
- Cross-domain imports without a shared kernel
- Growing God classes (> 500 lines)
```

### Strategic Architecture Recommendations
When architecture debt is high, recommend:
1. Extract God classes into focused Action + Service pairs
2. Add missing domain layers to complete the pattern
3. Standardize cross-cutting concerns (audit, notifications, tenant scoping) into reusable traits

---

## 3. Security Assessment

Apply `security-auditor` skill. Security has the highest weight because Dot.Agents handles multi-tenant enterprise AI — a breach is platform-ending.

### Non-Negotiable Security Requirements
These must ALL be true for any non-zero security score:
- [ ] Zero known CVEs in dependencies (`composer audit` clean)
- [ ] Zero tenant isolation failures (cross-org data accessible)
- [ ] Zero hardcoded secrets in codebase
- [ ] All AI inputs scanned for prompt injection
- [ ] All Actions have authorization checks

**Any failure → Security Score = 0, regardless of other passing items.**

---

## 4. Scalability Assessment

### Platform Capacity Signals

| Signal | Healthy | Unhealthy |
|--------|---------|-----------|
| AI call pattern | Async via Job | Synchronous in HTTP |
| Queue depth | < 100 pending | > 1000 pending |
| Average AI task latency | < 10s | > 30s |
| DB query count per request | < 20 | > 50 |
| Eager loading | Used throughout | N+1 detected |
| Cache hit rate | > 80% | < 50% |

### Scalability Score Calculation
```
Each async AI call pattern: +10
Each queued heavy operation: +5
Each cached expensive query: +5
Each N+1 query detected: -10
Each synchronous AI call in HTTP: -20
Missing queue separation by priority: -10
```

---

## 5. Release Intelligence

### Deployment Success Tracking
Review `agent_deployments` for failure patterns:
```sql
-- Deployment success rate
SELECT
    DATE(created_at) as date,
    COUNT(*) as total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as successful,
    ROUND(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as success_rate
FROM agent_deployments
WHERE created_at > NOW() - INTERVAL 30 DAY
GROUP BY DATE(created_at)
ORDER BY date DESC;
```

### Performance Regression Detection
```sql
-- Detect latency regressions
SELECT
    DATE(created_at) as date,
    AVG(latency_ms) as avg_latency,
    MAX(latency_ms) as max_latency,
    PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY latency_ms) as p95_latency
FROM agent_tasks
WHERE created_at > NOW() - INTERVAL 7 DAY
GROUP BY DATE(created_at)
ORDER BY date;
```

### Incident Tracking
```sql
-- Recent security events
SELECT type, COUNT(*) as count, MAX(created_at) as last_seen
FROM security_events
WHERE created_at > NOW() - INTERVAL 30 DAY
GROUP BY type
ORDER BY count DESC;
```

---

## 6. Platform Evolution Agent

### Missing Features Detection
Review the codebase against the platform vision and identify gaps:

**Must-Have for Enterprise Platform:**
- [ ] Agent marketplace (browse and install agent types)
- [ ] Billing and usage metering (tracked in `agent_tasks` token counts)
- [ ] SLA monitoring dashboard (latency p95 per agent)
- [ ] Audit log export (CSV/JSON for compliance)
- [ ] Organization-level API key management
- [ ] Webhook system for external integrations
- [ ] Agent versioning and rollback
- [ ] Multi-region deployment support documentation

### Architectural Evolution Opportunities
Identify when the platform has outgrown its current architecture:
- When `agent_tasks` > 10M rows → needs partitioning strategy
- When `audit_logs` > 50M rows → needs archival/tiering
- When `queue:failed` > 1% of total jobs → needs dead letter queue strategy
- When > 50 Livewire components → needs component library documentation

### Optimization Opportunities
Flag when:
- AI model pricing changes make a different model more cost-effective
- Cache hit rates drop below 70% (database load increasing)
- Job queue depth consistently high (worker scaling needed)
- Test suite > 10 minutes (parallelization needed)

---

## 7. Engineering Excellence Tracking

### The 10 Engineering Excellence Dimensions

| # | Dimension | What It Measures | Target |
|---|-----------|-----------------|--------|
| 1 | Architecture Integrity | Domain boundaries, layer separation | ≥ 90 |
| 2 | Security Posture | Vulnerabilities, isolation, injection | ≥ 95 |
| 3 | Performance | Latency, N+1, cache, async patterns | ≥ 85 |
| 4 | Scalability | Queue usage, stateless design | ≥ 85 |
| 5 | Maintainability | Debt score, complexity, readability | ≥ 80 |
| 6 | Testability | Coverage, test quality, isolation | ≥ 80 |
| 7 | Observability | Logging, error tracking, monitoring | ≥ 85 |
| 8 | Documentation | API, agent, workflow, architecture | ≥ 75 |
| 9 | DevOps Maturity | CI/CD, rollback, deployment safety | ≥ 85 |
| 10 | Agent Quality | Contract compliance, governance | ≥ 90 |

---

## 8. Platform Health Report Format

```
╔══════════════════════════════════════════════════════╗
║      DOT.AGENTS PLATFORM HEALTH REPORT               ║
║      Engineering CEO Assessment — [DATE]             ║
╚══════════════════════════════════════════════════════╝

PLATFORM HEALTH SCORE: [XX]/100 — [STATUS]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

DIMENSION SCORES
┌─────────────────────────┬───────┬────────┬──────────┐
│ Dimension               │ Score │ Weight │ Weighted │
├─────────────────────────┼───────┼────────┼──────────┤
│ Architecture            │  /100 │  15%   │          │
│ Security                │  /100 │  20%   │          │
│ Scalability             │  /100 │  15%   │          │
│ Maintainability         │  /100 │  15%   │          │
│ Testability             │  /100 │  15%   │          │
│ Documentation           │  /100 │   5%   │          │
│ Production Readiness    │  /100 │  10%   │          │
│ Agent Quality           │  /100 │   5%   │          │
└─────────────────────────┴───────┴────────┴──────────┘

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🚨 CRITICAL ISSUES (block release)
1. [issue] → [impact] → [fix]

⚠️  HIGH PRIORITY (address this sprint)
1. [issue] → [impact] → [fix]

📈 STRATEGIC RECOMMENDATIONS
1. [recommendation] → [expected impact]
2. [recommendation] → [expected impact]
3. [recommendation] → [expected impact]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

PLATFORM EVOLUTION STATUS
Missing Features (highest value):
- [feature] → [business impact]

Optimization Opportunities:
- [opportunity] → [expected improvement]

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

RELEASE INTELLIGENCE
- Deployment success rate (30d): [X]%
- Avg agent task latency (7d): [X]ms (p95: [X]ms)
- Security events (30d): [X] events ([breakdown])
- Technical debt trend: [improving/stable/worsening]
- Test coverage: [X]%

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

ENGINEERING CEO VERDICT
[2–3 sentence strategic summary of the platform's current state,
 key strength, biggest risk, and top priority action.]
```
