<?php

namespace App\Services\AI;

use App\Models\AgentPlugin;
use App\Models\AgentPluginInstallation;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Agent Plugin Service
 *
 * Manages the lifecycle of installable agent plugins:
 * - Install / uninstall plugins per organisation
 * - Resolve a plugin's implementation class at runtime
 * - List available plugins for an organisation
 *
 * Security: All plugin installations are gated by integrity verification.
 * Plugins must have a non-null `checksum` (SHA-256 of the implementation class
 * file) or be flagged as platform-built-in (`is_platform_plugin = true`).
 * Third-party plugins without a verified checksum are BLOCKED.
 */
class AgentPluginService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Plugin keys that are always allowed regardless of checksum
     * (built into the platform, not marketplace-sourced).
     */
    private const PLATFORM_BUILTIN_KEYS = [
        'audit-logger',
        'memory-manager',
        'risk-assessor',
        'workflow-executor',
        'notification-sender',
    ];

    // ──────────────────────────────────────────────
    // Installation
    // ──────────────────────────────────────────────

    /**
     * Install a plugin for an organisation.
     * Binds the plugin key into the service container so it can be resolved
     * anywhere via app('plugin-key').
     *
     * @throws \RuntimeException if plugin integrity check fails
     */
    public function install(string $pluginKey, Organization $organization, int $installedBy, array $config = []): AgentPluginInstallation
    {
        $plugin = $this->findPlugin($pluginKey, $organization->id);

        // ── Security: Integrity verification ─────────────────────────────────
        $this->verifyPluginIntegrity($plugin);

        $installation = AgentPluginInstallation::firstOrCreate(
            [
                'plugin_id' => $plugin->id,
                'organization_id' => $organization->id,
            ],
            [
                'installed_by' => $installedBy,
                'config' => $config,
                'installed_at' => now(),
            ]
        );

        Log::info('[AgentPlugin] Plugin installed', [
            'plugin_key' => $pluginKey,
            'organization_id' => $organization->id,
            'installed_by' => $installedBy,
            'is_platform_plugin' => $plugin->is_platform_plugin ?? false,
        ]);

        $this->bindToContainer($plugin);
        $this->bustCache($organization->id);

        return $installation;
    }

    /**
     * Uninstall a plugin from an organisation.
     */
    public function uninstall(string $pluginKey, Organization $organization): void
    {
        $plugin = $this->findPlugin($pluginKey, $organization->id);

        AgentPluginInstallation::where('plugin_id', $plugin->id)
            ->where('organization_id', $organization->id)
            ->delete();

        $this->bustCache($organization->id);
    }

    // ──────────────────────────────────────────────
    // Resolution
    // ──────────────────────────────────────────────

    /**
     * Resolve the implementation instance for a plugin key.
     * Falls back to the platform Agent model if no plugin is registered.
     */
    public function resolve(string $agentKey, int $organizationId): mixed
    {
        // Check container first (bound via install())
        if (app()->bound($agentKey)) {
            return app($agentKey);
        }

        $plugin = AgentPlugin::active()
            ->forOrganization($organizationId)
            ->where('key', $agentKey)
            ->first();

        if (! $plugin) {
            throw new \RuntimeException("Agent plugin [{$agentKey}] not found or not installed for organisation #{$organizationId}.");
        }

        return $plugin->resolve();
    }

    /**
     * Check whether a plugin is installed for an organisation.
     */
    public function isInstalled(string $pluginKey, int $organizationId): bool
    {
        return Cache::remember(
            "plugin_installed:{$pluginKey}:{$organizationId}",
            self::CACHE_TTL,
            function () use ($pluginKey, $organizationId) {
                $plugin = AgentPlugin::where('key', $pluginKey)->first();
                if (! $plugin) {
                    return false;
                }

                return AgentPluginInstallation::where('plugin_id', $plugin->id)
                    ->where('organization_id', $organizationId)
                    ->exists();
            }
        );
    }

    // ──────────────────────────────────────────────
    // Listing
    // ──────────────────────────────────────────────

    /**
     * Return all plugins available to an organisation (platform-wide + org-specific).
     */
    public function availableFor(int $organizationId)
    {
        return AgentPlugin::active()
            ->forOrganization($organizationId)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();
    }

    /**
     * Return the plugin keys that are currently installed for an organisation.
     */
    public function installedKeysFor(int $organizationId): array
    {
        return Cache::remember(
            "installed_plugin_keys:{$organizationId}",
            self::CACHE_TTL,
            function () use ($organizationId) {
                return AgentPluginInstallation::where('organization_id', $organizationId)
                    ->with('plugin:id,key')
                    ->get()
                    ->pluck('plugin.key')
                    ->filter()
                    ->values()
                    ->toArray();
            }
        );
    }

    // ──────────────────────────────────────────────
    // Internals
    // ──────────────────────────────────────────────

    private function findPlugin(string $pluginKey, int $organizationId): AgentPlugin
    {
        $plugin = AgentPlugin::active()
            ->forOrganization($organizationId)
            ->where('key', $pluginKey)
            ->firstOrFail();

        return $plugin;
    }

    /**
     * Verify a plugin's integrity before installation.
     *
     * Rules:
     * 1. Platform built-in plugins (is_platform_plugin = true) are always trusted.
     * 2. Plugins on the PLATFORM_BUILTIN_KEYS allowlist are always trusted.
     * 3. All other plugins MUST have a non-null `checksum` field.
     *    In a full production deployment this checksum would be verified against
     *    a trusted registry / signature authority.
     *
     * @throws \RuntimeException if integrity check fails
     */
    private function verifyPluginIntegrity(AgentPlugin $plugin): void
    {
        // Platform built-in — always trusted
        if (($plugin->is_platform_plugin ?? false) === true) {
            return;
        }

        // Allowlisted keys — always trusted
        if (in_array($plugin->key, self::PLATFORM_BUILTIN_KEYS, true)) {
            return;
        }

        // Third-party plugins MUST have a checksum on record
        if (empty($plugin->checksum)) {
            Log::warning('[AgentPlugin] Blocked installation of unverified plugin', [
                'plugin_key' => $plugin->key,
                'plugin_id' => $plugin->id,
            ]);

            throw new \RuntimeException(
                "Plugin [{$plugin->key}] cannot be installed: no integrity checksum is on record. "
                .'Contact your platform administrator to have the plugin verified.'
            );
        }

        // Verify the implementation class file hash matches the stored checksum
        $classFile = $this->resolveClassFile($plugin);
        if ($classFile && file_exists($classFile)) {
            $actualHash = hash_file('sha256', $classFile);
            if (! hash_equals($plugin->checksum, $actualHash)) {
                Log::critical('[AgentPlugin] Integrity check FAILED — plugin checksum mismatch', [
                    'plugin_key' => $plugin->key,
                    'plugin_id' => $plugin->id,
                    'expected' => substr($plugin->checksum, 0, 16).'...',
                    'actual' => substr($actualHash, 0, 16).'...',
                ]);

                throw new \RuntimeException(
                    "Plugin [{$plugin->key}] integrity check failed: implementation file has been tampered with. "
                    .'Installation has been blocked.'
                );
            }
        }
    }

    /**
     * Resolve the filesystem path of a plugin's implementation class file.
     */
    private function resolveClassFile(AgentPlugin $plugin): ?string
    {
        if (empty($plugin->class)) {
            return null;
        }

        try {
            $reflector = new \ReflectionClass($plugin->class);

            return $reflector->getFileName() ?: null;
        } catch (\ReflectionException) {
            return null;
        }
    }

    /**
     * Bind a plugin's implementation into Laravel's container
     * so it can be resolved with app('plugin-key').
     */
    private function bindToContainer(AgentPlugin $plugin): void
    {
        app()->bindIf($plugin->key, fn () => app($plugin->class));
    }

    private function bustCache(int $organizationId): void
    {
        Cache::forget("installed_plugin_keys:{$organizationId}");
    }
}
