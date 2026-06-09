<x-layouts.platform>
    <x-slot:header>Agent Chat</x-slot:header>
    @livewire('agents.agent-chat', ['deploymentId' => $deployment->id])
</x-layouts.platform>
