---
name: agent-quality-auditor
description: "Activate when auditing, building, or reviewing AI agents in Dot.Agents. Validates agent interfaces, contracts, interoperability standards, plug-and-play compliance, governance integration (audit logging, approval workflows, delusion detection), and performance characteristics. Use when the user asks to 'audit an agent', 'validate agent contracts', 'check governance integration', 'review agent performance', or works on any file in app/Actions/Agents/, app/Services/AI/, app/Jobs/ related to agent execution."
license: MIT
metadata:
  author: dotagents
---

# Agent Quality Auditor

Quality assurance authority for all AI agents deployed on the Dot.Agents platform. Validates agent contracts, governance integration, performance characteristics, and plug-and-play interoperability so every agent is enterprise-grade before deployment.

## When to Activate

- Building or modifying any agent Action, Service, or Job
- Reviewing an AgentDeployment configuration
- Validating governance hooks (audit log, approval queue, delusion detection)
- Performance profiling of agent execution
- Certifying a new agent type for the marketplace

---

## 1. Agent Interfaces & Contracts

### Required Agent Contract
Every agent integration MUST implement a consistent interface. Validate:

- [ ] Agent execution flows through `ProcessAgentTask` Job (never synchronous in HTTP)
- [ ] Agent input is validated against a typed DTO before dispatch
- [ ] Agent output is captured in `decision_logs` table with all required fields
- [ ] Agent confidence score (0–100) is recorded on every execution
- [ ] Agent `delusion_risk_score` is populated after every execution

### Decision Log Schema (Required Fields)
```php
// Every agent execution must record:
[
    'agent_deployment_id' => int,       // Which deployed agent
    'organization_id'     => int,       // Tenant isolation
    'task_id'             => string,    // UUID
    'input_hash'          => string,    // SHA-256 of prompt (no PII)
    'output_summary'      => string,    // Truncated, non-sensitive summary
    'confidence_score'    => float,     // 0–100 from AI model
    'delusion_risk_score' => float,     // 0–100 computed risk
    'latency_ms'          => int,       // Wall-clock execution time
    'model_used'          => string,    // e.g. 'gpt-4o', 'claude-3-5-sonnet'
    'token_count'         => int,       // Total tokens consumed
    'status'              => string,    // 'completed'|'failed'|'pending_approval'
    'requires_approval'   => bool,      // True if below confidence threshold
]
```

### Validation Checklist
- [ ] All fields above populated for every execution (no nullable required fields)
- [ ] `input_hash` uses SHA-256 — never store raw PII in logs
- [ ] `confidence_score` and `delusion_risk_score` computed before storing

---

## 2. Agent Interoperability

### Plug-and-Play Compliance
An agent is plug-and-play compliant if:

- [ ] It can be deployed by any organization without code changes
- [ ] Configuration is fully driven by `AgentDeployment` settings (not hardcoded)
- [ ] `deployment_mode` controls autonomy: `advisory` | `semi-autonomous` | `autonomous` | `executive_approval`
- [ ] `confidence_threshold` is respected — below threshold routes to approval queue
- [ ] `custom_instructions` are injected into the system prompt dynamically

### System Prompt Injection Pattern
```php
// ✅ CORRECT — Dynamic instructions from deployment config
$systemPrompt = $this->buildSystemPrompt($deployment);

protected function buildSystemPrompt(AgentDeployment $deployment): string
{
    return implode("\n\n", array_filter([
        $deployment->agent->base_system_prompt,
        $deployment->custom_instructions,
        "Current organization context: {$deployment->organization->name}",
        "Confidence threshold: {$deployment->confidence_threshold}%",
    ]));
}

// ❌ WRONG — Hardcoded instructions
$systemPrompt = "You are a financial analyst for ACME Corp...";
```

### Multi-Agent Orchestration Check
For agents that spawn sub-agents:
- [ ] Parent agent passes `organization_id` to all child agents
- [ ] Child agent outputs feed into parent's `decision_log` as nested records
- [ ] Total token budget tracked across the full orchestration chain
- [ ] Circuit breaker: max depth of 3 for nested agent calls

---

## 3. Agent Governance Reviewer

