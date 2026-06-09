<?php

namespace Database\Seeders;

use App\Models\Agent;
use App\Models\AgentCategory;
use App\Models\AgentDepartment;
use App\Models\AgentPersona;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AgentPlatformSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSubscriptionPlans();
        $this->seedDepartments();
        $this->seedCategories();
        $this->seedAgents();
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Perfect for small teams getting started with AI agents.',
                'price' => 49.00,
                'yearly_price' => 490.00,
                'max_agents' => 3,
                'max_users' => 5,
                'max_departments' => 2,
                'max_workflows' => 3,
                'monthly_token_quota' => 500000,
                'features' => ['Basic Agents', 'Audit Logs', 'Email Support'],
                'is_featured' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'slug' => 'professional',
                'description' => 'For growing businesses that need more power.',
                'price' => 149.00,
                'yearly_price' => 1490.00,
                'max_agents' => 10,
                'max_users' => 25,
                'max_departments' => 8,
                'max_workflows' => 15,
                'monthly_token_quota' => 2000000,
                'features' => ['All Agents', 'Governance Suite', 'Workflows', 'Priority Support', 'API Access'],
                'is_featured' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Full-scale AI workforce for large organizations.',
                'price' => 499.00,
                'yearly_price' => 4990.00,
                'max_agents' => -1, // unlimited
                'max_users' => -1,
                'max_departments' => -1,
                'max_workflows' => -1,
                'monthly_token_quota' => 20000000,
                'features' => ['Unlimited Agents', 'Digital Immune System', 'SSO', 'SAML', 'Custom Models', 'SLA', 'Dedicated Support', 'White Label'],
                'is_featured' => false,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::firstOrCreate(['slug' => $plan['slug']], $plan);
        }
    }

    private function seedDepartments(): void
    {
        $departments = [
            ['name' => 'Executive', 'slug' => 'executive', 'icon' => 'briefcase', 'color' => 'purple', 'sort_order' => 1],
            ['name' => 'Finance', 'slug' => 'finance', 'icon' => 'currency-dollar', 'color' => 'green', 'sort_order' => 2],
            ['name' => 'Human Resources', 'slug' => 'hr', 'icon' => 'users', 'color' => 'blue', 'sort_order' => 3],
            ['name' => 'Information Technology', 'slug' => 'it', 'icon' => 'chip', 'color' => 'indigo', 'sort_order' => 4],
            ['name' => 'Operations', 'slug' => 'operations', 'icon' => 'cog', 'color' => 'gray', 'sort_order' => 5],
            ['name' => 'Sales', 'slug' => 'sales', 'icon' => 'trending-up', 'color' => 'orange', 'sort_order' => 6],
            ['name' => 'Marketing', 'slug' => 'marketing', 'icon' => 'speakerphone', 'color' => 'pink', 'sort_order' => 7],
            ['name' => 'Legal', 'slug' => 'legal', 'icon' => 'scale', 'color' => 'red', 'sort_order' => 8],
            ['name' => 'Customer Service', 'slug' => 'customer-service', 'icon' => 'support', 'color' => 'teal', 'sort_order' => 9],
        ];

        foreach ($departments as $dept) {
            AgentDepartment::firstOrCreate(['slug' => $dept['slug']], array_merge($dept, ['is_active' => true]));
        }
    }

    private function seedCategories(): void
    {
        $categories = [
            ['name' => 'Analysis & Research', 'slug' => 'analysis', 'color' => 'blue', 'sort_order' => 1],
            ['name' => 'Automation', 'slug' => 'automation', 'color' => 'green', 'sort_order' => 2],
            ['name' => 'Communication', 'slug' => 'communication', 'color' => 'purple', 'sort_order' => 3],
            ['name' => 'Decision Support', 'slug' => 'decision-support', 'color' => 'yellow', 'sort_order' => 4],
            ['name' => 'Content Generation', 'slug' => 'content', 'color' => 'pink', 'sort_order' => 5],
            ['name' => 'Monitoring & Alerts', 'slug' => 'monitoring', 'color' => 'red', 'sort_order' => 6],
            ['name' => 'Executive Leadership', 'slug' => 'executive', 'color' => 'indigo', 'sort_order' => 7],
        ];

        foreach ($categories as $cat) {
            AgentCategory::firstOrCreate(['slug' => $cat['slug']], array_merge($cat, ['is_active' => true]));
        }
    }

    private function seedAgents(): void
    {
        $agents = $this->getAgentDefinitions();

        foreach ($agents as $agentData) {
            $personaSystemPrompt = $agentData['system_prompt'] ?? null;
            unset($agentData['system_prompt']);

            $agent = Agent::firstOrCreate(
                ['slug' => $agentData['slug']],
                array_merge($agentData, [
                    'uuid' => (string) Str::uuid(),
                    'status' => 'active',
                    'avg_rating' => rand(42, 50) / 10,
                    'review_count' => rand(10, 500),
                    'total_deployments' => rand(5, 1000),
                    'accuracy_score' => rand(75, 98),
                    'reliability_score' => rand(80, 99),
                    'satisfaction_score' => rand(78, 97),
                    'version' => '1.0.0',
                ])
            );

            if ($personaSystemPrompt) {
                AgentPersona::firstOrCreate(
                    ['agent_id' => $agent->id, 'name' => 'default'],
                    [
                        'system_prompt' => $personaSystemPrompt,
                        'temperature' => 0.7,
                        'max_tokens' => 4096,
                        'is_default' => true,
                    ]
                );
            }
        }
    }

    private function getAgentDefinitions(): array
    {
        $execDept = AgentDepartment::where('slug', 'executive')->first()?->id;
        $financeDept = AgentDepartment::where('slug', 'finance')->first()?->id;
        $hrDept = AgentDepartment::where('slug', 'hr')->first()?->id;
        $itDept = AgentDepartment::where('slug', 'it')->first()?->id;
        $opsDept = AgentDepartment::where('slug', 'operations')->first()?->id;
        $salesDept = AgentDepartment::where('slug', 'sales')->first()?->id;
        $marketingDept = AgentDepartment::where('slug', 'marketing')->first()?->id;
        $legalDept = AgentDepartment::where('slug', 'legal')->first()?->id;
        $csDept = AgentDepartment::where('slug', 'customer-service')->first()?->id;

        $analysisCat = AgentCategory::where('slug', 'analysis')->first()?->id;
        $automationCat = AgentCategory::where('slug', 'automation')->first()?->id;
        $decisionCat = AgentCategory::where('slug', 'decision-support')->first()?->id;
        $contentCat = AgentCategory::where('slug', 'content')->first()?->id;
        $monitorCat = AgentCategory::where('slug', 'monitoring')->first()?->id;
        $execCat = AgentCategory::where('slug', 'executive')->first()?->id;
        $commCat = AgentCategory::where('slug', 'communication')->first()?->id;

        return [
            // ---- EXECUTIVE ----
            [
                'name' => 'CEO Agent',
                'slug' => 'ceo-agent',
                'tagline' => 'Strategic leadership and organizational direction',
                'description' => 'Provides C-level strategic guidance, analyzes business performance, identifies opportunities, and helps with executive decision-making.',
                'department_id' => $execDept,
                'category_id' => $execCat,
                'agent_type' => 'executive',
                'primary_model' => 'gpt-4o',
                'model_provider' => 'openai',
                'capabilities' => ['Strategic planning', 'Business analysis', 'KPI monitoring', 'Board reporting', 'Risk assessment', 'M&A analysis'],
                'limitations' => ['Cannot legally bind the company', 'Requires human approval for major decisions'],
                'skills' => ['Strategic thinking', 'Financial analysis', 'Leadership coaching', 'Market analysis'],
                'knowledge_areas' => ['Business strategy', 'Corporate governance', 'Financial management', 'Organizational behavior'],
                'pricing_model' => 'subscription',
                'base_price' => 299.00,
                'billing_cycle' => 'monthly',
                'default_deployment_mode' => 'advisory',
                'is_featured' => true,
                'is_verified' => true,
                'tags' => ['executive', 'strategy', 'leadership', 'C-suite'],
                'system_prompt' => 'You are a world-class CEO-level AI advisor. Your role is to provide strategic guidance, analyze business situations with senior executive perspective, and help organizations make informed decisions. You focus on long-term value creation, stakeholder management, and organizational effectiveness. Always be transparent about confidence levels. Never make irreversible recommendations without explicit human approval.',
            ],
            [
                'name' => 'CFO Agent',
                'slug' => 'cfo-agent',
                'tagline' => 'Financial leadership and strategic financial planning',
                'description' => 'Delivers CFO-level financial analysis, budgeting support, cash flow optimization, and financial risk management.',
                'department_id' => $execDept,
                'category_id' => $analysisCat,
                'agent_type' => 'executive',
                'primary_model' => 'gpt-4o',
                'model_provider' => 'openai',
                'capabilities' => ['Financial modeling', 'Budget analysis', 'Cash flow forecasting', 'Risk assessment', 'Investor reporting', 'Cost optimization'],
                'limitations' => ['Cannot sign financial documents', 'Requires human approval for commitments'],
                'base_price' => 249.00,
                'billing_cycle' => 'monthly',
                'default_deployment_mode' => 'advisory',
                'is_featured' => true,
                'is_verified' => true,
                'tags' => ['finance', 'CFO', 'executive', 'financial planning'],
            ],
            [
                'name' => 'CTO Agent',
                'slug' => 'cto-agent',
                'tagline' => 'Technology strategy and architecture leadership',
                'description' => 'Provides CTO-level guidance on technology strategy, architecture decisions, digital transformation, and technical team leadership.',
                'department_id' => $execDept,
                'category_id' => $decisionCat,
                'agent_type' => 'executive',
                'primary_model' => 'gpt-4o',
                'model_provider' => 'openai',
                'capabilities' => ['Tech stack evaluation', 'Architecture review', 'Digital transformation', 'Build vs buy analysis', 'Technology roadmap', 'Vendor assessment'],
                'base_price' => 249.00,
                'default_deployment_mode' => 'advisory',
                'is_featured' => false,
                'tags' => ['CTO', 'technology', 'architecture', 'digital transformation'],
            ],

            // ---- FINANCE ----
            [
                'name' => 'Financial Analyst',
                'slug' => 'financial-analyst',
                'tagline' => 'In-depth financial analysis and reporting',
                'description' => 'Analyzes financial statements, performs ratio analysis, builds financial models, and generates comprehensive financial reports.',
                'department_id' => $financeDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'primary_model' => 'gpt-4o',
                'capabilities' => ['P&L analysis', 'Balance sheet review', 'Financial ratios', 'DCF modeling', 'Variance analysis', 'Report generation'],
                'base_price' => 79.00,
                'default_deployment_mode' => 'semi-autonomous',
                'is_featured' => true,
                'tags' => ['finance', 'analysis', 'reporting', 'financial models'],
            ],
            [
                'name' => 'Budget Planner',
                'slug' => 'budget-planner',
                'tagline' => 'Intelligent budget planning and optimization',
                'description' => 'Assists with budget creation, allocation optimization, scenario planning, and budget vs actual analysis.',
                'department_id' => $financeDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'capabilities' => ['Budget creation', 'Scenario planning', 'Cost allocation', 'Variance reporting', 'Forecast adjustment'],
                'base_price' => 69.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['finance', 'budgeting', 'planning'],
            ],
            [
                'name' => 'Risk Analyst',
                'slug' => 'risk-analyst',
                'tagline' => 'Enterprise risk identification and mitigation',
                'description' => 'Identifies, quantifies, and helps mitigate financial, operational, and strategic risks across the organization.',
                'department_id' => $financeDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'capabilities' => ['Risk identification', 'Risk scoring', 'Mitigation strategies', 'Regulatory compliance', 'Stress testing'],
                'base_price' => 89.00,
                'default_deployment_mode' => 'advisory',
                'is_featured' => true,
                'tags' => ['risk', 'compliance', 'finance', 'risk management'],
            ],

            // ---- HR ----
            [
                'name' => 'Recruiter Agent',
                'slug' => 'recruiter-agent',
                'tagline' => 'AI-powered talent acquisition and screening',
                'description' => 'Handles job posting creation, CV screening, candidate ranking, interview scheduling, and hiring pipeline management.',
                'department_id' => $hrDept,
                'category_id' => $automationCat,
                'agent_type' => 'operational',
                'capabilities' => ['Job description writing', 'CV screening', 'Candidate ranking', 'Interview scheduling', 'Offer letter drafting'],
                'base_price' => 59.00,
                'default_deployment_mode' => 'semi-autonomous',
                'is_featured' => true,
                'tags' => ['HR', 'recruiting', 'talent acquisition', 'hiring'],
            ],
            [
                'name' => 'Performance Agent',
                'slug' => 'performance-agent',
                'tagline' => 'Employee performance tracking and development',
                'description' => 'Manages performance review processes, identifies development areas, tracks KPIs, and supports employee growth planning.',
                'department_id' => $hrDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'capabilities' => ['Performance reviews', 'KPI tracking', 'Development planning', 'Feedback analysis', '360 assessment'],
                'base_price' => 49.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['HR', 'performance', 'employee development'],
            ],
            [
                'name' => 'Training Agent',
                'slug' => 'training-agent',
                'tagline' => 'Personalized employee training and onboarding',
                'description' => 'Creates personalized training plans, delivers onboarding content, tracks learning progress, and identifies skill gaps.',
                'department_id' => $hrDept,
                'category_id' => $contentCat,
                'agent_type' => 'operational',
                'capabilities' => ['Training plan creation', 'Onboarding automation', 'Skill gap analysis', 'Learning path design', 'Progress tracking'],
                'base_price' => 49.00,
                'default_deployment_mode' => 'semi-autonomous',
                'tags' => ['HR', 'training', 'onboarding', 'learning'],
            ],

            // ---- IT ----
            [
                'name' => 'IT Support Agent',
                'slug' => 'it-support-agent',
                'tagline' => 'Intelligent IT helpdesk and issue resolution',
                'description' => 'Provides first-line IT support, troubleshoots common issues, escalates when needed, and reduces ticket resolution time.',
                'department_id' => $itDept,
                'category_id' => $automationCat,
                'agent_type' => 'operational',
                'capabilities' => ['Ticket triage', 'Issue diagnosis', 'Self-service guidance', 'Escalation routing', 'Knowledge base search', 'Password reset workflows'],
                'base_price' => 39.00,
                'default_deployment_mode' => 'semi-autonomous',
                'is_featured' => true,
                'tags' => ['IT', 'support', 'helpdesk', 'automation'],
            ],
            [
                'name' => 'Cybersecurity Agent',
                'slug' => 'cybersecurity-agent',
                'tagline' => 'Proactive security monitoring and threat analysis',
                'description' => 'Monitors security events, analyzes threats, assesses vulnerabilities, and provides security recommendations.',
                'department_id' => $itDept,
                'category_id' => $monitorCat,
                'agent_type' => 'specialist',
                'capabilities' => ['Threat analysis', 'Vulnerability assessment', 'Security audit', 'Incident response guidance', 'Compliance review', 'Security training'],
                'base_price' => 99.00,
                'default_deployment_mode' => 'advisory',
                'is_featured' => true,
                'is_enterprise_only' => false,
                'tags' => ['security', 'cybersecurity', 'IT', 'compliance'],
            ],
            [
                'name' => 'Cloud Architect Agent',
                'slug' => 'cloud-architect-agent',
                'tagline' => 'Cloud infrastructure design and optimization',
                'description' => 'Helps design, optimize, and manage cloud infrastructure across AWS, Azure, and GCP for performance and cost efficiency.',
                'department_id' => $itDept,
                'category_id' => $decisionCat,
                'agent_type' => 'specialist',
                'capabilities' => ['Architecture review', 'Cost optimization', 'Migration planning', 'Security assessment', 'Performance tuning', 'IaC templates'],
                'base_price' => 89.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['cloud', 'AWS', 'Azure', 'infrastructure', 'architecture'],
            ],

            // ---- OPERATIONS ----
            [
                'name' => 'Operations Manager Agent',
                'slug' => 'operations-manager-agent',
                'tagline' => 'Operational efficiency and process optimization',
                'description' => 'Monitors operational KPIs, identifies bottlenecks, coordinates cross-functional tasks, and drives continuous improvement.',
                'department_id' => $opsDept,
                'category_id' => $analysisCat,
                'agent_type' => 'operational',
                'capabilities' => ['KPI monitoring', 'Process mapping', 'Bottleneck identification', 'SOP creation', 'Workflow optimization', 'Resource allocation'],
                'base_price' => 79.00,
                'default_deployment_mode' => 'semi-autonomous',
                'is_featured' => true,
                'tags' => ['operations', 'efficiency', 'process improvement', 'KPIs'],
            ],
            [
                'name' => 'Supply Chain Agent',
                'slug' => 'supply-chain-agent',
                'tagline' => 'Supply chain visibility and optimization',
                'description' => 'Monitors supply chain health, identifies disruptions, optimizes procurement, and manages vendor relationships.',
                'department_id' => $opsDept,
                'category_id' => $monitorCat,
                'agent_type' => 'operational',
                'capabilities' => ['Inventory analysis', 'Demand forecasting', 'Supplier risk assessment', 'Lead time optimization', 'Cost reduction'],
                'base_price' => 79.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['supply chain', 'logistics', 'procurement', 'operations'],
            ],

            // ---- SALES ----
            [
                'name' => 'Lead Generation Agent',
                'slug' => 'lead-generation-agent',
                'tagline' => 'Intelligent B2B lead discovery and qualification',
                'description' => 'Identifies, researches, and qualifies sales leads based on ICP criteria, enriches contact data, and prioritizes outreach.',
                'department_id' => $salesDept,
                'category_id' => $automationCat,
                'agent_type' => 'operational',
                'capabilities' => ['ICP matching', 'Lead research', 'Contact enrichment', 'LinkedIn prospecting', 'Lead scoring', 'Outreach sequencing'],
                'base_price' => 69.00,
                'default_deployment_mode' => 'semi-autonomous',
                'is_featured' => true,
                'tags' => ['sales', 'leads', 'B2B', 'prospecting', 'CRM'],
            ],
            [
                'name' => 'Sales Forecast Agent',
                'slug' => 'sales-forecast-agent',
                'tagline' => 'Accurate revenue forecasting and pipeline analysis',
                'description' => 'Analyzes sales pipeline, forecasts revenue with confidence scores, identifies at-risk deals, and recommends acceleration strategies.',
                'department_id' => $salesDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'capabilities' => ['Pipeline analysis', 'Revenue forecasting', 'Deal risk scoring', 'Win/loss analysis', 'Quota tracking'],
                'base_price' => 59.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['sales', 'forecasting', 'revenue', 'pipeline'],
            ],

            // ---- MARKETING ----
            [
                'name' => 'Content Agent',
                'slug' => 'content-agent',
                'tagline' => 'High-quality content creation at scale',
                'description' => 'Creates blog posts, emails, social copy, whitepapers, and marketing materials aligned with brand voice and SEO best practices.',
                'department_id' => $marketingDept,
                'category_id' => $contentCat,
                'agent_type' => 'operational',
                'capabilities' => ['Blog writing', 'Email copy', 'Social media content', 'SEO optimization', 'Brand voice alignment', 'A/B test variants'],
                'base_price' => 49.00,
                'default_deployment_mode' => 'semi-autonomous',
                'is_featured' => true,
                'tags' => ['marketing', 'content', 'writing', 'SEO', 'copywriting'],
            ],
            [
                'name' => 'SEO Agent',
                'slug' => 'seo-agent',
                'tagline' => 'Search engine optimization and organic growth',
                'description' => 'Conducts keyword research, analyzes content gaps, optimizes on-page SEO, tracks rankings, and provides actionable recommendations.',
                'department_id' => $marketingDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'capabilities' => ['Keyword research', 'Content gap analysis', 'On-page optimization', 'Competitor analysis', 'Backlink strategy', 'Technical SEO audit'],
                'base_price' => 59.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['SEO', 'marketing', 'content', 'organic growth'],
            ],
            [
                'name' => 'Market Research Agent',
                'slug' => 'market-research-agent',
                'tagline' => 'Deep market intelligence and competitive analysis',
                'description' => 'Conducts comprehensive market research, competitor analysis, customer insights, and trend identification.',
                'department_id' => $marketingDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'capabilities' => ['Competitive analysis', 'Market sizing', 'Trend analysis', 'Customer segmentation', 'SWOT analysis', 'Industry reports'],
                'base_price' => 79.00,
                'default_deployment_mode' => 'advisory',
                'is_featured' => true,
                'tags' => ['market research', 'competitive intelligence', 'strategy', 'marketing'],
            ],

            // ---- LEGAL ----
            [
                'name' => 'Contract Review Agent',
                'slug' => 'contract-review-agent',
                'tagline' => 'AI-powered contract analysis and risk flagging',
                'description' => 'Reviews contracts, identifies risk clauses, flags non-standard terms, suggests amendments, and creates summaries for stakeholders.',
                'department_id' => $legalDept,
                'category_id' => $analysisCat,
                'agent_type' => 'analytical',
                'capabilities' => ['Contract analysis', 'Risk clause identification', 'Redline suggestions', 'Summary generation', 'Compliance checking', 'Version comparison'],
                'base_price' => 99.00,
                'default_deployment_mode' => 'advisory',
                'is_featured' => true,
                'tags' => ['legal', 'contracts', 'compliance', 'risk'],
            ],
            [
                'name' => 'Compliance Agent',
                'slug' => 'compliance-agent',
                'tagline' => 'Regulatory compliance monitoring and guidance',
                'description' => 'Monitors regulatory requirements, tracks compliance obligations, identifies gaps, and provides remediation guidance.',
                'department_id' => $legalDept,
                'category_id' => $monitorCat,
                'agent_type' => 'specialist',
                'capabilities' => ['Regulatory monitoring', 'Compliance gap analysis', 'Policy review', 'GDPR/POPIA guidance', 'Audit preparation', 'Risk reporting'],
                'base_price' => 109.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['compliance', 'regulatory', 'GDPR', 'legal', 'risk'],
            ],

            // ---- CUSTOMER SERVICE ----
            [
                'name' => 'Customer Support Agent',
                'slug' => 'customer-support-agent',
                'tagline' => '24/7 intelligent customer support',
                'description' => 'Handles customer inquiries, resolves common issues, escalates complex cases, and maintains high satisfaction scores.',
                'department_id' => $csDept,
                'category_id' => $commCat,
                'agent_type' => 'operational',
                'capabilities' => ['Query resolution', 'FAQ handling', 'Ticket creation', 'Escalation routing', 'Sentiment detection', 'Multi-language support'],
                'base_price' => 39.00,
                'default_deployment_mode' => 'semi-autonomous',
                'is_featured' => true,
                'tags' => ['customer service', 'support', 'helpdesk', 'customer success'],
            ],
            [
                'name' => 'Customer Success Agent',
                'slug' => 'customer-success-agent',
                'tagline' => 'Proactive customer success and retention',
                'description' => 'Monitors customer health scores, identifies churn risk, drives expansion opportunities, and creates success plans.',
                'department_id' => $csDept,
                'category_id' => $analysisCat,
                'agent_type' => 'operational',
                'capabilities' => ['Health score tracking', 'Churn prediction', 'Expansion opportunity identification', 'QBR preparation', 'NPS analysis'],
                'base_price' => 59.00,
                'default_deployment_mode' => 'advisory',
                'tags' => ['customer success', 'retention', 'churn', 'NPS'],
            ],
        ];

        return $agents;
    }
}
