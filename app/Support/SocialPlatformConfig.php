<?php

namespace App\Support;

/**
 * Social platform OAuth configuration — driver names and required scopes.
 *
 * Extracted from SocialOAuthController to keep controllers lean and allow
 * reuse in tests, jobs, and other services.
 */
class SocialPlatformConfig
{
    /** Map of platform slug → Socialite driver name. */
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

    /** OAuth scopes required per platform to manage pages and messages. */
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

    public static function isSupported(string $platform): bool
    {
        return array_key_exists($platform, self::SUPPORTED_PLATFORMS);
    }

    public static function driverFor(string $platform): string
    {
        return self::SUPPORTED_PLATFORMS[$platform];
    }

    /** @return string[] */
    public static function scopesFor(string $platform): array
    {
        return self::PLATFORM_SCOPES[$platform] ?? [];
    }

    /** @return array<string, string> */
    public static function allPlatforms(): array
    {
        return self::SUPPORTED_PLATFORMS;
    }
}
