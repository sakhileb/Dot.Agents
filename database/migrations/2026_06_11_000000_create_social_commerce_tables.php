<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Social Accounts ──────────────────────────────────────────────────
        // Connected social media platform credentials per organization
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('platform');                         // facebook|instagram|linkedin|x|tiktok|whatsapp|telegram|youtube
            $table->string('platform_account_id');             // ID on the platform side
            $table->string('account_name');
            $table->string('account_handle')->nullable();
            $table->string('account_type')->default('page');   // page|profile|group|channel
            $table->string('avatar_url')->nullable();
            $table->text('access_token')->nullable();           // encrypted
            $table->text('refresh_token')->nullable();          // encrypted
            $table->timestamp('token_expires_at')->nullable();
            $table->json('scopes')->nullable();                 // granted OAuth scopes
            $table->json('settings')->nullable();               // platform-specific config
            $table->string('status')->default('active');        // active|disconnected|expired|suspended
            $table->boolean('is_primary')->default(false);
            $table->timestamp('connected_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'platform', 'status']);
            $table->unique(['organization_id', 'platform', 'platform_account_id'], 'unique_org_platform_account');
        });

        // ── Social Pages ─────────────────────────────────────────────────────
        // Pages/profiles/channels managed through a social account
        Schema::create('social_pages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();

            $table->string('platform_page_id');
            $table->string('name');
            $table->string('handle')->nullable();
            $table->string('category')->nullable();
            $table->text('about')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('cover_url')->nullable();
            $table->string('website')->nullable();
            $table->unsignedBigInteger('follower_count')->default(0);
            $table->unsignedBigInteger('following_count')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);
            $table->json('metrics')->nullable();                // platform-specific analytics
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'social_account_id']);
        });

        // ── Social Posts ─────────────────────────────────────────────────────
        // Posts created, scheduled, or published by agents
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_page_id')->constrained('social_pages')->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable();       // FK added after campaigns table

            $table->string('platform_post_id')->nullable();     // ID once published
            $table->string('post_type')->default('post');       // post|reel|story|tweet|video|article
            $table->text('content');
            $table->json('media_urls')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('mentions')->nullable();
            $table->string('link_url')->nullable();
            $table->string('status')->default('draft');         // draft|scheduled|published|failed|deleted
            $table->string('approval_status')->default('pending'); // pending|approved|rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('published_at')->nullable();

            // Engagement metrics (updated from platform)
            $table->unsignedBigInteger('like_count')->default(0);
            $table->unsignedBigInteger('comment_count')->default(0);
            $table->unsignedBigInteger('share_count')->default(0);
            $table->unsignedBigInteger('view_count')->default(0);
            $table->unsignedBigInteger('click_count')->default(0);
            $table->decimal('engagement_rate', 5, 2)->default(0);

            $table->json('ai_metadata')->nullable();            // AI generation context
            $table->json('platform_response')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'scheduled_at']);
            $table->index(['social_page_id', 'status']);
        });

        // ── Social Campaigns ─────────────────────────────────────────────────
        // Coordinated multi-post, multi-channel marketing campaigns
        Schema::create('social_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('campaign_type')->default('awareness'); // awareness|lead_gen|sales|retention|support
            $table->string('status')->default('draft');            // draft|active|paused|completed|archived
            $table->json('target_platforms')->nullable();
            $table->json('target_audience')->nullable();
            $table->json('goals')->nullable();                     // {metric: target_value}
            $table->decimal('budget', 10, 2)->nullable();
            $table->decimal('spent', 10, 2)->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->json('metrics')->nullable();                   // aggregate campaign metrics
            $table->json('ai_strategy')->nullable();               // AI-generated campaign strategy
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });

        // Add FK from social_posts to social_campaigns
        Schema::table('social_posts', function (Blueprint $table) {
            $table->foreign('campaign_id')->references('id')->on('social_campaigns')->nullOnDelete();
        });

        // ── Social Conversations ─────────────────────────────────────────────
        // Threaded conversations across all channels (DMs, comments, reviews)
        Schema::create('social_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->foreignId('social_page_id')->nullable()->constrained('social_pages')->nullOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('platform_conversation_id')->nullable();
            $table->string('platform');                         // facebook|instagram|linkedin|x|tiktok|whatsapp|telegram|youtube
            $table->string('channel_type');                     // dm|comment|mention|review|group_message
            $table->string('contact_platform_id');              // external contact ID
            $table->string('contact_name')->nullable();
            $table->string('contact_handle')->nullable();
            $table->string('contact_avatar')->nullable();
            $table->string('status')->default('open');          // open|in_progress|waiting|resolved|escalated|closed
            $table->string('priority')->default('normal');      // low|normal|high|urgent|vip
            $table->string('sentiment')->default('neutral');    // positive|neutral|concerned|frustrated|angry|vip
            $table->decimal('sentiment_score', 5, 2)->default(0);
            $table->string('intent')->nullable();               // browsing|interested|considering|ready_to_buy|high_intent
            $table->decimal('intent_score', 5, 2)->default(0);
            $table->boolean('requires_human')->default(false);
            $table->boolean('is_lead')->default(false);
            $table->boolean('is_escalated')->default(false);
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('message_count')->default(0);
            $table->integer('response_time_seconds')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'platform', 'status']);
            $table->index(['organization_id', 'sentiment', 'priority']);
            $table->index(['organization_id', 'intent', 'intent_score']);
        });

        // ── Social Messages ──────────────────────────────────────────────────
        // Individual messages within a conversation
        Schema::create('social_messages', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_conversation_id')->constrained('social_conversations')->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('platform_message_id')->nullable();
            $table->string('direction');                        // inbound|outbound
            $table->string('sender_type');                      // contact|agent|human_agent|system
            $table->string('sender_id')->nullable();            // user ID or agent deployment ID
            $table->string('sender_name')->nullable();
            $table->text('content');
            $table->json('media_attachments')->nullable();
            $table->string('message_type')->default('text');    // text|image|video|document|voice|template|carousel
            $table->string('status')->default('sent');          // pending|sent|delivered|read|failed
            $table->boolean('is_ai_generated')->default(false);
            $table->boolean('requires_approval')->default(false);
            $table->string('approval_status')->nullable();      // pending|approved|rejected
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->decimal('ai_confidence', 5, 2)->nullable();
            $table->json('ai_context')->nullable();             // reasoning, context used
            $table->boolean('was_disclosed_as_ai')->default(false); // AI transparency disclosure
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['social_conversation_id', 'direction', 'created_at']);
            $table->index(['organization_id', 'is_ai_generated', 'requires_approval']);
        });

        // ── Social Leads ─────────────────────────────────────────────────────
        // Leads captured from social interactions
        Schema::create('social_leads', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_conversation_id')->nullable()->constrained('social_conversations')->nullOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            // Contact info captured
            $table->string('platform');
            $table->string('contact_platform_id');
            $table->string('contact_name')->nullable();
            $table->string('contact_handle')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('location')->nullable();

            // Lead qualification
            $table->string('status')->default('new');           // new|contacted|qualified|unqualified|converted|lost
            $table->string('stage')->default('awareness');      // awareness|interest|consideration|intent|evaluation|purchase
            $table->string('intent_level')->default('browsing'); // browsing|interested|considering|ready_to_buy|high_intent
            $table->decimal('lead_score', 5, 2)->default(0);   // 0-100
            $table->decimal('intent_score', 5, 2)->default(0); // 0-100
            $table->string('priority')->default('normal');      // low|normal|high|hot

            // Recommended actions
            $table->json('recommended_actions')->nullable();    // offer_discount|book_demo|transfer_to_sales
            $table->boolean('crm_synced')->default(false);
            $table->string('crm_platform')->nullable();         // salesforce|hubspot|zoho|dynamics
            $table->string('crm_record_id')->nullable();        // ID in the external CRM
            $table->timestamp('crm_synced_at')->nullable();

            $table->json('custom_fields')->nullable();
            $table->json('interaction_history')->nullable();    // summary of touchpoints
            $table->timestamp('first_touch_at')->nullable();
            $table->timestamp('last_touch_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'status', 'lead_score']);
            $table->index(['organization_id', 'intent_level', 'priority']);
            $table->index(['platform', 'contact_platform_id']);
        });

        // ── Social Reviews ───────────────────────────────────────────────────
        // Customer reviews and reputation data
        Schema::create('social_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->constrained('social_accounts')->cascadeOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('platform');
            $table->string('platform_review_id');
            $table->string('reviewer_name')->nullable();
            $table->string('reviewer_id')->nullable();
            $table->decimal('rating', 3, 1)->nullable();        // 1.0 - 5.0
            $table->text('review_text')->nullable();
            $table->string('sentiment')->default('neutral');    // positive|neutral|negative
            $table->decimal('sentiment_score', 5, 2)->default(0);
            $table->boolean('has_response')->default(false);
            $table->text('response_text')->nullable();
            $table->boolean('response_is_ai_generated')->default(false);
            $table->foreignId('responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('responded_at')->nullable();
            $table->boolean('requires_escalation')->default(false);
            $table->boolean('is_verified_purchase')->default(false);
            $table->json('tags')->nullable();                   // product_issue|service_issue|compliment
            $table->timestamp('reviewed_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['organization_id', 'platform', 'sentiment']);
            $table->index(['organization_id', 'rating', 'reviewed_at']);
            $table->unique(['organization_id', 'platform', 'platform_review_id'], 'unique_org_platform_review');
        });

        // ── Social Engagements ───────────────────────────────────────────────
        // Atomic engagement events (likes, shares, clicks, follows, etc.)
        Schema::create('social_engagements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_page_id')->constrained('social_pages')->cascadeOnDelete();
            $table->foreignId('social_post_id')->nullable()->constrained('social_posts')->nullOnDelete();

            $table->string('platform');
            $table->string('engagement_type');                  // like|comment|share|click|follow|save|reaction|view
            $table->string('actor_platform_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->integer('count')->default(1);               // for aggregated metrics
            $table->date('engagement_date');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'platform', 'engagement_date']);
            $table->index(['social_post_id', 'engagement_type', 'engagement_date']);
        });

        // ── Social Sentiment Scores ──────────────────────────────────────────
        // Time-series sentiment tracking per channel / topic
        Schema::create('social_sentiment_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_account_id')->nullable()->constrained('social_accounts')->nullOnDelete();
            $table->foreignId('social_conversation_id')->nullable()->constrained('social_conversations')->nullOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('subject_type');                     // account|conversation|review|mention|brand
            $table->string('platform')->nullable();
            $table->string('sentiment');                        // positive|neutral|concerned|frustrated|angry
            $table->decimal('score', 5, 2);                     // -1.0 to 1.0 normalized to 0-100
            $table->decimal('confidence', 5, 2)->nullable();    // model confidence
            $table->text('summary')->nullable();                // AI-generated summary of why
            $table->json('detected_topics')->nullable();        // product|service|delivery|pricing
            $table->json('detected_emotions')->nullable();      // joy|trust|fear|surprise|sadness|disgust|anger|anticipation
            $table->boolean('requires_escalation')->default(false);
            $table->boolean('escalation_handled')->default(false);
            $table->timestamp('scored_at');
            $table->timestamps();

            $table->index(['organization_id', 'sentiment', 'scored_at']);
            $table->index(['organization_id', 'requires_escalation']);
        });

        // ── Social Conversions ───────────────────────────────────────────────
        // Conversion events traced back to social interactions
        Schema::create('social_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('social_lead_id')->nullable()->constrained('social_leads')->nullOnDelete();
            $table->foreignId('social_conversation_id')->nullable()->constrained('social_conversations')->nullOnDelete();
            $table->foreignId('agent_deployment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('social_campaigns')->nullOnDelete();

            $table->string('conversion_type');                  // purchase|signup|demo_booked|quote_requested|upsell|renewal
            $table->string('platform')->nullable();
            $table->decimal('revenue', 12, 2)->nullable();
            $table->string('currency', 3)->default('USD');
            $table->string('product_or_service')->nullable();
            $table->decimal('agent_attribution_score', 5, 2)->default(0); // % credit to agent
            $table->json('attribution_path')->nullable();       // touchpoints leading to conversion
            $table->json('metadata')->nullable();
            $table->timestamp('converted_at');
            $table->timestamps();

            $table->index(['organization_id', 'conversion_type', 'converted_at']);
            $table->index(['organization_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::table('social_posts', function (Blueprint $table) {
            $table->dropForeign(['campaign_id']);
        });

        Schema::dropIfExists('social_conversions');
        Schema::dropIfExists('social_sentiment_scores');
        Schema::dropIfExists('social_engagements');
        Schema::dropIfExists('social_reviews');
        Schema::dropIfExists('social_leads');
        Schema::dropIfExists('social_messages');
        Schema::dropIfExists('social_conversations');
        Schema::dropIfExists('social_campaigns');
        Schema::dropIfExists('social_posts');
        Schema::dropIfExists('social_pages');
        Schema::dropIfExists('social_accounts');
    }
};
