<x-layouts.platform>
    <x-slot:header>
        <div class="flex items-center gap-3">
            <a href="{{ route('workflows') }}" class="text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                Workflows
            </a>
            <svg class="w-4 h-4 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 dark:text-white font-semibold">Graph Builder</span>
        </div>
    </x-slot:header>

    {{-- Full-height canvas layout — escape the p-6 main padding so canvas is edge-to-edge --}}
    <div class="-mx-6 -my-6 h-[calc(100vh-3.5rem)]">
        <livewire:workflows.workflow-builder :workflow-id="$workflow->id" />
    </div>
</x-layouts.platform>
