<?php

namespace App\Services\AI;

use App\Models\AgentPlugin;
use App\Models\AgentPluginInstallation;
use App\Models\Organization;
use Illuminate\Support\Facades\Cache;

/**
 * Agent Plugin Service
 *
 * Manages the lifecycle of installable agent plugins:
 * - Install / uninstall plugins per organisation
 * - Resolve a plugin's implementation class at runtime
 * - List available plugins for an organisation
 */
class AgentPluginService
{
    private const CACHE_TTL = 300; // 5 minutes

    // ──────────────────────────────────────────────
    // Installation
    // ──────────────────────────────────────────────

    /**
     * Install a plugin for an organisation.
     * Binds the plugin key into the service container so it can be resolved
     * anywhere via app('plugin-key').
     */
    public function install(string $pluginKey, Organization $organization, int $installedBy, array $config = []): AgentPluginInstallation
    {
        $plugin = $this->findPlugin($pluginKey, $organization->id);

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
