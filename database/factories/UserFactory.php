<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Cashier\Subscription;
use Laravel\Cashier\SubscriptionItem;
use App\Models\Organization;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the user has a personal organization and sets it as current.
     */
    public function withPersonalOrganization(array $overrides = []): static
    {
        return $this->afterCreating(function ($user) use ($overrides) {
            Organization::factory()
                ->state(array_merge([
                    'user_id' => $user->id,
                    'personal' => true,
                    'name' => $user->name,
                ], $overrides))
                ->create();
        });
    }

    /**
     * Indicate that the user has a personal organization with an active subscription.
     */
    public function withPersonalOrganizationAndSubscription(array $orgOverrides = [], array $subOverrides = [], array $itemOverrides = []): static
    {
        return $this->afterCreating(function ($user) use ($orgOverrides, $subOverrides, $itemOverrides) {
            $organization = Organization::factory()
                ->state(array_merge([
                    'user_id' => $user->id,
                    'personal' => true,
                    'name' => $user->name,
                ], $orgOverrides))
                ->create();

            $subscription = Subscription::factory()
                ->state(array_merge([
                    'organization_id' => $organization->id,
                    'type' => 'default',
                    'stripe_id' => 'sub_' . Str::random(24),
                    'stripe_status' => 'active',
                ], $subOverrides))
                ->create();

            SubscriptionItem::factory()
                ->state(array_merge([
                    'subscription_id' => $subscription->id,
                    'stripe_price' => config('services.stripe.price_id'),
                    'quantity' => 1,
                ], $itemOverrides))
                ->create();
        });
    }
}