### Audit Logging Validation
- [ ] Every agent action (start, complete, fail, escalate) fires a domain Event
- [ ] `LogDeploymentAudit` listener records to `audit_logs` table
- [ ] Audit record includes: `user_id`, `organization_id`, `action`, `resource_type`, `resource_id`, `before_state`, `after_state`, `ip_address`
- [ ] No audit log suppression exists in code paths

### Approval Workflow Validation
When `confidence_score < deployment.confidence_threshold`:
- [ ] Task status set to `pending_approval`
- [ ] `ApprovalRequested` event fired
- [ ] `SendApprovalNotification` listener queued
- [ ] Human reviewer notified via `platform_notifications`
- [ ] Task execution paused until approval or rejection
- [ ] `ApprovalProcessed` event fires on resolution

```php
// ✅ CORRECT approval gate in ProcessAgentTask
if ($result->confidence < $deployment->confidence_threshold) {
    $task->update(['status' => 'pending_approval']);
    event(new ApprovalRequested($task, $result));
    return; // Halt execution — do not proceed
}
```

### Delusion Detection Validation
- [ ] `DetectAgentDelusion` Job dispatched after every agent completion
- [ ] Delusion risk score stored in `decision_logs.delusion_risk_score`
- [ ] Score > 60 triggers `AgentDriftDetected` event
- [ ] Score > 80 automatically pauses agent deployment (`status = 'paused'`)
- [ ] `NotifyOnAgentDrift` listener queued when drift detected

### Scorecard Integration
- [ ] `UpdateScorecardOnTaskComplete` listener runs after every task
- [ ] Scorecard updates all 10 dimensions: accuracy, speed, cost, reliability, safety, compliance, communication, adaptability, collaboration, innovation
- [ ] `GenerateAgentScorecard` Job runs on schedule (daily/weekly)

---

## 4. Agent Performance Reviewer

### Latency Benchmarks
| Operation | Target | Alert Threshold |
|-----------|--------|----------------|
| Simple advisory task | < 3s | > 10s |
| Complex analysis task | < 15s | > 30s |
| Multi-agent orchestration | < 60s | > 120s |
| Approval notification | < 5s | > 15s |

### Latency Measurement
```php
// Every agent job measures and records wall-clock time
$start = microtime(true);
// ... agent execution ...
$latencyMs = (int) ((microtime(true) - $start) * 1000);
$task->update(['latency_ms' => $latencyMs]);
```

### Resource Usage Validation
- [ ] Token count tracked per execution (input + output tokens)
- [ ] Cost per execution calculable from token count × model pricing
- [ ] No unbounded context windows — max context size enforced per deployment
- [ ] Memory usage within Job stays below PHP memory limit

### Cost Per Execution
```php
// Required cost calculation (approximate)
$costUsd = match ($modelUsed) {
    'gpt-4o'                => ($inputTokens * 0.000005) + ($outputTokens * 0.000015),
    'gpt-4o-mini'           => ($inputTokens * 0.00000015) + ($outputTokens * 0.0000006),
    'claude-3-5-sonnet'     => ($inputTokens * 0.000003) + ($outputTokens * 0.000015),
    default                 => 0.0,
};
```

### Performance Red Flags
- [ ] Agent making redundant AI calls for same input within same task
- [ ] Agent loading full conversation history every call (use summarization)
- [ ] Agent querying database inside the AI prompt loop (pre-fetch all context)
- [ ] Agent not using `Cache::remember()` for deterministic lookups

---

## 5. Quality Certification Output

```
## Agent Quality Audit — [Agent Name] v[Version]

### Certification Status: ✅ CERTIFIED | ⚠️ CONDITIONAL | 🔴 FAILED

### Quality Scores
| Dimension | Score | Status |
|-----------|-------|--------|
| Contract Compliance | /100 | |
| Interoperability | /100 | |
| Governance Integration | /100 | |
| Audit Logging | /100 | |
| Approval Workflow | /100 | |
| Delusion Detection | /100 | |
| Performance | /100 | |
| Cost Efficiency | /100 | |

### Certification Blockers (must resolve)
1. [issue] → [file:line] → [fix]

### Governance Gaps (must fix before production)
1. [gap] → [fix]

### Performance Recommendations
- [recommendation]

### Certification Summary
This agent [is/is not] ready for production deployment on Dot.Agents.
```

**Minimum certification score: 90/100 on all governance dimensions.**
**Any unchecked governance item = automatic FAILED certification.**
