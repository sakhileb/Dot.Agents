<div class="flex h-full">
    {{-- Filter Sidebar --}}
    <div class="w-64 flex-shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 overflow-y-auto p-4 space-y-6">
        <div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Search</h3>
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search agents..."
                       class="w-full pl-9 pr-3 py-2 text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500">
            </div>
        </div>

        <div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Department</h3>
            <div class="space-y-1">
                <button wire:click="$set('selectedDepartment', '')"
                        class="w-full text-left px-3 py-2 text-sm rounded-lg {{ !$selectedDepartment ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    All Departments
                </button>
                @foreach($this->departments as $dept)
                <button wire:click="$set('selectedDepartment', '{{ $dept->slug }}')"
                        class="w-full text-left px-3 py-2 text-sm rounded-lg {{ $selectedDepartment === $dept->slug ? 'bg-purple-50 dark:bg-purple-900/30 text-purple-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}">
                    {{ $dept->name }}
                </button>
                @endforeach
            </div>
        </div>

        <div>
            <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Sort By</h3>
            <select wire:model.live="sortBy" class="w-full text-sm bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500">
                <option value="featured">Featured</option>
                <option value="rating">Top Rated</option>
                <option value="popular">Most Deployed</option>
                <option value="price_asc">Price: Low to High</option>
                <option value="price_desc">Price: High to Low</option>
            </select>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="flex-1 overflow-y-auto p-6">
        <div class="mb-6 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white">Agent Marketplace</h2>
            <p class="text-sm text-gray-500">{{ $this->agents->total() }} agents available</p>
        </div>

        {{-- Agent Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            @foreach($this->agents as $agent)
            <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-800 overflow-hidden hover:shadow-md hover:border-purple-200 dark:hover:border-purple-800 transition group">
                {{-- Card Header --}}
                <div class="bg-gradient-to-r from-purple-600 to-purple-800 p-4 relative">
                    @if($agent->is_featured)
                    <span class="absolute top-3 right-3 px-2 py-0.5 bg-yellow-400 text-yellow-900 text-xs font-bold rounded-full">Featured</span>
                    @endif
                    @if($agent->is_beta)
                    <span class="absolute top-3 right-3 px-2 py-0.5 bg-blue-400 text-blue-900 text-xs font-bold rounded-full">Beta</span>
                    @endif
                    <div class="w-12 h-12 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold text-lg">
                        {{ strtoupper(substr($agent->name, 0, 2)) }}
                    </div>
                </div>

                <div class="p-5">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">{{ $agent->name }}</h3>
                            <p class="text-xs text-purple-600 dark:text-purple-400">{{ $agent->agentDepartment?->name }}</p>
                        </div>
                        <div class="text-right flex-shrink-0">
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ $agent->formatted_price }}</p>
                        </div>
                    </div>

                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ $agent->tagline ?? $agent->description }}</p>

                    {{-- Scores --}}
                    <div class="flex items-center gap-3 mb-4">
                        <div class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <span class="text-xs font-medium text-gray-700 dark:text-gray-300">{{ number_format($agent->avg_rating, 1) }}</span>
                            <span class="text-xs text-gray-400">({{ $agent->review_count }})</span>
                        </div>
                        <span class="text-gray-300 dark:text-gray-700">|</span>
                        <span class="text-xs text-gray-500">{{ number_format($agent->total_deployments) }} deployments</span>
                        @if($agent->is_verified)
                        <span class="text-xs font-medium text-green-600">&#10003; Verified</span>
                        @endif
                    </div>

                    {{-- Capabilities Tags --}}
                    @if($agent->capabilities)
                    <div class="flex flex-wrap gap-1.5 mb-4">
                        @foreach(array_slice((array) $agent->capabilities, 0, 3) as $cap)
                        <span class="px-2 py-0.5 bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-xs rounded-full">{{ $cap }}</span>
                        @endforeach
                    </div>
                    @endif

                    {{-- Actions --}}
                    <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-gray-800">
                        <button wire:click="previewAgent({{ $agent->id }})"
                                class="flex-1 px-3 py-2 text-sm font-medium text-purple-600 dark:text-purple-400 border border-purple-200 dark:border-purple-800 rounded-lg hover:bg-purple-50 dark:hover:bg-purple-900/20 transition">
                            Preview
                        </button>
                        <button wire:click="startDeploy({{ $agent->id }})"
                                class="flex-1 px-3 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition">
                            Deploy
                        </button>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-6">
            {{ $this->agents->links() }}
        </div>
    </div>

    {{-- Preview Modal --}}
    @if($previewAgent)
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4" wire:click.self="closePreview">
        <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-2xl">
            <div class="bg-gradient-to-r from-purple-600 to-purple-900 p-6 rounded-t-2xl">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-xl bg-white/20 flex items-center justify-center text-white font-bold text-xl">
                            {{ strtoupper(substr($previewAgent->name, 0, 2)) }}
                        </div>
                        <div class="text-white">
                            <h2 class="text-xl font-bold">{{ $previewAgent->name }}</h2>
                            <p class="text-purple-200 text-sm">{{ $previewAgent->agentDepartment?->name }}</p>
                        </div>
                    </div>
                    <button wire:click="closePreview" class="text-white/60 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <div class="p-6 space-y-5">
                <p class="text-gray-700 dark:text-gray-300">{{ $previewAgent->description }}</p>

                @if($previewAgent->capabilities)
                <div>
                    <h4 class="font-semibold text-gray-900 dark:text-white mb-2">Capabilities</h4>
                    <ul class="grid grid-cols-2 gap-1.5">
                        @foreach($previewAgent->capabilities as $cap)
                        <li class="flex items-center gap-2 text-sm text-gray-600 dark:text-gray-400">
                            <span class="w-1.5 h-1.5 bg-purple-500 rounded-full"></span>{{ $cap }}
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-800">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $previewAgent->formatted_price }}</div>
                    <button wire:click="startDeploy({{ $previewAgent->id }})"
                            class="px-6 py-2.5 bg-purple-600 hover:bg-purple-700 text-white font-medium rounded-xl transition">
                        Deploy Agent
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Deploy Modal --}}
    @if($showDeployModal)
    <div class="fixed inset-0 bg-black/60 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-900 rounded-2xl w-full max-w-lg shadow-2xl">
            <div class="p-6 border-b border-gray-200 dark:border-gray-800">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Deploy Agent</h3>
                <p class="text-sm text-gray-500 mt-1">Configure this agent for your organization</p>
            </div>
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Agent Name</label>
                    <input wire:model="deploymentName" type="text"
                           class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white dark:bg-gray-800">
                    @error("deploymentName") <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Deployment Mode</label>
                    <select wire:model="deploymentMode" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-800">
                        <option value="advisory">Advisory (Recommends only, no actions)</option>
                        <option value="semi-autonomous">Semi-Autonomous (Actions need approval)</option>
                        <option value="autonomous">Autonomous (Acts independently)</option>
                        <option value="executive_approval">Executive Approval Required</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">Custom Instructions <span class="text-gray-400">(Optional)</span></label>
                    <textarea wire:model="customInstructions" rows="3" placeholder="Add specific instructions for this deployment..."
                              class="w-full px-3 py-2 border border-gray-300 dark:border-gray-700 rounded-lg text-sm focus:ring-2 focus:ring-purple-500 bg-white dark:bg-gray-800 resize-none"></textarea>
                </div>
            </div>
            <div class="px-6 pb-6 flex items-center gap-3 justify-end">
                <button wire:click="$set('showDeployModal', false)"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                    Cancel
                </button>
                <button wire:click="deploy" wire:loading.attr="disabled"
                        class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition disabled:opacity-50">
                    <span wire:loading.remove wire:target="deploy">Deploy Agent</span>
                    <span wire:loading wire:target="deploy">Deploying...</span>
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
