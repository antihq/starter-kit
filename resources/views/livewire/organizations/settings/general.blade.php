<?php

use Livewire\Component;

use App\Models\Organization;
use Flux\Flux;

new class extends Component {
    public Organization $organization;

    public string $name;

    public function mount()
    {
        $this->authorize('update', $this->organization);

        $this->name = $this->organization->name;
    }

    public function edit()
    {
        $this->authorize('update', $this->organization);

        $this->validate([
            'name' => ['required'],
        ]);

        $this->organization->update(['name' => $this->name]);

        Flux::toast(
            heading: __('Saved'),
            text: __('Organization updated successfully.'),
            variant: 'success'
        );
    }
}; ?>

<x-slot:breadcrumbs>
    @include('partials.organization-settings-breadcrumbs', ['organization' => $organization, 'current' => __('General')])
</x-slot:breadcrumbs>

<div>
    @include('partials.organization-settings-heading')
    <div class="flex items-start max-md:flex-col">
        @include('partials.organization-settings-sidebar', ['organization' => $organization])
        <flux:separator class="md:hidden" />
        <div class="flex-1 self-stretch max-md:pt-6">
            <header>
                <flux:heading>
                    {{ __('General Settings') }}
                </flux:heading>
                <flux:text class="mt-2">
                    {{ __('Update your organization name below to keep your workspace up to date.') }}
                </flux:text>
            </header>
            <form wire:submit="edit" class="max-w-lg space-y-6 mt-6">
                <flux:input wire:model="name" :label="__('Name')" />
                <flux:button type="submit" variant="primary">
                    {{ __('Save') }}
                </flux:button>
            </form>
        </div>
    </div>
</div>
