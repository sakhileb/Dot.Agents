<?php

namespace App\Services\Social;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Log;

/**
 * Social Publishing Service — stub for platform API integration.
 *
 * In production, each platform has its own publisher implementation
 * (FacebookPublisher, InstagramPublisher, etc.). This service
 * dispatches to the correct one based on the platform identifier.
 */
class SocialPublishingService
{
    /**
     * Publish a post to the appropriate platform API.
     * Returns the platform-assigned post ID.
     */
    public function publish(SocialPost $post): string
    {
        $platform = $post->socialPage->socialAccount->platform;

        Log::info('SocialPublishingService: publishing post', [
            'post_id' => $post->id,
            'platform' => $platform,
        ]);

        // In production, delegate to platform-specific publisher:
        // return match($platform) {
        //     'facebook'  => app(FacebookPublisher::class)->publish($post),
        //     'instagram' => app(InstagramPublisher::class)->publish($post),
        //     'linkedin'  => app(LinkedInPublisher::class)->publish($post),
        //     'x'         => app(XPublisher::class)->publish($post),
        //     default     => throw new \InvalidArgumentException("Unsupported platform: {$platform}"),
        // };

        // Stub: return a fake platform post ID
        return 'platform_post_'.$post->uuid;
    }
}
