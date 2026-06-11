<div
    x-data="workflowCanvas(@entangle('nodes').live, @entangle('connections').live)"
    x-init="init()"
    @mousemove.window="onMouseMove($event)"
    @mouseup.window="onMouseUp($event)"
    class="flex h-full bg-gray-50 dark:bg-gray-950 overflow-hidden border border-gray-200 dark:border-gray-800 select-none"
>

    {{-- ── LEFT PANEL: Agent Library ── --}}
    <aside class="w-64 shrink-0 bg-white dark:bg-gray-900 border-r border-gray-200 dark:border-gray-800 flex flex-col">

        {{-- Header --}}
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Agent Library</p>
        </div>

        {{-- Agent list (drag sources) --}}
        <div class="flex-1 overflow-y-auto p-3 space-y-1.5">
            @foreach($this->availableAgents as $agent)
                <div
                    draggable="true"
                    @dragstart="startAgentDrag($event, '{{ $agent['slug'] }}', '{{ addslashes($agent['name']) }}')"
                    class="flex items-center gap-2.5 px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 cursor-grab active:cursor-grabbing hover:border-purple-400 dark:hover:border-purple-500 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-all group"
                >
                    <div class="w-7 h-7 rounded-lg bg-purple-100 dark:bg-purple-900/40 flex items-center justify-center shrink-0">
                        <svg class="w-3.5 h-3.5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                        </svg>
                    </div>
                    <span class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate">{{ $agent['name'] }}</span>
                </div>
            @endforeach
        </div>

        {{-- Footer actions --}}
        <div class="p-3 border-t border-gray-200 dark:border-gray-800 space-y-2">
            <button
                wire:click="save"
                wire:loading.attr="disabled"
                class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold transition-colors"
                aria-label="Save workflow graph"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <span wire:loading.remove>Save Graph</span>
                <span wire:loading>Saving…</span>
            </button>
            <button
                wire:click="run"
                wire:loading.attr="disabled"
                class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-xs font-semibold transition-colors"
                aria-label="Execute workflow"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span wire:loading.remove>Run Workflow</span>
                <span wire:loading>Running…</span>
            </button>
        </div>
    </aside>

    {{-- ── MAIN CANVAS ── --}}
    <main
        class="relative flex-1 overflow-hidden"
        @dragover.prevent
        @drop="onCanvasDrop($event)"
        id="workflow-canvas"
    >

        {{-- Canvas grid background --}}
        <svg class="absolute inset-0 w-full h-full pointer-events-none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="grid" width="24" height="24" patternUnits="userSpaceOnUse">
                    <path d="M 24 0 L 0 0 0 24" fill="none" stroke="currentColor" stroke-width="0.5" class="text-gray-200 dark:text-gray-800"/>
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#grid)"/>
        </svg>

        {{-- ── SVG Connections ── --}}
        <svg class="absolute inset-0 w-full h-full pointer-events-none" id="connections-svg">
            <defs>
                <marker id="arrowhead" markerWidth="8" markerHeight="8" refX="6" refY="3" orient="auto">
                    <path d="M0,0 L0,6 L8,3 z" class="fill-purple-500 dark:fill-purple-400"/>
                </marker>
            </defs>

            {{-- Rendered connections --}}
            <template x-for="conn in connections" :key="conn.id">
                <g class="pointer-events-auto">
                    <path
                        :d="connectionPath(conn)"
                        fill="none"
                        class="stroke-purple-500 dark:stroke-purple-400"
                        stroke-width="2"
                        marker-end="url(#arrowhead)"
                        stroke-dasharray="none"
                    />
                    {{-- Clickable delete zone --}}
                    <path
                        :d="connectionPath(conn)"
                        fill="none"
                        stroke="transparent"
                        stroke-width="12"
                        class="cursor-pointer"
                        @click="removeConnection(conn.id)"
                    />
                </g>
            </template>

            {{-- Live drawing line --}}
            <template x-if="drawingConnection">
                <path
                    :d="liveConnectionPath()"
                    fill="none"
                    class="stroke-yellow-400"
                    stroke-width="2"
                    stroke-dasharray="6 3"
                />
            </template>
        </svg>

        {{-- ── Agent Nodes ── --}}
        <template x-for="node in nodes" :key="node.id">
            <div
                :id="'node-' + node.id"
                :style="`left: ${node.x}px; top: ${node.y}px`"
                class="absolute w-44 rounded-xl shadow-lg border-2 cursor-move transition-shadow hover:shadow-xl"
                :class="selectedNode === node.id
                    ? 'border-yellow-400 bg-white dark:bg-gray-800'
                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-850'"
                @mousedown="startDrag($event, node)"
                @click.stop="selectNode(node.id)"
            >
                {{-- Node header --}}
                <div class="flex items-center gap-2 px-3 py-2 bg-purple-600 dark:bg-purple-700 rounded-t-xl">
                    <div class="w-5 h-5 rounded bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                        </svg>
                    </div>
                    <span class="text-white text-xs font-semibold truncate flex-1" x-text="node.label || node.agent_key"></span>
                    <button
                        @click.stop="removeNode(node.id)"
                        class="w-4 h-4 rounded flex items-center justify-center text-white/60 hover:text-white hover:bg-white/20 transition-colors"
                        aria-label="Remove node"
                    >
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Node body --}}
                <div class="px-3 py-2">
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="node.agent_key"></p>
                </div>

                {{-- Input port (top-center) --}}
                <div
                    class="absolute -top-2.5 left-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-white dark:bg-gray-700 border-2 border-purple-500 cursor-crosshair flex items-center justify-center hover:scale-125 transition-transform"
                    @mousedown.stop="startConnectionFrom(node)"
                    title="Connect from this node"
                >
                    <div class="w-1.5 h-1.5 rounded-full bg-purple-500"></div>
                </div>

                {{-- Output port (bottom-center) --}}
                <div
                    class="absolute -bottom-2.5 left-1/2 -translate-x-1/2 w-4 h-4 rounded-full bg-white dark:bg-gray-700 border-2 border-yellow-400 cursor-crosshair flex items-center justify-center hover:scale-125 transition-transform"
                    @mousedown.stop="endConnectionAt(node)"
                    title="Connect to this node"
                >
                    <div class="w-1.5 h-1.5 rounded-full bg-yellow-400"></div>
                </div>
            </div>
        </template>

        {{-- Empty state --}}
        <template x-if="nodes.length === 0">
            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
                <div class="w-16 h-16 rounded-2xl bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-purple-500 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-gray-600 dark:text-gray-400">Drag agents onto the canvas</p>
                <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">Connect ports to build your AI workflow graph</p>
            </div>
        </template>

        {{-- Flash feedback --}}
        @if($flashMessage)
            <div
                x-data="{ show: true }"
                x-show="show"
                x-init="setTimeout(() => show = false, 3500)"
                x-transition:leave="transition ease-in duration-300"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-2"
                class="absolute top-4 right-4 z-50 flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-lg text-sm font-medium
                    {{ $flashType === 'success' ? 'bg-green-500 text-white' : 'bg-yellow-400 text-gray-900' }}"
                role="status"
                aria-live="polite"
            >
                {{ $flashMessage }}
            </div>
        @endif

    </main>

    {{-- ── RIGHT PANEL: Node Properties ── --}}
    <aside
        class="w-56 shrink-0 bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-800 flex flex-col"
        x-show="selectedNode"
        x-cloak
    >
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-800">
            <p class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Node Properties</p>
        </div>
        <div class="p-4 space-y-4 flex-1 overflow-y-auto">
            <template x-if="selectedNodeData()">
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Label</label>
                        <input
                            type="text"
                            :value="selectedNodeData()?.label"
                            @input="updateNodeLabel($event.target.value)"
                            class="w-full text-xs rounded-lg border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 text-gray-700 dark:text-gray-300 focus:ring-purple-500"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Agent Key</label>
                        <p class="text-xs text-gray-500 dark:text-gray-500 font-mono bg-gray-50 dark:bg-gray-800 rounded-lg px-2 py-1.5" x-text="selectedNodeData()?.agent_key"></p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Position</label>
                        <p class="text-xs text-gray-400 dark:text-gray-600 font-mono"
                            x-text="`x: ${selectedNodeData()?.x}  y: ${selectedNodeData()?.y}`"
                        ></p>
                    </div>
                    <button
                        @click="removeNode(selectedNode); selectedNode = null"
                        class="w-full flex items-center justify-center gap-2 px-3 py-2 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-medium hover:bg-red-100 dark:hover:bg-red-900/40 transition-colors"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Remove Node
                    </button>
                </div>
            </template>
        </div>
    </aside>

