<?php

namespace Database\Seeders;

use App\Models\AgentSkill;
use App\Skills\Core\ContextEngineeringSkill;
use App\Skills\Meta\SuperpowersSkill;
use App\Skills\Platform\ExcelDataProcessingSkill;
use App\Skills\Platform\MarketingIntelligenceSkill;
use App\Skills\Platform\MassContentGenerationSkill;
use App\Skills\Platform\SeoAnalyserSkill;
use App\Skills\Platform\SeoAuditSkill;
use App\Skills\Platform\SeoOptimizationSkill;
use App\Skills\Platform\VideoScriptingSkill;
use Illuminate\Database\Seeder;

class EnterpriseSkillsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->skills() as $skill) {
            AgentSkill::firstOrCreate(
                ['key' => $skill['key']],
                $skill
            );
        }

        $this->command->info('[EnterpriseSkillsSeeder] '.count($this->skills()).' enterprise skills seeded.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Full skill catalog
    // ─────────────────────────────────────────────────────────────────────────

    private function skills(): array
    {
        return array_merge(
            $this->financeControllerSkills(),
            $this->accountsPayableSkills(),
            $this->accountsReceivableSkills(),
            $this->hrManagerSkills(),
            $this->recruitmentSkills(),
            $this->itOperationsSkills(),
            $this->securityOperationsSkills(),
            $this->salesManagerSkills(),
            $this->crmSkills(),
            $this->marketingStrategistSkills(),
            $this->operationsManagerSkills(),
            $this->ceoSkills(),
            $this->cfoSkills(),
            $this->cioSkills(),
            $this->agentAuditorSkills(),
            $this->agentTrainerSkills(),
            $this->agentMarketplaceManagerSkills(),
            $this->socialCommerceSkills(),
            $this->communitySourcedSkills(),
        );
    }

    // ─── Finance Controller ───────────────────────────────────────────────────

    private function financeControllerSkills(): array
    {
        return [
            $this->skill('finance-controller.generate-budget-forecast', 'Generate Budget Forecast', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_budgets'], ['erp.budgets', 'erp.actuals']),
            $this->skill('finance-controller.monitor-budget-variance', 'Monitor Budget Variance', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_budgets'], ['erp.budgets', 'erp.actuals']),
            $this->skill('finance-controller.analyze-cost-trends', 'Analyze Cost Trends', 'finance', 'finance_controller', 'report', 'low', false, ['finance.read_budgets'], ['erp.expenses']),
            $this->skill('finance-controller.detect-cost-overruns', 'Detect Cost Overruns', 'finance', 'finance_controller', 'notification', 'high', true, ['finance.read_budgets'], ['erp.budgets', 'erp.actuals'], ['alert_threshold' => 'budget_exceeded']),
            $this->skill('finance-controller.review-department-spend', 'Review Department Spend', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_spend'], ['erp.expenses']),
            $this->skill('finance-controller.recommend-cost-reductions', 'Recommend Cost Reductions', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_budgets'], ['erp.expenses', 'erp.budgets']),
            $this->skill('finance-controller.validate-expense-claims', 'Validate Expense Claims', 'finance', 'finance_controller', 'action', 'high', true, ['finance.validate_expenses'], ['erp.expenses', 'hr.policies']),
            $this->skill('finance-controller.generate-financial-reports', 'Generate Financial Reports', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_reports'], ['erp.gl', 'erp.actuals']),
            $this->skill('finance-controller.monitor-financial-kpis', 'Monitor Financial KPIs', 'finance', 'finance_controller', 'report', 'low', false, ['finance.read_kpis'], ['erp.kpis']),
            $this->skill('finance-controller.perform-variance-analysis', 'Perform Variance Analysis', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_budgets'], ['erp.budgets', 'erp.actuals']),
            $this->skill('finance-controller.track-capex', 'Track Capital Expenditure', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_capex'], ['erp.assets', 'erp.capex']),
            $this->skill('finance-controller.track-opex', 'Track Operating Expenditure', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_opex'], ['erp.expenses']),
            $this->skill('finance-controller.generate-executive-finance-packs', 'Generate Executive Finance Packs', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.generate_exec_packs'], ['erp.gl', 'erp.budgets', 'erp.kpis']),
            $this->skill('finance-controller.forecast-cash-flow', 'Forecast Cash Flow', 'finance', 'finance_controller', 'report', 'medium', false, ['finance.read_cashflow'], ['erp.cashflow', 'erp.receivables', 'erp.payables']),
            $this->skill('finance-controller.calculate-roi', 'Calculate ROI', 'finance', 'finance_controller', 'report', 'low', false, ['finance.read_investments'], ['erp.investments', 'erp.returns']),
        ];
    }

    // ─── Accounts Payable ─────────────────────────────────────────────────────

    private function accountsPayableSkills(): array
    {
        return [
            $this->skill('ap.validate-vendor-invoices', 'Validate Vendor Invoices', 'finance', 'accounts_payable', 'action', 'high', true, ['finance.validate_invoices'], ['erp.invoices', 'erp.vendors']),
            $this->skill('ap.match-purchase-orders', 'Match Purchase Orders', 'finance', 'accounts_payable', 'action', 'medium', false, ['finance.match_pos'], ['erp.purchase_orders', 'erp.invoices']),
            $this->skill('ap.detect-duplicate-invoices', 'Detect Duplicate Invoices', 'finance', 'accounts_payable', 'notification', 'high', true, ['finance.read_invoices'], ['erp.invoices'], ['flag_duplicates' => true]),
            $this->skill('ap.process-payment-requests', 'Process Payment Requests', 'finance', 'accounts_payable', 'action', 'critical', true, ['finance.process_payments'], ['erp.payments', 'banking.outbound']),
            $this->skill('ap.schedule-vendor-payments', 'Schedule Vendor Payments', 'finance', 'accounts_payable', 'action', 'high', true, ['finance.schedule_payments'], ['erp.payables', 'banking.outbound']),
            $this->skill('ap.monitor-outstanding-invoices', 'Monitor Outstanding Invoices', 'finance', 'accounts_payable', 'report', 'medium', false, ['finance.read_invoices'], ['erp.invoices']),
            $this->skill('ap.verify-banking-details', 'Verify Banking Details', 'finance', 'accounts_payable', 'action', 'critical', true, ['finance.verify_banking'], ['erp.vendors', 'banking.verification']),
            $this->skill('ap.generate-payment-reports', 'Generate Payment Reports', 'finance', 'accounts_payable', 'report', 'low', false, ['finance.read_payments'], ['erp.payments']),
            $this->skill('ap.track-payment-status', 'Track Payment Status', 'finance', 'accounts_payable', 'report', 'low', false, ['finance.read_payments'], ['erp.payments', 'banking.status']),
            $this->skill('ap.escalate-payment-risks', 'Escalate Payment Risks', 'finance', 'accounts_payable', 'notification', 'high', true, ['finance.escalate_risks'], ['erp.payments', 'erp.vendors']),
        ];
    }

    // ─── Accounts Receivable ─────────────────────────────────────────────────

    private function accountsReceivableSkills(): array
    {
        return [
            $this->skill('ar.generate-customer-invoices', 'Generate Customer Invoices', 'finance', 'accounts_receivable', 'record', 'medium', true, ['finance.create_invoices'], ['erp.customers', 'erp.orders']),
            $this->skill('ar.track-outstanding-payments', 'Track Outstanding Payments', 'finance', 'accounts_receivable', 'report', 'medium', false, ['finance.read_receivables'], ['erp.receivables']),
            $this->skill('ar.monitor-debtor-aging', 'Monitor Debtor Aging', 'finance', 'accounts_receivable', 'report', 'medium', false, ['finance.read_debtors'], ['erp.receivables', 'erp.customers']),
            $this->skill('ar.send-payment-reminders', 'Send Payment Reminders', 'finance', 'accounts_receivable', 'notification', 'medium', true, ['finance.send_reminders', 'crm.send_emails'], ['erp.receivables', 'crm.customers']),
            $this->skill('ar.predict-late-payments', 'Predict Late Payments', 'finance', 'accounts_receivable', 'report', 'medium', false, ['finance.read_receivables'], ['erp.receivables', 'crm.payment_history']),
            $this->skill('ar.generate-collection-reports', 'Generate Collection Reports', 'finance', 'accounts_receivable', 'report', 'low', false, ['finance.read_collections'], ['erp.receivables']),
            $this->skill('ar.monitor-credit-limits', 'Monitor Credit Limits', 'finance', 'accounts_receivable', 'report', 'medium', false, ['finance.read_credit'], ['erp.credit_limits', 'crm.customers']),
            $this->skill('ar.flag-collection-risks', 'Flag Collection Risks', 'finance', 'accounts_receivable', 'notification', 'high', true, ['finance.flag_risks'], ['erp.receivables', 'crm.customers']),
        ];
    }

    // ─── HR Manager ───────────────────────────────────────────────────────────

    private function hrManagerSkills(): array
    {
        return [
            $this->skill('hr.monitor-workforce-health', 'Monitor Workforce Health', 'hr', 'hr_manager', 'report', 'low', false, ['hr.read_workforce'], ['hris.employees', 'hris.attendance']),
            $this->skill('hr.track-employee-turnover', 'Track Employee Turnover', 'hr', 'hr_manager', 'report', 'medium', false, ['hr.read_turnover'], ['hris.exits', 'hris.employees']),
            $this->skill('hr.track-leave-balances', 'Track Leave Balances', 'hr', 'hr_manager', 'report', 'low', false, ['hr.read_leave'], ['hris.leave']),
            $this->skill('hr.generate-hr-reports', 'Generate HR Reports', 'hr', 'hr_manager', 'report', 'medium', false, ['hr.read_reports'], ['hris.employees', 'hris.performance']),
            $this->skill('hr.monitor-department-staffing', 'Monitor Department Staffing', 'hr', 'hr_manager', 'report', 'medium', false, ['hr.read_staffing'], ['hris.departments', 'hris.employees']),
            $this->skill('hr.analyze-workforce-trends', 'Analyze Workforce Trends', 'hr', 'hr_manager', 'report', 'low', false, ['hr.read_workforce'], ['hris.employees', 'hris.performance']),
            $this->skill('hr.review-employee-performance', 'Review Employee Performance', 'hr', 'hr_manager', 'report', 'high', false, ['hr.read_performance'], ['hris.performance', 'hris.reviews']),
            $this->skill('hr.track-training-compliance', 'Track Training Compliance', 'hr', 'hr_manager', 'report', 'medium', false, ['hr.read_training'], ['hris.training', 'compliance.requirements']),
            $this->skill('hr.identify-hiring-needs', 'Identify Hiring Needs', 'hr', 'hr_manager', 'report', 'medium', false, ['hr.read_staffing'], ['hris.departments', 'hris.headcount']),
            $this->skill('hr.manage-succession-planning', 'Manage Succession Planning', 'hr', 'hr_manager', 'report', 'high', true, ['hr.succession_planning'], ['hris.employees', 'hris.performance']),
        ];
    }

    // ─── Recruitment ──────────────────────────────────────────────────────────

    private function recruitmentSkills(): array
    {
        return [
            $this->skill('recruitment.generate-job-descriptions', 'Generate Job Descriptions', 'hr', 'recruitment', 'record', 'low', false, ['hr.create_jobs'], ['hris.job_templates']),
            $this->skill('recruitment.screen-candidates', 'Screen Candidates', 'hr', 'recruitment', 'report', 'medium', false, ['hr.screen_candidates'], ['ats.applications', 'ats.resumes']),
            $this->skill('recruitment.rank-applicants', 'Rank Applicants', 'hr', 'recruitment', 'report', 'medium', false, ['hr.rank_applicants'], ['ats.applications', 'ats.assessments']),
            $this->skill('recruitment.schedule-interviews', 'Schedule Interviews', 'hr', 'recruitment', 'action', 'low', false, ['hr.schedule_interviews', 'calendar.write'], ['ats.applications', 'calendar.availability']),
            $this->skill('recruitment.perform-candidate-matching', 'Perform Candidate Matching', 'hr', 'recruitment', 'report', 'medium', false, ['hr.screen_candidates'], ['ats.applications', 'ats.job_requirements']),
            $this->skill('recruitment.analyze-candidate-skills', 'Analyze Candidate Skills', 'hr', 'recruitment', 'report', 'medium', false, ['hr.screen_candidates'], ['ats.resumes', 'ats.assessments']),
            $this->skill('recruitment.generate-interview-packs', 'Generate Interview Packs', 'hr', 'recruitment', 'report', 'low', false, ['hr.read_interviews'], ['ats.applications', 'hris.job_profiles']),
            $this->skill('recruitment.track-recruitment-funnel', 'Track Recruitment Funnel', 'hr', 'recruitment', 'report', 'low', false, ['hr.read_recruitment'], ['ats.pipeline']),
            $this->skill('recruitment.predict-hiring-success', 'Predict Hiring Success', 'hr', 'recruitment', 'report', 'medium', false, ['hr.read_recruitment'], ['ats.applications', 'hris.performance_history']),
            $this->skill('recruitment.recommend-final-candidates', 'Recommend Final Candidates', 'hr', 'recruitment', 'report', 'high', true, ['hr.recommend_candidates'], ['ats.assessments', 'ats.interviews']),
        ];
    }

    // ─── IT Operations ────────────────────────────────────────────────────────

    private function itOperationsSkills(): array
    {
        return [
            $this->skill('it-ops.monitor-system-health', 'Monitor System Health', 'it', 'it_operations', 'report', 'low', false, ['it.read_monitoring'], ['monitoring.metrics', 'monitoring.alerts']),
            $this->skill('it-ops.track-infrastructure-utilization', 'Track Infrastructure Utilization', 'it', 'it_operations', 'report', 'low', false, ['it.read_infrastructure'], ['monitoring.capacity', 'cloud.billing']),
            $this->skill('it-ops.detect-service-outages', 'Detect Service Outages', 'it', 'it_operations', 'notification', 'critical', true, ['it.read_monitoring'], ['monitoring.availability'], ['auto_escalate' => true]),
            $this->skill('it-ops.monitor-application-availability', 'Monitor Application Availability', 'it', 'it_operations', 'report', 'medium', false, ['it.read_monitoring'], ['monitoring.uptime']),
            $this->skill('it-ops.track-incident-trends', 'Track Incident Trends', 'it', 'it_operations', 'report', 'low', false, ['it.read_incidents'], ['itsm.incidents']),
            $this->skill('it-ops.analyze-infrastructure-costs', 'Analyze Infrastructure Costs', 'it', 'it_operations', 'report', 'medium', false, ['it.read_costs'], ['cloud.billing', 'erp.it_spend']),
            $this->skill('it-ops.generate-operational-reports', 'Generate Operational Reports', 'it', 'it_operations', 'report', 'low', false, ['it.read_reports'], ['itsm.incidents', 'monitoring.metrics']),
            $this->skill('it-ops.monitor-service-levels', 'Monitor Service Levels', 'it', 'it_operations', 'report', 'medium', false, ['it.read_sla'], ['itsm.sla', 'monitoring.metrics']),
            $this->skill('it-ops.detect-capacity-risks', 'Detect Capacity Risks', 'it', 'it_operations', 'notification', 'high', true, ['it.read_capacity'], ['monitoring.capacity', 'cloud.forecasts']),
            $this->skill('it-ops.manage-asset-inventory', 'Manage Asset Inventory', 'it', 'it_operations', 'record', 'medium', false, ['it.manage_assets'], ['itsm.assets', 'cmdb.assets']),
        ];
    }

    // ─── Security Operations ──────────────────────────────────────────────────

    private function securityOperationsSkills(): array
    {
        return [
            $this->skill('sec-ops.detect-security-incidents', 'Detect Security Incidents', 'it', 'security_operations', 'notification', 'critical', true, ['security.read_events'], ['siem.events', 'siem.alerts'], ['auto_escalate' => true]),
            $this->skill('sec-ops.monitor-threat-activity', 'Monitor Threat Activity', 'it', 'security_operations', 'report', 'high', false, ['security.read_threats'], ['siem.threat_intel', 'siem.events']),
            $this->skill('sec-ops.analyze-login-patterns', 'Analyze Login Patterns', 'it', 'security_operations', 'report', 'high', false, ['security.read_audit_logs'], ['iam.auth_logs']),
            $this->skill('sec-ops.detect-privilege-escalation', 'Detect Privilege Escalation', 'it', 'security_operations', 'notification', 'critical', true, ['security.read_iam'], ['iam.access_logs', 'iam.roles'], ['auto_escalate' => true]),
            $this->skill('sec-ops.track-security-compliance', 'Track Security Compliance', 'it', 'security_operations', 'report', 'high', false, ['security.read_compliance'], ['compliance.controls', 'siem.compliance']),
            $this->skill('sec-ops.generate-security-reports', 'Generate Security Reports', 'it', 'security_operations', 'report', 'medium', false, ['security.read_reports'], ['siem.events', 'siem.incidents']),
            $this->skill('sec-ops.monitor-endpoint-health', 'Monitor Endpoint Health', 'it', 'security_operations', 'report', 'medium', false, ['security.read_endpoints'], ['edr.endpoints']),
            $this->skill('sec-ops.review-access-violations', 'Review Access Violations', 'it', 'security_operations', 'report', 'high', false, ['security.read_violations'], ['iam.access_logs', 'siem.violations']),
            $this->skill('sec-ops.investigate-security-events', 'Investigate Security Events', 'it', 'security_operations', 'report', 'high', true, ['security.investigate_events'], ['siem.events', 'iam.logs', 'edr.forensics']),
            $this->skill('sec-ops.recommend-remediation-actions', 'Recommend Remediation Actions', 'it', 'security_operations', 'report', 'high', true, ['security.recommend_remediation'], ['siem.incidents', 'vuln.scanner']),
        ];
    }

    // ─── Sales Manager ────────────────────────────────────────────────────────

    private function salesManagerSkills(): array
    {
        return [
            $this->skill('sales.monitor-sales-performance', 'Monitor Sales Performance', 'sales', 'sales_manager', 'report', 'low', false, ['sales.read_performance'], ['crm.deals', 'crm.activities']),
            $this->skill('sales.analyze-pipeline-health', 'Analyze Pipeline Health', 'sales', 'sales_manager', 'report', 'low', false, ['sales.read_pipeline'], ['crm.pipeline']),
            $this->skill('sales.track-sales-targets', 'Track Sales Targets', 'sales', 'sales_manager', 'report', 'medium', false, ['sales.read_targets'], ['crm.targets', 'erp.revenue']),
            $this->skill('sales.forecast-revenue', 'Forecast Revenue', 'sales', 'sales_manager', 'report', 'medium', false, ['sales.read_forecasts'], ['crm.pipeline', 'crm.history']),
            $this->skill('sales.generate-sales-reports', 'Generate Sales Reports', 'sales', 'sales_manager', 'report', 'low', false, ['sales.read_reports'], ['crm.deals', 'erp.revenue']),
            $this->skill('sales.monitor-conversion-rates', 'Monitor Conversion Rates', 'sales', 'sales_manager', 'report', 'low', false, ['sales.read_conversion'], ['crm.pipeline', 'crm.deals']),
            $this->skill('sales.track-customer-acquisition', 'Track Customer Acquisition', 'sales', 'sales_manager', 'report', 'low', false, ['sales.read_acquisition'], ['crm.customers', 'crm.deals']),
            $this->skill('sales.identify-sales-risks', 'Identify Sales Risks', 'sales', 'sales_manager', 'report', 'medium', false, ['sales.read_pipeline'], ['crm.pipeline', 'crm.activities']),
            $this->skill('sales.review-sales-activities', 'Review Sales Activities', 'sales', 'sales_manager', 'report', 'low', false, ['sales.read_activities'], ['crm.activities']),
            $this->skill('sales.recommend-revenue-actions', 'Recommend Revenue Actions', 'sales', 'sales_manager', 'report', 'medium', false, ['sales.read_pipeline'], ['crm.pipeline', 'crm.deals']),
        ];
    }

    // ─── CRM ──────────────────────────────────────────────────────────────────

    private function crmSkills(): array
    {
        return [
            $this->skill('crm.create-leads', 'Create Leads', 'sales', 'crm', 'record', 'low', false, ['crm.create_leads'], ['crm.leads']),
            $this->skill('crm.qualify-leads', 'Qualify Leads', 'sales', 'crm', 'action', 'medium', false, ['crm.qualify_leads'], ['crm.leads', 'crm.scoring']),
            $this->skill('crm.score-opportunities', 'Score Opportunities', 'sales', 'crm', 'action', 'medium', false, ['crm.score_opps'], ['crm.pipeline', 'crm.scoring']),
            $this->skill('crm.track-customer-engagement', 'Track Customer Engagement', 'sales', 'crm', 'report', 'low', false, ['crm.read_engagement'], ['crm.activities', 'crm.emails']),
            $this->skill('crm.monitor-deal-progress', 'Monitor Deal Progress', 'sales', 'crm', 'report', 'low', false, ['crm.read_deals'], ['crm.pipeline']),
            $this->skill('crm.generate-follow-ups', 'Generate Follow-Ups', 'sales', 'crm', 'notification', 'low', false, ['crm.send_followups', 'email.send'], ['crm.pipeline', 'calendar.events']),
            $this->skill('crm.identify-upsell-opportunities', 'Identify Upsell Opportunities', 'sales', 'crm', 'report', 'medium', false, ['crm.read_customers'], ['crm.customers', 'crm.products', 'erp.orders']),
            $this->skill('crm.detect-churn-risks', 'Detect Churn Risks', 'sales', 'crm', 'notification', 'high', true, ['crm.read_engagement'], ['crm.activities', 'crm.support_tickets']),
            $this->skill('crm.recommend-next-actions', 'Recommend Next Actions', 'sales', 'crm', 'report', 'low', false, ['crm.read_pipeline'], ['crm.pipeline', 'crm.activities']),
            $this->skill('crm.maintain-crm-records', 'Maintain CRM Records', 'sales', 'crm', 'action', 'medium', true, ['crm.update_records'], ['crm.contacts', 'crm.companies']),
        ];
    }

    // ─── Marketing Strategist ─────────────────────────────────────────────────

    private function marketingStrategistSkills(): array
    {
        return [
            $this->skill('marketing.analyze-campaign-performance', 'Analyze Campaign Performance', 'marketing', 'marketing_strategist', 'report', 'low', false, ['marketing.read_campaigns'], ['marketing.campaigns', 'analytics.data']),
            $this->skill('marketing.track-marketing-kpis', 'Track Marketing KPIs', 'marketing', 'marketing_strategist', 'report', 'low', false, ['marketing.read_kpis'], ['marketing.metrics']),
            $this->skill('marketing.monitor-lead-generation', 'Monitor Lead Generation', 'marketing', 'marketing_strategist', 'report', 'low', false, ['marketing.read_leads'], ['crm.leads', 'marketing.campaigns']),
            $this->skill('marketing.generate-marketing-reports', 'Generate Marketing Reports', 'marketing', 'marketing_strategist', 'report', 'low', false, ['marketing.read_reports'], ['marketing.campaigns', 'analytics.data']),
            $this->skill('marketing.analyze-competitor-activity', 'Analyze Competitor Activity', 'marketing', 'marketing_strategist', 'report', 'medium', false, ['marketing.read_intelligence'], ['marketing.intel', 'web.search']),
            $this->skill('marketing.forecast-campaign-results', 'Forecast Campaign Results', 'marketing', 'marketing_strategist', 'report', 'medium', false, ['marketing.read_campaigns'], ['marketing.campaigns', 'analytics.history']),
            $this->skill('marketing.optimize-marketing-spend', 'Optimize Marketing Spend', 'marketing', 'marketing_strategist', 'report', 'high', true, ['marketing.manage_budgets'], ['marketing.budgets', 'marketing.campaigns']),
            $this->skill('marketing.monitor-brand-sentiment', 'Monitor Brand Sentiment', 'marketing', 'marketing_strategist', 'report', 'medium', false, ['marketing.read_sentiment'], ['social.mentions', 'reviews.data']),
            $this->skill('marketing.track-customer-engagement', 'Track Customer Engagement', 'marketing', 'marketing_strategist', 'report', 'low', false, ['marketing.read_engagement'], ['crm.engagement', 'analytics.data']),
            $this->skill('marketing.recommend-marketing-actions', 'Recommend Marketing Actions', 'marketing', 'marketing_strategist', 'report', 'medium', false, ['marketing.read_campaigns'], ['marketing.metrics', 'crm.segments']),
        ];
    }

    // ─── Operations Manager ───────────────────────────────────────────────────

    private function operationsManagerSkills(): array
    {
        return [
            $this->skill('ops.monitor-operational-performance', 'Monitor Operational Performance', 'operations', 'operations_manager', 'report', 'low', false, ['ops.read_performance'], ['erp.operations', 'monitoring.kpis']),
            $this->skill('ops.track-production-targets', 'Track Production Targets', 'operations', 'operations_manager', 'report', 'medium', false, ['ops.read_production'], ['erp.production', 'erp.targets']),
            $this->skill('ops.identify-bottlenecks', 'Identify Bottlenecks', 'operations', 'operations_manager', 'report', 'medium', false, ['ops.read_workflows'], ['erp.operations', 'monitoring.throughput']),
            $this->skill('ops.analyze-workflow-efficiency', 'Analyze Workflow Efficiency', 'operations', 'operations_manager', 'report', 'low', false, ['ops.read_workflows'], ['erp.workflows', 'monitoring.tasks']),
            $this->skill('ops.monitor-asset-utilization', 'Monitor Asset Utilization', 'operations', 'operations_manager', 'report', 'low', false, ['ops.read_assets'], ['erp.assets', 'iot.sensors']),
            $this->skill('ops.generate-operations-reports', 'Generate Operations Reports', 'operations', 'operations_manager', 'report', 'low', false, ['ops.read_reports'], ['erp.operations']),
            $this->skill('ops.track-service-levels', 'Track Service Levels', 'operations', 'operations_manager', 'report', 'medium', false, ['ops.read_sla'], ['itsm.sla', 'erp.services']),
            $this->skill('ops.detect-operational-risks', 'Detect Operational Risks', 'operations', 'operations_manager', 'notification', 'high', true, ['ops.read_risks'], ['erp.operations', 'monitoring.alerts']),
            $this->skill('ops.recommend-process-improvements', 'Recommend Process Improvements', 'operations', 'operations_manager', 'report', 'medium', false, ['ops.read_workflows'], ['erp.operations', 'monitoring.kpis']),
            $this->skill('ops.monitor-department-kpis', 'Monitor Department KPIs', 'operations', 'operations_manager', 'report', 'low', false, ['ops.read_kpis'], ['erp.kpis', 'monitoring.metrics']),
        ];
    }

    // ─── CEO Agent ────────────────────────────────────────────────────────────

    private function ceoSkills(): array
    {
        return [
            $this->skill('ceo.review-organizational-performance', 'Review Organizational Performance', 'executive', 'ceo', 'report', 'medium', false, ['exec.read_performance'], ['erp.kpis', 'hris.headcount', 'crm.revenue']),
            $this->skill('ceo.analyze-strategic-kpis', 'Analyze Strategic KPIs', 'executive', 'ceo', 'report', 'medium', false, ['exec.read_kpis'], ['erp.kpis', 'analytics.strategic']),
            $this->skill('ceo.generate-executive-reports', 'Generate Executive Reports', 'executive', 'ceo', 'report', 'medium', false, ['exec.generate_reports'], ['erp.all', 'hris.summary', 'crm.summary']),
            $this->skill('ceo.monitor-department-health', 'Monitor Department Health', 'executive', 'ceo', 'report', 'medium', false, ['exec.read_departments'], ['erp.departments', 'hris.departments']),
            $this->skill('ceo.identify-strategic-risks', 'Identify Strategic Risks', 'executive', 'ceo', 'report', 'high', true, ['exec.read_risks'], ['erp.risks', 'siem.threats', 'market.intel']),
            $this->skill('ceo.evaluate-business-opportunities', 'Evaluate Business Opportunities', 'executive', 'ceo', 'report', 'high', true, ['exec.evaluate_opportunities'], ['market.intel', 'erp.financials', 'crm.pipeline']),
            $this->skill('ceo.track-organizational-goals', 'Track Organizational Goals', 'executive', 'ceo', 'report', 'medium', false, ['exec.read_goals'], ['erp.okrs', 'hris.goals']),
            $this->skill('ceo.review-workforce-productivity', 'Review Workforce Productivity', 'executive', 'ceo', 'report', 'medium', false, ['exec.read_workforce'], ['hris.productivity', 'erp.output']),
            $this->skill('ceo.monitor-platform-roi', 'Monitor Platform ROI', 'executive', 'ceo', 'report', 'medium', false, ['exec.read_roi'], ['platform.usage', 'erp.costs', 'crm.revenue']),
            $this->skill('ceo.recommend-strategic-actions', 'Recommend Strategic Actions', 'executive', 'ceo', 'report', 'critical', true, ['exec.strategic_actions'], ['erp.all', 'crm.all', 'market.intel']),
        ];
    }

    // ─── CFO Agent ────────────────────────────────────────────────────────────

    private function cfoSkills(): array
    {
        return [
            $this->skill('cfo.monitor-organizational-spend', 'Monitor Organizational Spend', 'executive', 'cfo', 'report', 'medium', false, ['finance.read_all_spend'], ['erp.expenses', 'erp.budgets']),
            $this->skill('cfo.analyze-profitability', 'Analyze Profitability', 'executive', 'cfo', 'report', 'medium', false, ['finance.read_pl'], ['erp.gl', 'erp.revenue']),
            $this->skill('cfo.forecast-financial-performance', 'Forecast Financial Performance', 'executive', 'cfo', 'report', 'medium', false, ['finance.read_forecasts'], ['erp.financials', 'erp.pipeline']),
            $this->skill('cfo.review-budget-compliance', 'Review Budget Compliance', 'executive', 'cfo', 'report', 'high', true, ['finance.review_budgets'], ['erp.budgets', 'erp.actuals']),
            $this->skill('cfo.track-cost-efficiency', 'Track Cost Efficiency', 'executive', 'cfo', 'report', 'medium', false, ['finance.read_efficiency'], ['erp.expenses', 'erp.benchmarks']),
            $this->skill('cfo.generate-executive-financial-reports', 'Generate Executive Financial Reports', 'executive', 'cfo', 'report', 'medium', false, ['finance.generate_exec_reports'], ['erp.gl', 'erp.kpis']),
            $this->skill('cfo.monitor-cash-flow', 'Monitor Cash Flow', 'executive', 'cfo', 'report', 'high', false, ['finance.read_cashflow'], ['erp.cashflow', 'banking.accounts']),
            $this->skill('cfo.identify-financial-risks', 'Identify Financial Risks', 'executive', 'cfo', 'notification', 'high', true, ['finance.assess_risks'], ['erp.financials', 'market.rates']),
            $this->skill('cfo.evaluate-investment-opportunities', 'Evaluate Investment Opportunities', 'executive', 'cfo', 'report', 'critical', true, ['finance.evaluate_investments'], ['erp.capex', 'market.intel']),
            $this->skill('cfo.recommend-financial-actions', 'Recommend Financial Actions', 'executive', 'cfo', 'report', 'critical', true, ['finance.strategic_actions'], ['erp.financials', 'market.intel']),
        ];
    }

    // ─── CIO Agent ────────────────────────────────────────────────────────────

    private function cioSkills(): array
    {
        return [
            $this->skill('cio.review-technology-performance', 'Review Technology Performance', 'executive', 'cio', 'report', 'medium', false, ['it.read_exec_view'], ['monitoring.metrics', 'cloud.billing']),
            $this->skill('cio.monitor-platform-health', 'Monitor Platform Health', 'executive', 'cio', 'report', 'medium', false, ['it.read_platform_health'], ['monitoring.availability', 'monitoring.performance']),
            $this->skill('cio.track-technology-costs', 'Track Technology Costs', 'executive', 'cio', 'report', 'medium', false, ['it.read_costs'], ['cloud.billing', 'erp.it_spend']),
            $this->skill('cio.evaluate-technology-risks', 'Evaluate Technology Risks', 'executive', 'cio', 'report', 'high', true, ['it.evaluate_risks'], ['siem.risk', 'vuln.scanner']),
            $this->skill('cio.analyze-system-performance', 'Analyze System Performance', 'executive', 'cio', 'report', 'low', false, ['it.read_performance'], ['monitoring.apm', 'monitoring.infra']),
            $this->skill('cio.generate-cio-reports', 'Generate CIO Reports', 'executive', 'cio', 'report', 'medium', false, ['it.generate_reports'], ['monitoring.all', 'cloud.billing']),
            $this->skill('cio.review-digital-transformation-progress', 'Review Digital Transformation Progress', 'executive', 'cio', 'report', 'medium', false, ['it.read_transformation'], ['erp.projects', 'it.roadmap']),
            $this->skill('cio.monitor-cybersecurity-posture', 'Monitor Cybersecurity Posture', 'executive', 'cio', 'report', 'high', true, ['security.read_posture'], ['siem.compliance', 'siem.threats']),
            $this->skill('cio.recommend-technology-investments', 'Recommend Technology Investments', 'executive', 'cio', 'report', 'critical', true, ['it.strategic_investments'], ['erp.capex', 'it.roadmap', 'market.intel']),
        ];
    }

    // ─── Agent Auditor ────────────────────────────────────────────────────────

    private function agentAuditorSkills(): array
    {
        return [
            $this->skill('agent-auditor.audit-agent-decisions', 'Audit Agent Decisions', 'platform', 'agent_auditor', 'report', 'medium', false, ['governance.read_decisions'], ['platform.decision_logs']),
            $this->skill('agent-auditor.verify-agent-outputs', 'Verify Agent Outputs', 'platform', 'agent_auditor', 'report', 'medium', false, ['governance.read_outputs'], ['platform.task_outputs']),
            $this->skill('agent-auditor.validate-confidence-scores', 'Validate Confidence Scores', 'platform', 'agent_auditor', 'report', 'medium', false, ['governance.read_scores'], ['platform.decision_logs']),
            $this->skill('agent-auditor.review-governance-compliance', 'Review Governance Compliance', 'platform', 'agent_auditor', 'report', 'high', false, ['governance.read_compliance'], ['platform.audit_logs', 'platform.policies']),
            $this->skill('agent-auditor.detect-hallucinations', 'Detect Hallucinations', 'platform', 'agent_auditor', 'notification', 'high', true, ['governance.detect_delusions'], ['platform.decision_logs', 'platform.outputs']),
            $this->skill('agent-auditor.monitor-agent-performance', 'Monitor Agent Performance', 'platform', 'agent_auditor', 'report', 'low', false, ['governance.read_scorecards'], ['platform.scorecards']),
            $this->skill('agent-auditor.generate-audit-reports', 'Generate Audit Reports', 'platform', 'agent_auditor', 'report', 'medium', false, ['governance.read_audit_logs'], ['platform.audit_logs']),
            $this->skill('agent-auditor.track-agent-reliability', 'Track Agent Reliability', 'platform', 'agent_auditor', 'report', 'medium', false, ['governance.read_reliability'], ['platform.scorecards', 'platform.tasks']),
            $this->skill('agent-auditor.recommend-agent-improvements', 'Recommend Agent Improvements', 'platform', 'agent_auditor', 'report', 'medium', false, ['governance.read_performance'], ['platform.scorecards', 'platform.decision_logs']),
        ];
    }

    // ─── Agent Trainer ────────────────────────────────────────────────────────

    private function agentTrainerSkills(): array
    {
        return [
            $this->skill('agent-trainer.analyze-agent-outcomes', 'Analyze Agent Outcomes', 'platform', 'agent_trainer', 'report', 'low', false, ['governance.read_outcomes'], ['platform.tasks', 'platform.scorecards']),
            $this->skill('agent-trainer.identify-skill-gaps', 'Identify Skill Gaps', 'platform', 'agent_trainer', 'report', 'medium', false, ['governance.read_skills'], ['platform.skill_executions', 'platform.scorecards']),
            $this->skill('agent-trainer.recommend-skill-improvements', 'Recommend Skill Improvements', 'platform', 'agent_trainer', 'report', 'medium', false, ['governance.read_skills'], ['platform.skill_scores', 'platform.decision_logs']),
            $this->skill('agent-trainer.generate-training-plans', 'Generate Training Plans', 'platform', 'agent_trainer', 'report', 'medium', false, ['governance.manage_training'], ['platform.skill_gaps', 'platform.performance']),
            $this->skill('agent-trainer.optimize-agent-performance', 'Optimize Agent Performance', 'platform', 'agent_trainer', 'action', 'high', true, ['governance.optimize_agents'], ['platform.scorecards', 'platform.skill_scores']),
            $this->skill('agent-trainer.track-learning-progress', 'Track Learning Progress', 'platform', 'agent_trainer', 'report', 'low', false, ['governance.read_training'], ['platform.training_logs']),
            $this->skill('agent-trainer.recommend-workflow-enhancements', 'Recommend Workflow Enhancements', 'platform', 'agent_trainer', 'report', 'medium', false, ['governance.read_workflows'], ['platform.workflows', 'platform.performance']),
        ];
    }

    // ─── Agent Marketplace Manager ────────────────────────────────────────────

    private function agentMarketplaceManagerSkills(): array
    {
        return [
            $this->skill('marketplace.review-marketplace-agents', 'Review Marketplace Agents', 'platform', 'marketplace_manager', 'report', 'medium', false, ['marketplace.read_agents'], ['platform.agents', 'marketplace.submissions']),
            $this->skill('marketplace.validate-plugin-compliance', 'Validate Plugin Compliance', 'platform', 'marketplace_manager', 'action', 'high', true, ['marketplace.validate_plugins'], ['marketplace.plugins', 'compliance.rules']),
            $this->skill('marketplace.verify-agent-security', 'Verify Agent Security', 'platform', 'marketplace_manager', 'action', 'critical', true, ['marketplace.audit_security'], ['marketplace.agents', 'siem.scan']),
            $this->skill('marketplace.monitor-agent-ratings', 'Monitor Agent Ratings', 'platform', 'marketplace_manager', 'report', 'low', false, ['marketplace.read_ratings'], ['marketplace.reviews']),
            $this->skill('marketplace.track-agent-usage', 'Track Agent Usage', 'platform', 'marketplace_manager', 'report', 'low', false, ['marketplace.read_usage'], ['platform.deployments', 'platform.tasks']),
            $this->skill('marketplace.recommend-marketplace-improvements', 'Recommend Marketplace Improvements', 'platform', 'marketplace_manager', 'report', 'medium', false, ['marketplace.read_analytics'], ['marketplace.analytics', 'marketplace.ratings']),
            $this->skill('marketplace.approve-agent-publishing', 'Approve Agent Publishing', 'platform', 'marketplace_manager', 'action', 'critical', true, ['marketplace.approve_publishing'], ['marketplace.submissions', 'compliance.rules']),
            $this->skill('marketplace.manage-agent-lifecycle', 'Manage Agent Lifecycle', 'platform', 'marketplace_manager', 'action', 'high', true, ['marketplace.manage_lifecycle'], ['marketplace.agents', 'platform.deployments']),
        ];
    }

    // ─── Social Commerce & Customer Success (SCCS) ───────────────────────────

    private function socialCommerceSkills(): array
    {
        return [
            // Account & platform connectivity
            $this->skill('sccs.connect-social-account', 'Connect Social Account', 'marketing', 'social_commerce', 'action', 'medium', true, ['social.connect_accounts'], ['oauth.platforms']),

            // Content & publishing
            $this->skill('sccs.schedule-social-post', 'Schedule Social Post', 'marketing', 'social_commerce', 'action', 'medium', false, ['social.create_posts'], ['social.platforms', 'content.library']),
            $this->skill('sccs.approve-social-post', 'Approve Social Post', 'marketing', 'social_commerce', 'action', 'high', true, ['social.approve_posts'], ['social.platforms']),

            // Customer engagement
            $this->skill('sccs.respond-to-social-message', 'Respond to Social Message', 'marketing', 'social_commerce', 'action', 'high', false, ['social.send_messages'], ['social.inbox', 'crm.contacts'], ['ai_disclosure_required' => true, 'max_autonomous_replies_before_escalation' => 3]),
            $this->skill('sccs.escalate-conversation', 'Escalate Conversation', 'marketing', 'social_commerce', 'action', 'high', false, ['social.escalate'], ['social.conversations', 'crm.contacts']),

            // Lead management
            $this->skill('sccs.capture-lead', 'Capture Social Lead', 'sales', 'social_commerce', 'record', 'medium', false, ['social.capture_leads'], ['social.conversations', 'crm.leads']),
            $this->skill('sccs.qualify-lead', 'Qualify Social Lead', 'sales', 'social_commerce', 'action', 'medium', false, ['social.qualify_leads'], ['crm.leads', 'social.conversations']),
            $this->skill('sccs.record-social-conversion', 'Record Social Conversion', 'sales', 'social_commerce', 'record', 'medium', true, ['social.record_conversions'], ['crm.leads', 'sales.orders']),

            // Intelligence & monitoring
            $this->skill('sccs.analyze-sentiment', 'Analyze Conversation Sentiment', 'marketing', 'social_commerce', 'report', 'low', false, ['social.read_sentiment'], ['social.messages', 'nlp.sentiment']),
            $this->skill('sccs.monitor-brand-mentions', 'Monitor Brand Mentions', 'marketing', 'social_commerce', 'notification', 'medium', false, ['social.read_mentions'], ['social.platforms', 'brand.monitoring'], ['alert_on_negative_threshold' => 60]),
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Skill builder helper
    // ─────────────────────────────────────────────────────────────────────────

    private function skill(
        string $key,
        string $name,
        string $department,
        string $agentType,
        string $outputType,
        string $riskLevel,
        bool $approvalRequired,
        array $requiredPermissions = [],
        array $requiredDataSources = [],
        array $governanceRules = [],
    ): array {
        return [
            'key' => $key,
            'name' => $name,
            'description' => "Enterprise executable skill: {$name}",
            'layer' => 'enterprise',
            'category' => $this->categoryForDepartment($department),
            'department' => $department,
            'agent_type' => $agentType,
            'output_type' => $outputType,
            'risk_level' => $riskLevel,
            'approval_required' => $approvalRequired,
            'audit_required' => true,
            'delegation_capable' => ! $approvalRequired,
            'required_permissions' => $requiredPermissions,
            'required_data_sources' => $requiredDataSources,
            'governance_rules' => $governanceRules ?: null,
            'confidence_score' => $riskLevel === 'low' ? 85 : ($riskLevel === 'medium' ? 75 : ($riskLevel === 'high' ? 65 : 55)),
            'requires_ai' => true,
            'is_built_in' => true,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    // ─── Community-Sourced Skills ─────────────────────────────────────────────
    // Each skill is backed by an open-source project reference.
    // PHP implementations live in app/Skills/{layer}/.

    private function communitySourcedSkills(): array
    {
        return [
            // excel-data-processing — haris-musa/excel-mcp-server
            // Spreadsheet intelligence: parse, analyze, generate, export
            [
                'key' => 'excel-data-processing',
                'name' => 'Excel Data Processing',
                'description' => 'Parse, analyse, generate, and export tabular data (CSV/Excel/JSON). Powered by the excel-mcp-server pattern.',
                'layer' => 'platform',
                'category' => 'technical',
                'department' => 'it',
                'agent_type' => 'data_analyst',
                'output_type' => 'report',
                'risk_level' => 'low',
                'approval_required' => false,
                'audit_required' => true,
                'delegation_capable' => true,
                'required_permissions' => ['data.read', 'data.export'],
                'required_data_sources' => [],
                'governance_rules' => null,
                'confidence_score' => 85,
                'requires_ai' => false,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 100,
                'class' => ExcelDataProcessingSkill::class,
            ],

            // marketing-intelligence — coreyhaines31/marketingskills
            // Campaign analysis, content briefs, audience segmentation, ROI
            [
                'key' => 'marketing-intelligence',
                'name' => 'Marketing Intelligence',
                'description' => 'Analyse campaigns, generate content briefs, segment audiences, and measure marketing ROI.',
                'layer' => 'platform',
                'category' => 'marketing',
                'department' => 'marketing',
                'agent_type' => 'marketing_strategist',
                'output_type' => 'report',
                'risk_level' => 'low',
                'approval_required' => false,
                'audit_required' => true,
                'delegation_capable' => true,
                'required_permissions' => ['marketing.read', 'marketing.analyse'],
                'required_data_sources' => ['crm.campaigns', 'analytics.platform'],
                'governance_rules' => null,
                'confidence_score' => 80,
                'requires_ai' => true,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 101,
                'class' => MarketingIntelligenceSkill::class,
            ],

            // seo-optimization — agricidaniel/claude-seo
            // On-page analysis, keyword research, technical audit, content scoring
            [
                'key' => 'seo-optimization',
                'name' => 'SEO Optimization',
                'description' => 'Audit on-page SEO, research keywords, run technical checklists, and score content against search best practices.',
                'layer' => 'platform',
                'category' => 'marketing',
                'department' => 'marketing',
                'agent_type' => 'marketing_strategist',
                'output_type' => 'report',
                'risk_level' => 'low',
                'approval_required' => false,
                'audit_required' => true,
                'delegation_capable' => true,
                'required_permissions' => ['content.read', 'content.analyse'],
                'required_data_sources' => ['cms.pages', 'analytics.search'],
                'governance_rules' => null,
                'confidence_score' => 82,
                'requires_ai' => true,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 102,
                'class' => SeoOptimizationSkill::class,
            ],

            // seo-analyser — extracted from seo-optimization (content analysis actions)
            [
                'key' => 'seo-analyser',
                'name' => 'SEO Analyser',
                'description' => 'Analyse on-page SEO, research keywords, and score content against search best practices.',
                'layer' => 'platform',
                'category' => 'marketing',
                'department' => 'marketing',
                'agent_type' => 'marketing_strategist',
                'output_type' => 'report',
                'risk_level' => 'low',
                'approval_required' => false,
                'audit_required' => true,
                'delegation_capable' => true,
                'required_permissions' => ['content.read', 'content.analyse'],
                'required_data_sources' => ['cms.pages', 'analytics.search'],
                'governance_rules' => null,
                'confidence_score' => 82,
                'requires_ai' => true,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 103,
                'class' => SeoAnalyserSkill::class,
            ],

            // seo-audit — extracted from seo-optimization (technical checklist action)
            [
                'key' => 'seo-audit',
                'name' => 'SEO Technical Audit',
                'description' => 'Run a technical SEO checklist for HTTPS, canonical tags, structured data, and Core Web Vitals compliance.',
                'layer' => 'platform',
                'category' => 'marketing',
                'department' => 'marketing',
                'agent_type' => 'marketing_strategist',
                'output_type' => 'report',
                'risk_level' => 'low',
                'approval_required' => false,
                'audit_required' => true,
                'delegation_capable' => true,
                'required_permissions' => ['content.read', 'content.analyse'],
                'required_data_sources' => ['cms.pages'],
                'governance_rules' => null,
                'confidence_score' => 88,
                'requires_ai' => false,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 104,
                'class' => SeoAuditSkill::class,
            ],

            // video-scripting — remotion-dev/remotion
            // Script, storyboard, scene breakdown, Remotion render config
            [
                'key' => 'video-scripting',
                'name' => 'Video Scripting',
                'description' => 'Generate timed video scripts, storyboards, scene breakdowns, and Remotion-compatible render configs from a brief.',
                'layer' => 'platform',
                'category' => 'marketing',
                'department' => 'marketing',
                'agent_type' => 'content_creator',
                'output_type' => 'record',
                'risk_level' => 'low',
                'approval_required' => false,
                'audit_required' => true,
                'delegation_capable' => true,
                'required_permissions' => ['content.create'],
                'required_data_sources' => [],
                'governance_rules' => null,
                'confidence_score' => 78,
                'requires_ai' => true,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 103,
                'class' => VideoScriptingSkill::class,
            ],

            // context-engineering — muratcankoylan/agent-skills-for-context-engineering
            // Optimize, compress, prioritize, and inject agent context
            [
                'key' => 'context-engineering',
                'name' => 'Context Engineering',
                'description' => 'Optimise, compress, prioritise, and inject memory into agent context windows for maximum task relevance.',
                'layer' => 'core',
                'category' => 'technical',
                'department' => 'platform',
                'agent_type' => 'platform_agent',
                'output_type' => 'record',
                'risk_level' => 'low',
                'approval_required' => false,
                'audit_required' => false,
                'delegation_capable' => false,
                'required_permissions' => ['memory.read'],
                'required_data_sources' => ['agent.memory'],
                'governance_rules' => null,
                'confidence_score' => 90,
                'requires_ai' => false,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 104,
                'class' => ContextEngineeringSkill::class,
            ],

            // mass-content-generation — massgen/massgen
            // Batch generation, template building, quality validation, distribution
            [
                'key' => 'mass-content-generation',
                'name' => 'Mass Content Generation',
                'description' => 'Generate, validate, and distribute content at scale using parameterised templates and quality guardrails.',
                'layer' => 'platform',
                'category' => 'marketing',
                'department' => 'marketing',
                'agent_type' => 'content_creator',
                'output_type' => 'record',
                'risk_level' => 'medium',
                'approval_required' => true,
                'audit_required' => true,
                'delegation_capable' => true,
                'required_permissions' => ['content.create', 'content.publish'],
                'required_data_sources' => ['cms.templates'],
                'governance_rules' => ['max_batch_size' => 500, 'quality_check_required' => true],
                'confidence_score' => 75,
                'requires_ai' => true,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 105,
                'class' => MassContentGenerationSkill::class,
            ],

            // superpowers — obra/superpowers
            // Introspect, extend, combine, and augment agent capabilities at runtime
            [
                'key' => 'superpowers',
                'name' => 'Superpowers',
                'description' => 'Meta-capability framework: introspect skills, dynamically activate capabilities, combine multiple skills, and augment inputs with contextual intelligence.',
                'layer' => 'meta',
                'category' => 'governance',
                'department' => 'platform',
                'agent_type' => 'meta_agent',
                'output_type' => 'record',
                'risk_level' => 'medium',
                'approval_required' => false,
                'audit_required' => true,
                'delegation_capable' => false,
                'required_permissions' => ['skills.read', 'skills.assign'],
                'required_data_sources' => [],
                'governance_rules' => ['cannot_activate_critical_risk_skills' => true],
                'confidence_score' => 88,
                'requires_ai' => false,
                'is_built_in' => true,
                'is_active' => true,
                'sort_order' => 106,
                'class' => SuperpowersSkill::class,
            ],
        ];
    }

    private function categoryForDepartment(string $department): string
    {
        return match ($department) {
            'finance' => 'financial',
            'hr' => 'workforce',
            'it' => 'technical',
            'sales' => 'commercial',
            'marketing' => 'marketing',
            'operations' => 'operational',
            'executive' => 'strategic',
            'platform' => 'governance',
            'social_commerce' => 'social_commerce',
            default => 'general',
        };
    }
}
