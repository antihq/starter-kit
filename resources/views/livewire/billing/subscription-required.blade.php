<?php

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Actions\Logout;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

new #[Layout('components.layouts.auth')] class extends Component {
    public Collection $organizations;
    public ?int $selectedOrganizationId;

    #[Computed]
    public function user() {
        return Auth::user();
    }

    public function mount()
    {
        $this->organizations = $this->user->allOrganizations();
        $this->selectedOrganizationId = $this->user->currentOrganization?->id;

        if ($this->user->currentOrganization->subscribed('default')) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function switchOrganization(Organization $organization)
    {
        $this->authorize('switch', $organization);

        $this->user->switchOrganization($organization);

        if ($organization->subscribed('default')) {
            $this->redirect(route('dashboard'), navigate: true);
        }
    }

    public function updatedSelectedOrganizationId(Organization $organization)
    {
        $this->switchOrganization($organization);
    }

    public function goToCheckout()
    {
        $stripePriceId = config('services.stripe.price_id');

        $this->redirect($this->user->currentOrganization->newSubscription('default', $stripePriceId)
            ->trialDays(31)
            ->checkout([
                'success_url' => route('settings.profile'),
                'cancel_url' => route('subscription-required'),
            ])->asStripeCheckoutSession()->url, navigate: false);
    }

    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div class="mt-4 flex flex-col gap-6">
    <div class="flex justify-center">
        <flux:dropdown position="bottom" align="center">
            <flux:profile :name="$this->user->currentOrganization->name" />
            <flux:menu>
                <flux:menu.radio.group wire:model.live="selectedOrganizationId">
                    @foreach($organizations as $organization)
                        <flux:menu.radio :value="$organization->id">
                            {{ $organization->name }}
                        </flux:menu.radio>
                    @endforeach
                </flux:menu.radio.group>
            </flux:menu>
        </flux:dropdown>
    </div>
    <flux:text class="text-center">
        {{ __('You need to subscribe to our service to continue.') }}
    </flux:text>
    <div class="flex flex-col items-center justify-between space-y-3">
        <flux:button wire:click="goToCheckout" variant="primary" class="w-full">
            {{ __('Proceed to Checkout') }}
        </flux:button>
        <flux:link class="text-sm cursor-pointer" wire:click="logout">
            {{ __('Log out') }}
        </flux:link>
    </div>
</div>
