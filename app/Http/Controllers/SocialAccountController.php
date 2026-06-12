<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\SaveConnectionSettingsAction;
use App\Actions\Social\ConnectSocialAccountAction;
use App\Actions\Social\DisconnectSocialAccountAction;
use App\DTOs\Organizations\SaveConnectionSettingsData;
use App\DTOs\Social\ConnectSocialAccountData;
use App\Http\Requests\ConnectSocialAccountRequest;
use App\Models\OrganizationSocialCredential;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Laravel\Socialite\Contracts\Provider;

class SocialAccountController extends Controller
{
    /** Platforms supported and their Socialite driver names. */
    private const SUPPORTED_PLATFORMS = [
        'facebook' => 'facebook',
        'instagram' => 'instagram',
        'linkedin' => 'linkedin-openid',
        'twitter' => 'twitter',
        'tiktok' => 'tiktok',
        'youtube' => 'youtube',
        'pinterest' => 'pinterest',
        'patreon' => 'patreon',
        'snapchat' => 'snapchat',
        'reddit' => 'reddit',
        'discord' => 'discord',
        'twitch' => 'twitch',
    ];

    /** Scopes needed per platform to manage pages and messages. */
    private const PLATFORM_SCOPES = [
        'facebook' => ['pages_manage_posts', 'pages_read_engagement', 'pages_messaging', 'leads_retrieval', 'pages_manage_metadata'],
        'instagram' => ['instagram_basic', 'instagram_manage_comments', 'instagram_manage_insights', 'pages_show_list'],
        'linkedin' => ['r_liteprofile', 'r_emailaddress', 'w_member_social', 'rw_organization_admin'],
        'twitter' => ['tweet.read', 'tweet.write', 'users.read', 'dm.read', 'dm.write'],
        'tiktok' => ['user.info.basic', 'video.list', 'video.publish', 'comment.list', 'comment.create'],
        'youtube' => ['https://www.googleapis.com/auth/youtube', 'https://www.googleapis.com/auth/youtube.readonly', 'https://www.googleapis.com/auth/yt-analytics.readonly'],
        'pinterest' => ['read_users', 'write_users', 'read_boards', 'write_boards', 'read_pins', 'write_pins', 'read_secret'],
        'patreon' => ['identity', 'identity[email]', 'campaigns', 'campaigns.members'],
        'snapchat' => ['snapchat-marketing-api'],
        'reddit' => ['identity', 'read', 'submit', 'subscribe', 'privatemessages'],
        'discord' => ['identify', 'email', 'guilds', 'guilds.members.read', 'messages.read'],
        'twitch' => ['user:read:email', 'channel:read:subscriptions', 'channel:manage:broadcast', 'moderation:read', 'chat:read', 'chat:edit'],
    ];

    public function __construct(private readonly Socialite $socialite) {}

    /**
     * Redirect the user to the platform's OAuth consent screen.
     */
    public function redirect(ConnectSocialAccountRequest $request, string $platform): RedirectResponse
    {
        $this->abortIfUnsupported($platform);

        session()->put("social_oauth_{$platform}_deployment_id", $request->validated('agent_deployment_id'));

        $driver = $this->resolveDriver($platform);
        $scopes = self::PLATFORM_SCOPES[$platform] ?? [];

        return empty($scopes) ? $driver->redirect() : $driver->scopes($scopes)->redirect();
    }

    /**
     * Handle the OAuth callback and create/update the SocialAccount.
     */
    public function callback(Request $request, string $platform, ConnectSocialAccountAction $action): RedirectResponse
    {
        $this->abortIfUnsupported($platform);

        if ($request->has('error')) {
            return redirect()->route('social.accounts')
                ->with('error', 'Connection cancelled or denied by the platform.');
        }

        Gate::authorize('create', [SocialAccount::class, session('current_organization_id')]);

        $oauthUser = $this->resolveDriver($platform)->user();

        $agentDeploymentId = session()->pull("social_oauth_{$platform}_deployment_id");

        $data = ConnectSocialAccountData::fromArray([
            'organization_id' => (int) session('current_organization_id'),
            'connected_by' => auth()->id(),
            'platform' => $platform,
            'platform_account_id' => (string) $oauthUser->getId(),
            'account_name' => $oauthUser->getName() ?? $oauthUser->getNickname() ?? $oauthUser->getEmail(),
            'account_handle' => $oauthUser->getNickname(),
            'account_type' => 'page',
            'avatar_url' => $oauthUser->getAvatar(),
            'access_token' => $oauthUser->token,
            'refresh_token' => $oauthUser->refreshToken ?? null,
            'token_expires_at' => $oauthUser->expiresIn ? now()->addSeconds((int) $oauthUser->expiresIn)->toIso8601String() : null,
            'scopes' => self::PLATFORM_SCOPES[$platform] ?? [],
            'agent_deployment_id' => $agentDeploymentId ? (int) $agentDeploymentId : null,
        ]);

        $account = $action->execute($data);

        // If the user came through the connection wizard, save their settings.
        $wizardSettings = session()->pull("social_wizard_{$platform}");
        if ($wizardSettings) {
            app(SaveConnectionSettingsAction::class)->execute($account, SaveConnectionSettingsData::fromArray($wizardSettings));
        }

        return redirect()->route('social.accounts')
            ->with('success', "Connected {$account->account_name} on ".ucfirst($platform).'.');
    }

    /**
     * Disconnect (soft-delete) a social account.
     */
    public function destroy(Request $request, SocialAccount $socialAccount): RedirectResponse
    {
        app(DisconnectSocialAccountAction::class)->executeSingle($socialAccount);

        return redirect()->route('social.accounts')
            ->with('success', 'Social account disconnected.');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Return a Socialite driver, overriding credentials with per-org config if present.
     */
    private function resolveDriver(string $platform): Provider
    {
        $driver = self::SUPPORTED_PLATFORMS[$platform];

        $orgId = (int) session('current_organization_id');

        $orgCred = OrganizationSocialCredential::where('organization_id', $orgId)
            ->where('platform', $platform)
            ->first();

        if ($orgCred) {
            $callbackUrl = $orgCred->redirect_uri ?? route('social.auth.callback', ['platform' => $platform]);

            // Override the Socialite config for this request only.
            config([
                "services.{$driver}.client_id" => $orgCred->client_id,
                "services.{$driver}.client_secret" => $orgCred->client_secret,
                "services.{$driver}.redirect" => $callbackUrl,
            ]);
        }

        return $this->socialite->driver($driver);
    }

    private function abortIfUnsupported(string $platform): void
    {
        abort_unless(array_key_exists($platform, self::SUPPORTED_PLATFORMS), 404, 'Unsupported platform.');
    }
}
