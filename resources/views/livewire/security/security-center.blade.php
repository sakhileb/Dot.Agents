<div class="space-y-6">
    {{-- DIS Control Panel --}}
    <div class="bg-gradient-to-br from-gray-900 to-purple-950 dark:from-gray-950 dark:to-purple-950 rounded-2xl border border-purple-800/30 p-6 text-white">
        <div class="flex items-center justify-between mb-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-700/50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="font-bold text-lg">Digital Immune System</h2>
                    <p class="text-purple-300 text-sm">Real-time threat detection & autonomous remediation</p>
                </div>
            </div>
            <button wire:click="runDISCheck" wire:loading.attr="disabled"
                class="px-5 py-2.5 bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-semibold text-sm rounded-xl transition-colors disabled:opacity-60">
                <span wire:loading.remove wire:target="runDISCheck">▶ Run Health Check</span>
                <span wire:loading wire:target="runDISCheck">Scanning...</span>
            </button>
        </div>

        {{-- Stats --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach([
                ['Events (24h)', $this->stats['total_24h'], 'purple'],
                ['Critical Open', $this->stats['critical'], 'red'],
                ['Auto-Remediated', $this->stats['auto_remediated'], 'emerald'],
                ['Quarantined', $this->stats['quarantined'], 'orange'],
            ] as [$label, $value, $color])
                <div class="bg-white/5 rounded-xl p-4 text-center">
                    <div class="text-3xl font-bold text-white">{{ $value }}</div>
                    <div class="text-xs text-purple-300 mt-1">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- DIS Report (shown after running) --}}
    @if($disReport)
        <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 p-6">
            <h3 class="font-semibold text-gray-900 dark:text-white text-sm mb-4">
                DIS Health Check Report
                <span class="ml-2 text-xs text-gray-400">{{ count($disReport['alerts'] ?? []) }} alerts &middot; {{ count($disReport['actions_taken'] ?? []) }} actions taken</span>
            </h3>

            @if(!empty($disReport['alerts']))
                <div class="space-y-2 mb-4">
                    @foreach($disReport['alerts'] as $alert)
                        <div class="flex items-start gap-3 p-3 rounded-xl
                            {{ str_contains($alert, 'CRITICAL') || str_contains($alert, 'critical') ? 'bg-red-50 dark:bg-red-950/20 border border-red-200 dark:border-red-800' : 'bg-yellow-50 dark:bg-yellow-950/20 border border-yellow-200 dark:border-yellow-800' }}">
                            <span class="text-sm">{{ str_contains($alert, 'CRITICAL') ? '🔴' : '⚠️' }}</span>
                            <p class="text-xs text-gray-700 dark:text-gray-300">{{ $alert }}</p>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center gap-2 p-3 bg-emerald-50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800 rounded-xl mb-4">
                    <span class="text-emerald-600 dark:text-emerald-400">✓</span>
                    <p class="text-sm text-emerald-700 dark:text-emerald-400">All agents healthy. No threats detected.</p>
                </div>
            @endif

            @if(!empty($disReport['actions_taken']))
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Automated Actions:</p>
                    @foreach($disReport['actions_taken'] as $action)
                        <div class="text-xs text-purple-700 dark:text-purple-400 bg-purple-50 dark:bg-purple-950/20 rounded-lg px-3 py-2 mb-1">⚡ {{ $action }}</div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    {{-- Security Events --}}
    <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white text-sm">Security Events</h3>
            <div class="flex gap-2">
                <select wire:model.live="filterSeverity"
                    class="text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Severities</option>
                    <option value="critical">Critical</option>
                    <option value="error">Error</option>
                    <option value="warning">Warning</option>
                    <option value="info">Info</option>
                </select>
                <select wire:model.live="filterType"
                    class="text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    <option value="">All Types</option>
                    <option value="prompt_injection">Prompt Injection</option>
                    <option value="agent_drift">Agent Drift</option>
                    <option value="delusion_detected">Delusion Detected</option>
                    <option value="permission_abuse">Permission Abuse</option>
                    <option value="autonomy_violation">Autonomy Violation</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-800/50">
                        <th class="text-left px-5 py-3 text-gray-500 font-medium">Timestamp</th>
                        <th class="text-left px-5 py-3 text-gray-500 font-medium">Event Type</th>
                        <th class="text-left px-5 py-3 text-gray-500 font-medium">Severity</th>
                        <th class="text-left px-5 py-3 text-gray-500 font-medium">Status</th>
                        <th class="text-left px-5 py-3 text-gray-500 font-medium">Auto-Remediated</th>
                        <th class="text-left px-5 py-3 text-gray-500 font-medium">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($this->events as $event)
                        @php
                            $sevColors = ['critical' => 'red', 'error' => 'orange', 'warning' => 'yellow', 'info' => 'blue'];
                            $sevColor = $sevColors[$event->severity] ?? 'gray';
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <td class="px-5 py-3 font-mono text-gray-400">{{ $event->created_at->format('M j H:i:s') }}</td>
                            <td class="px-5 py-3">
                                <span class="font-medium text-gray-900 dark:text-white">{{ ucwords(str_replace('_', ' ', $event->event_type)) }}</span>
                                @if($event->description)
                                    <p class="text-gray-400 mt-0.5 line-clamp-1">{{ $event->description }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium bg-{{ $sevColor }}-100 text-{{ $sevColor }}-700 dark:bg-{{ $sevColor }}-900/30 dark:text-{{ $sevColor }}-400">
                                    {{ ucfirst($event->severity) }}
                                </span>
                            </td>
                            <td class="px-5 py-3">
                                <span class="{{ $event->status === 'resolved' ? 'text-emerald-600 dark:text-emerald-400' : 'text-orange-600 dark:text-orange-400' }} font-medium">
                                    {{ ucfirst($event->status) }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center">
                                @if($event->auto_remediated)
                                    <span class="text-emerald-600 dark:text-emerald-400">✓</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($event->status === 'open')
                                    <button wire:click="resolveEvent({{ $event->id }})"
                                        class="text-xs text-purple-600 hover:text-purple-700 dark:text-purple-400 font-medium">
                                        Resolve
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-gray-400">No security events found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $this->events->links() }}
        </div>
    </div>
</div>
