<div
    x-data="workflowCanvas({{ Js::from($nodes) }}, {{ Js::from($connections) }})"
    x-init="init()"
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

            {{-- Status badge --}}
            <div class="flex items-center justify-between px-1 mb-1">
                <span class="text-xs text-gray-400 dark:text-gray-500">Status</span>
                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full
                    {{ $workflow->status === 'active' ? 'bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400' : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400' }}">
                    <span class="w-1.5 h-1.5 rounded-full inline-block
                        {{ $workflow->status === 'active' ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                    {{ ucfirst($workflow->status) }}
                </span>
            </div>

            <button
                wire:click="save"
                wire:loading.attr="disabled"
                class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-semibold transition-colors"
                aria-label="Save workflow graph"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                <span wire:loading.remove wire:target="save">Save Draft</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>

            @if($workflow->status !== 'active')
                <button
                    wire:click="publish"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-green-600 hover:bg-green-700 text-white text-xs font-semibold transition-colors"
                    aria-label="Publish workflow"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span wire:loading.remove wire:target="publish">Publish Workflow</span>
                    <span wire:loading wire:target="publish">Publishing…</span>
                </button>
            @else
                <button
                    wire:click="unpublish"
                    wire:loading.attr="disabled"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 text-xs font-semibold transition-colors"
                    aria-label="Unpublish workflow"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/></svg>
                    <span wire:loading.remove wire:target="unpublish">Unpublish</span>
                    <span wire:loading wire:target="unpublish">Unpublishing…</span>
                </button>
            @endif

            <button
                wire:click="run"
                wire:loading.attr="disabled"
                class="w-full flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-yellow-400 hover:bg-yellow-500 text-gray-900 text-xs font-semibold transition-colors"
                aria-label="Execute workflow"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span wire:loading.remove wire:target="run">Run Workflow</span>
                <span wire:loading wire:target="run">Running…</span>
            </button>
        </div>
    </aside>

    {{-- ── MAIN CANVAS ── --}}
    <main
        class="relative flex-1"
        style="overflow:hidden;"
        @dragover.prevent
        @drop="onCanvasDrop($event)"
        @mousemove="onMouseMove($event)"
        @click="cancelConnecting()"
        @keydown.escape.window="cancelConnecting()"
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

        {{-- ── SVG layer ──
             x-for CANNOT be used inside SVG with Livewire: when Livewire morphs
             the DOM after a server response, Alpine loses the x-for loop variable
             scope on cloned path elements → "conn is not defined".
             Solution: a single <g x-effect="renderConnections()"> that imperatively
             creates SVG path elements using document.createElementNS so they are
             always in the correct SVG namespace with no Alpine scope dependency. --}}
        <svg
            id="connections-svg"
            class="absolute inset-0 w-full h-full"
            style="z-index:20; pointer-events:none; overflow:visible;"
        >
            <defs>
                <marker id="arrowhead" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                    <polygon points="0 0,10 3.5,0 7" fill="#8b5cf6" />
                </marker>
                <marker id="arrowhead-live" markerWidth="10" markerHeight="7" refX="9" refY="3.5" orient="auto">
                    <polygon points="0 0,10 3.5,0 7" fill="#f5be1c" />
                </marker>
            </defs>

            {{-- Saved connection lines — rendered imperatively via x-effect --}}
            <g id="connections-group" x-effect="renderConnections()"></g>

            {{-- Live preview line (single path, always in DOM, toggled via display) --}}
            <path
                id="live-connection-path"
                :d="liveConnectionPath()"
                :style="connectingMode ? 'display:block' : 'display:none'"
                fill="none"
                stroke="#f5be1c"
                stroke-width="2.5"
                stroke-dasharray="8 4"
                marker-end="url(#arrowhead-live)"
                style="pointer-events:none;"
            />
        </svg>

        {{-- ── Agent Nodes ── --}}
        <template x-for="node in nodes" :key="node.id">
            <div
                :id="'node-' + node.id"
                :style="`left: ${node.x}px; top: ${node.y}px; z-index: ${dragging === node.id ? 30 : 10};`"
                class="absolute w-44 rounded-xl shadow-lg border-2 transition-shadow hover:shadow-xl select-none"
                :class="[
                    selectedNode === node.id ? 'border-yellow-400' : 'border-purple-200 dark:border-gray-700',
                    dragging === node.id ? 'opacity-90 shadow-2xl' : '',
                    connectingMode && connectionSourceId !== node.id ? 'cursor-crosshair' : ''
                ]"
                style="background:white;"
                @click.stop="selectNode(node.id)"
            >
                {{-- Node header — drag handle --}}
                <div
                    class="flex items-center gap-2 px-3 py-2 bg-purple-600 dark:bg-purple-700 rounded-t-xl cursor-move"
                    @mousedown.stop="startDrag($event, node.id)"
                >
                    <div class="w-5 h-5 rounded bg-white/20 flex items-center justify-center shrink-0">
                        <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17H3a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2h-2"/>
                        </svg>
                    </div>
                    <span class="text-white text-xs font-semibold truncate flex-1" x-text="node.label || node.agent_key"></span>
                    <button
                        @mousedown.stop
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

                {{-- OUTPUT port (bottom-centre) — CLICK to start a connection --}}
                {{-- Large hit zone (w-6 h-6) so it’s easy to click --}}
                <div
                    class="absolute left-1/2 -translate-x-1/2 flex items-center justify-center rounded-full border-2 border-yellow-400 bg-white z-30 transition-all"
                    :class="connectingMode && connectionSourceId === node.id
                        ? 'w-5 h-5 -bottom-3 ring-2 ring-yellow-300 ring-offset-1 bg-yellow-400'
                        : 'w-5 h-5 -bottom-3 hover:scale-125 cursor-pointer'"
                    @click.stop="startConnecting(node.id)"
                    @mousedown.stop
                    title="Click to start a connection from this node"
                >
                    <div class="w-2 h-2 rounded-full bg-yellow-400"></div>
                </div>

                {{-- INPUT port (top-centre) — CLICK to complete a connection --}}
                {{-- Glows green and enlarges when another node is in connect-mode --}}
                <div
                    class="absolute left-1/2 -translate-x-1/2 flex items-center justify-center rounded-full border-2 border-purple-500 bg-white z-30 transition-all"
                    :class="connectingMode && connectionSourceId !== node.id
                        ? 'w-6 h-6 -top-3 cursor-pointer ring-2 ring-green-400 ring-offset-1 scale-125 border-green-500'
                        : 'w-5 h-5 -top-3 cursor-default'"
                    @click.stop="connectingMode && connectionSourceId !== node.id ? completeConnection(node.id) : null"
                    @mousedown.stop
                    :title="connectingMode && connectionSourceId !== node.id ? 'Click to connect here' : 'Input port'"
                >
                    <div
                        class="rounded-full transition-all"
                        :class="connectingMode && connectionSourceId !== node.id
                            ? 'w-2.5 h-2.5 bg-green-500'
                            : 'w-2 h-2 bg-purple-500'"
                    ></div>
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
                    {{ $flashType === 'success' ? 'bg-green-500 text-white' : ($flashType === 'error' ? 'bg-red-600 text-white' : 'bg-yellow-400 text-gray-900') }}"
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
function workflowCanvas(initialNodes, initialConnections) {
    return {
        nodes: [],
        connections: [],

        // Node drag (store ID, not object reference — refs go stale after canvas-synced)
        dragging: null,
        dragOffsetX: 0,
        dragOffsetY: 0,

        // Click-click connection model
        connectingMode: false,
        connectionSourceId: null,
        mouseX: 0,
        mouseY: 0,

        // Selection
        selectedNode: null,

        // Sidebar drag-and-drop
        pendingAgentKey: null,
        pendingAgentLabel: null,

        // Node dimensions (w-44 = 176px wide; header ~36px + body ~32px = 68px)
        NODE_W: 176,
        NODE_H: 68,

        init() {
            this.nodes       = initialNodes       ?? [];
            this.connections = initialConnections ?? [];

            // canvas-synced is dispatched by the server after every mutation.
            // Guard against duplicate listeners on Livewire re-render.
            if (!window.__canvasSyncedBound) {
                window.__canvasSyncedBound = true;
                window.addEventListener('canvas-synced', (e) => {
                    // Find the live Alpine component and update it
                    const el = document.querySelector('[id^="workflow-canvas"]');
                    if (el && el._x_dataStack) {
                        const data = el._x_dataStack[0];
                        if (data) {
                            data.nodes       = e.detail.nodes       ?? [];
                            data.connections = e.detail.connections ?? [];
                        }
                    }
                });
            }
            // Also update directly on init (handles Livewire re-render)
            const self = this;
            window.addEventListener('canvas-synced', function handler(e) {
                self.nodes       = e.detail.nodes       ?? [];
                self.connections = e.detail.connections ?? [];
            });
        },

        // ── Sidebar drag-and-drop ──
        startAgentDrag(e, agentKey, agentLabel) {
            this.pendingAgentKey   = agentKey;
            this.pendingAgentLabel = agentLabel;
            e.dataTransfer.effectAllowed = 'copy';
        },

        onCanvasDrop(e) {
            if (!this.pendingAgentKey) return;
            const canvas = document.getElementById('workflow-canvas');
            const rect   = canvas.getBoundingClientRect();
            const x = Math.round(e.clientX - rect.left - this.NODE_W / 2);
            const y = Math.round(e.clientY - rect.top  - this.NODE_H / 2);
            this.$wire.addNode(this.pendingAgentKey, Math.max(0, x), Math.max(0, y));
            this.pendingAgentKey   = null;
            this.pendingAgentLabel = null;
        },

        // ── Node drag-to-move ──
        startDrag(e, nodeId) {
            if (e.button !== 0 || this.connectingMode) return;
            const idx = this.nodes.findIndex(n => n.id === nodeId);
            if (idx === -1) return;
            this.dragging    = nodeId;
            this.dragOffsetX = e.clientX - this.nodes[idx].x;
            this.dragOffsetY = e.clientY - this.nodes[idx].y;
            e.preventDefault();
        },

        onMouseMove(e) {
            this.mouseX = e.clientX;
            this.mouseY = e.clientY;
            if (this.dragging) {
                const idx = this.nodes.findIndex(n => n.id === this.dragging);
                if (idx !== -1) {
                    this.nodes[idx].x = Math.max(0, Math.round(e.clientX - this.dragOffsetX));
                    this.nodes[idx].y = Math.max(0, Math.round(e.clientY - this.dragOffsetY));
                }
            }
        },

        onMouseUp(e) {
            if (this.dragging) {
                const node = this.nodes.find(n => n.id === this.dragging);
                if (node) this.$wire.moveNode(this.dragging, node.x, node.y);
                this.dragging = null;
            }
        },

        // ── Click-click connection model ──
        // Step 1: click yellow OUTPUT port → enter connect mode
        startConnecting(nodeId) {
            if (this.dragging) return;
            this.connectingMode    = true;
            this.connectionSourceId = nodeId;
        },

        // Step 2: click purple INPUT port of another node → create connection
        completeConnection(targetNodeId) {
            if (!this.connectingMode || !this.connectionSourceId) return;
            if (this.connectionSourceId !== targetNodeId) {
                this.$wire.connectNodes(this.connectionSourceId, targetNodeId, null);
            }
            this.connectingMode    = false;
            this.connectionSourceId = null;
        },

        // Cancel connect mode (canvas click, Escape key)
        cancelConnecting() {
            if (this.connectingMode) {
                this.connectingMode    = false;
                this.connectionSourceId = null;
            }
        },

        removeNode(nodeId) {
            this.$wire.removeNode(nodeId);
        },

        removeConnection(connId) {
            this.$wire.removeConnection(connId);
        },

        // ── Imperative SVG renderer ──
        // Called by x-effect on <g id="connections-group">.
        // Builds SVG path elements using createElementNS so they are always
        // in the correct SVG namespace — avoids the "conn is not defined"
        // error caused by Alpine x-for losing scope when Livewire morphs the DOM.
        renderConnections() {
            const group = document.getElementById('connections-group');
            if (!group) return;

            // Remove all existing children
            while (group.firstChild) group.removeChild(group.firstChild);

            const SVG = 'http://www.w3.org/2000/svg';
            const self = this;

            for (const conn of this.connections) {
                const d = this.connectionPath(conn);
                if (!d || d === 'M 0 0') continue;

                // Visible bezier line
                const line = document.createElementNS(SVG, 'path');
                line.setAttribute('d', d);
                line.setAttribute('fill', 'none');
                line.setAttribute('stroke', '#8b5cf6');
                line.setAttribute('stroke-width', '2');
                line.setAttribute('marker-end', 'url(#arrowhead)');
                line.style.pointerEvents = 'none';
                group.appendChild(line);

                // Wide transparent hit-zone for click-to-delete
                const hit = document.createElementNS(SVG, 'path');
                hit.setAttribute('d', d);
                hit.setAttribute('fill', 'none');
                hit.setAttribute('stroke', 'transparent');
                hit.setAttribute('stroke-width', '16');
                hit.style.pointerEvents = 'all';
                hit.style.cursor = 'pointer';
                hit.title = 'Click to delete connection';
                // Capture conn.id in closure to avoid loop variable re-binding
                (function(id) {
                    hit.addEventListener('click', function(e) {
                        e.stopPropagation();
                        self.removeConnection(id);
                    });
                })(conn.id);
                group.appendChild(hit);
            }
        },

        // ── Selection ──
        selectNode(nodeId) {
            if (this.dragging || this.connectingMode) return;
            this.selectedNode = this.selectedNode === nodeId ? null : nodeId;
        },

        selectedNodeData() {
            if (!this.selectedNode) return null;
            return this.nodes.find(n => n.id === this.selectedNode) ?? null;
        },

        updateNodeLabel(label) {
            const idx = this.nodes.findIndex(n => n.id === this.selectedNode);
            if (idx !== -1) this.nodes[idx].label = label;
        },

        // ── Port centre helpers (canvas-relative coordinates) ──
        outputPort(node) {
            // Bottom-centre of node (where yellow port lives)
            return { x: node.x + this.NODE_W / 2, y: node.y + this.NODE_H + 2 };
        },
        inputPort(node) {
            // Top-centre of node (where purple port lives)
            return { x: node.x + this.NODE_W / 2, y: node.y - 2 };
        },

        // ── SVG bezier path generators ──
        connectionPath(conn) {
            const src = this.nodes.find(n => n.id === conn.from);
            const dst = this.nodes.find(n => n.id === conn.to);
            if (!src || !dst) return 'M 0 0';
            const from = this.outputPort(src);
            const to   = this.inputPort(dst);
            const cy   = Math.max(50, Math.abs(to.y - from.y) * 0.6);
            return `M ${from.x} ${from.y} C ${from.x} ${from.y + cy}, ${to.x} ${to.y - cy}, ${to.x} ${to.y}`;
        },

        liveConnectionPath() {
            if (!this.connectingMode || !this.connectionSourceId) return 'M 0 0';
            const src = this.nodes.find(n => n.id === this.connectionSourceId);
            if (!src) return 'M 0 0';
            const canvas = document.getElementById('workflow-canvas');
            if (!canvas) return 'M 0 0';
            const rect = canvas.getBoundingClientRect();
            const from = this.outputPort(src);
            const tx   = this.mouseX - rect.left;
            const ty   = this.mouseY - rect.top;
            const cy   = Math.max(40, Math.abs(ty - from.y) * 0.5);
            return `M ${from.x} ${from.y} C ${from.x} ${from.y + cy}, ${tx} ${ty - cy}, ${tx} ${ty}`;
        },
    };
}
</script>
