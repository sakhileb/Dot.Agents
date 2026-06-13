<?php

namespace Tests\Feature\Actions\Compliance;

use App\Actions\Compliance\RecordConsentAction;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecordConsentActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $org = Organization::factory()->create();
        session(['current_organization_id' => $org->id]);
    }

    #[Test]
    public function test_records_granted_consent(): void
    {
        $user = User::factory()->create(['consent_records' => null]);

        app(RecordConsentAction::class)->execute(
            user: $user,
            consentPurpose: 'marketing_emails',
            granted: true,
            version: '1.0',
            ipAddress: '127.0.0.1',
        );

        $user->refresh();
        $record = $user->consent_records['marketing_emails'];
        $this->assertTrue($record['granted']);
        $this->assertEquals('1.0', $record['version']);
        $this->assertEquals('127.0.0.1', $record['ip_address']);
    }

    #[Test]
    public function test_records_withdrawn_consent(): void
    {
        $user = User::factory()->create(['consent_records' => [
            'marketing_emails' => ['granted' => true, 'version' => '1.0', 'recorded_at' => now()->toISOString(), 'ip_address' => null, 'user_agent' => null],
        ]]);

        app(RecordConsentAction::class)->execute(
            user: $user,
            consentPurpose: 'marketing_emails',
            granted: false,
            version: '1.0',
        );

        $user->refresh();
        $this->assertFalse($user->consent_records['marketing_emails']['granted']);
    }

    #[Test]
    public function test_has_consent_returns_true_when_granted(): void
    {
        $user = User::factory()->create(['consent_records' => [
            'marketing_emails' => ['granted' => true, 'version' => '1.0', 'recorded_at' => now()->toISOString(), 'ip_address' => null, 'user_agent' => null],
        ]]);

        $action = app(RecordConsentAction::class);
        $this->assertTrue($action->hasConsent($user, 'marketing_emails'));
        $this->assertFalse($action->hasConsent($user, 'analytics'));
    }
}
