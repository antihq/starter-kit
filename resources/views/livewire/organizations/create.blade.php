<?php

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;

new class extends Component {
    public string $name = '';

    public function create()
    {
        $this->authorize('create', Organization::class);

        $this->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $organization = Auth::user()->organizations()->create([
            'name' => $this->name,
        ]);

        Auth::user()->switchOrganization($organization);

        return $this->redirect(route('dashboard'), navigate: true);
    }
}; ?>

<flux:modal name="create-organization" class="md:w-96">
    <form wire:submit="create" class="space-y-6">
        <div>
            <flux:heading size="lg">{{ __('Create Organization') }}</flux:heading>
            <flux:text class="mt-2">{{ __('Enter a name for your new organization.') }}</flux:text>
        </div>
        <flux:input :label="__('Organization Name')" :placeholder="__('Acme Inc')" wire:model="name" />

        <div class="flex gap-2">
            <flux:spacer />
            <flux:modal.close>
                <flux:button variant="ghost" type="button">{{ __('Cancel') }}</flux:button>
            </flux:modal.close>
            <flux:button type="submit" variant="primary">{{ __('Create') }}</flux:button>
        </div>
    </form>
</flux:modal>
