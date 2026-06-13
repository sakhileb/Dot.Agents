<?php

namespace App\Http\Controllers;

use App\Actions\Organizations\SaveConnectionSettingsAction;
use App\Actions\Social\ConnectSocialAccountAction;
use App\DTOs\Organizations\SaveConnectionSettingsData;
use App\DTOs\Social\ConnectSocialAccountData;
use App\Http\Requests\ConnectSocialAccountRequest;
use App\Models\OrganizationSocialCredential;
use App\Models\SocialAccount;
use App\Support\SocialPlatformConfig;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Laravel\Socialite\Contracts\Provider;

class SocialOAuthController extends Controller
{
    public function __construct(private readonly Socialite $socialite) {}

    public function redirect(ConnectSocialAccountRequest $request, string $platform): RedirectResponse
    {
        abort_unless(SocialPlatformConfig::isSupported($platform), 404, 'Unsupported platform.');

        session()->put("social_oauth_{$platform}_deployment_id", $request->validated('agent_deployment_id'));

        $driver = $this->resolveDriver($platform);
        $scopes = SocialPlatformConfig::scopesFor($platform);

        return empty($scopes) ? $driver->redirect() : $driver->scopes($scopes)->redirect();
    }

    /**
     * Handle the OAuth callback and create/update the SocialAccount.
     */
    public function callback(Request $request, string $platform, ConnectSocialAccountAction $action): RedirectResponse
    {
        abort_unless(SocialPlatformConfig::isSupported($platform), 404, 'Unsupported platform.');

        if ($request->has('error')) {
            return redirect()->route('social.accounts')
                ->with('error', 'Connection cancelled or denied by the platform.');
        }

        Gate::authorize('create', [SocialAccount::class, session('current_organization_id')]);

        $oauthUser = $this->resolveDriver($platform)->user();
        $agentDeploymentId = session()->pull("social_oauth_{$platform}_deployment_id");

        $account = $action->execute(ConnectSocialAccountData::fromArray([
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
            'scopes' => SocialPlatformConfig::scopesFor($platform),
            'agent_deployment_id' => $agentDeploymentId ? (int) $agentDeploymentId : null,
        ]));

        if ($wizardSettings = session()->pull("social_wizard_{$platform}")) {
            app(SaveConnectionSettingsAction::class)->execute($account, SaveConnectionSettingsData::fromArray($wizardSettings));
        }

        return redirect()->route('social.accounts')
            ->with('success', "Connected {$account->account_name} on ".ucfirst($platform).'.');
    }

    private function resolveDriver(string $platform): Provider
    {
        $driver = SocialPlatformConfig::driverFor($platform);
        $orgId = (int) session('current_organization_id');

        $orgCred = OrganizationSocialCredential::where('organization_id', $orgId)
            ->where('platform', $platform)
            ->first();

        if ($orgCred) {
            $callbackUrl = $orgCred->redirect_uri ?? route('social.auth.callback', ['platform' => $platform]);

            config([
                "services.{$driver}.client_id" => $orgCred->client_id,
                "services.{$driver}.client_secret" => $orgCred->client_secret,
                "services.{$driver}.redirect" => $callbackUrl,
            ]);
        }

        return $this->socialite->driver($driver);
    }
}
