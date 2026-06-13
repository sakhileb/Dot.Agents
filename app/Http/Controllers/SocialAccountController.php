<?php

namespace App\Http\Controllers;

use App\Actions\Social\DisconnectSocialAccountAction;
use App\Models\SocialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    /**
     * Disconnect (soft-delete) a social account.
     *
     * OAuth connect/callback logic lives in SocialOAuthController.
     */
    public function destroy(Request $request, SocialAccount $socialAccount): RedirectResponse
    {
        app(DisconnectSocialAccountAction::class)->executeSingle($socialAccount);

        return redirect()->route('social.accounts')
            ->with('success', 'Social account disconnected.');
    }
}
