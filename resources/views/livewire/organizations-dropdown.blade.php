<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

new class extends Component {
    public ?Organization $currentOrganization;
    public Collection $organizations;
    public ?int $selectedOrganizationId;

    #[Computed]
    public function user() {
        return Auth::user();
    }

    public function mount()
    {
        $this->currentOrganization = $this->user->currentOrganization;
        $this->organizations = $this->user->allOrganizations();
        $this->selectedOrganizationId = $this->currentOrganization?->id;
    }

    public function switchOrganization(Organization $organization)
    {
        $this->authorize('switch', $organization);
        $this->user->switchOrganization($organization);
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function updatedSelectedOrganizationId(Organization $organization)
    {
        $this->switchOrganization($organization);
    }
}; ?>

<flux:dropdown position="top" align="start">
    <flux:profile
        :name="$currentOrganization?->name ?? __('No organization')"
    />
    <flux:menu>
        <flux:menu.radio.group wire:model.live="selectedOrganizationId">
            @foreach($organizations as $organization)
                <flux:menu.radio :value="$organization->id">
                    {{ $organization->name }}
                </flux:menu.radio>
            @endforeach
        </flux:menu.radio.group>
        <flux:menu.separator />
        <flux:modal.trigger name="create-organization">
            <flux:menu.item icon="plus">{{ __('New organization') }}</flux:menu.item>
        </flux:modal.trigger>
    </flux:menu>
</flux:dropdown>