</div>

{{-- ── Alpine.js Canvas Controller ── --}}
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
function workflowCanvas(nodesRef, connectionsRef) {
    return {
        nodes: [],
        connections: [],

        // Drag state
        dragging: null,
        dragOffsetX: 0,
        dragOffsetY: 0,

        // Connection drawing state
        drawingConnection: false,
        connectionSource: null,     // node object
        mouseX: 0,
        mouseY: 0,

        // Selection
        selectedNode: null,

        // Dragged agent key from sidebar
        pendingAgentKey: null,
        pendingAgentLabel: null,

        init() {
            // Two-way sync with Livewire entangled refs
            this.$watch('nodesRef', (val) => { this.nodes = val ?? []; });
            this.$watch('connectionsRef', (val) => { this.connections = val ?? []; });
            this.nodes = nodesRef ?? [];
            this.connections = connectionsRef ?? [];
        },

        // ── Drag from sidebar ──
        startAgentDrag(e, agentKey, agentLabel) {
            this.pendingAgentKey   = agentKey;
            this.pendingAgentLabel = agentLabel;
            e.dataTransfer.effectAllowed = 'copy';
        },

        onCanvasDrop(e) {
            if (!this.pendingAgentKey) return;
            const canvas = document.getElementById('workflow-canvas');
            const rect   = canvas.getBoundingClientRect();
            const x = Math.round(e.clientX - rect.left - 88);
            const y = Math.round(e.clientY - rect.top  - 24);
            this.$wire.addNode(this.pendingAgentKey, Math.max(0, x), Math.max(0, y));
            this.pendingAgentKey   = null;
            this.pendingAgentLabel = null;
        },

        // ── Node drag-to-move ──
        startDrag(e, node) {
            if (e.button !== 0) return;
            this.dragging   = node;
            this.dragOffsetX = e.clientX - node.x;
            this.dragOffsetY = e.clientY - node.y;
            e.preventDefault();
        },

        onMouseMove(e) {
            if (this.dragging) {
                const x = Math.max(0, Math.round(e.clientX - this.dragOffsetX));
                const y = Math.max(0, Math.round(e.clientY - this.dragOffsetY));
                this.dragging.x = x;
                this.dragging.y = y;
            }
            this.mouseX = e.clientX;
            this.mouseY = e.clientY;
        },

        onMouseUp(e) {
            if (this.dragging) {
                // Persist final position to Livewire
                this.$wire.moveNode(this.dragging.id, this.dragging.x, this.dragging.y);
                this.dragging = null;
            }
        },

        // ── Connection drawing ──
        startConnectionFrom(node) {
            this.drawingConnection = true;
            this.connectionSource  = node;
        },

        endConnectionAt(targetNode) {
            if (!this.drawingConnection || !this.connectionSource) return;
            if (this.connectionSource.id !== targetNode.id) {
                this.$wire.connectNodes(this.connectionSource.id, targetNode.id, null);
            }
            this.drawingConnection = false;
            this.connectionSource  = null;
        },

        removeNode(nodeId) {
            this.$wire.removeNode(nodeId);
        },

        removeConnection(connId) {
            this.$wire.removeConnection(connId);
        },

        // ── Selection ──
        selectNode(nodeId) {
            this.selectedNode = this.selectedNode === nodeId ? null : nodeId;
        },

        selectedNodeData() {
            if (!this.selectedNode) return null;
            return this.nodes.find(n => n.id === this.selectedNode) ?? null;
        },

        updateNodeLabel(label) {
            const node = this.nodes.find(n => n.id === this.selectedNode);
            if (node) node.label = label;
        },

        // ── SVG path helpers ──
        nodeCenter(nodeId) {
            const node = this.nodes.find(n => n.id === nodeId);
            if (!node) return { x: 0, y: 0 };
            return { x: node.x + 88, y: node.y + 24 };
        },

        connectionPath(conn) {
            const from = this.nodeCenter(conn.from);
            const to   = this.nodeCenter(conn.to);
            if (!from || !to) return '';
            const dx = Math.abs(to.x - from.x);
            const cy = Math.min(150, dx * 0.6 + 60);
            return `M ${from.x} ${from.y + 18} C ${from.x} ${from.y + 18 + cy}, ${to.x} ${to.y - 18 - cy}, ${to.x} ${to.y - 18}`;
        },

        liveConnectionPath() {
            if (!this.connectionSource) return '';
            const canvas = document.getElementById('workflow-canvas');
            const rect   = canvas.getBoundingClientRect();
            const fx = this.connectionSource.x + 88;
            const fy = this.connectionSource.y + 42;
            const tx = this.mouseX - rect.left;
            const ty = this.mouseY - rect.top;
            const cy = Math.abs(ty - fy) * 0.5;
            return `M ${fx} ${fy} C ${fx} ${fy + cy}, ${tx} ${ty - cy}, ${tx} ${ty}`;
        },
    };
}
</script>
