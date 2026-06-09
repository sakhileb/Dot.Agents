<x-layouts.platform>
    <x-slot:header>Agent Scorecard</x-slot:header>
    @livewire('agents.scorecard-viewer', ['deploymentId' => $deployment->id])
</x-layouts.platform>
