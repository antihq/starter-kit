<?php

use App\Models\User;
use App\Models\Organization;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;


new #[Layout('layouts::auth')] class extends Component {
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string|digits:6')]
    public string $one_time_password = '';

    public bool $showOtpForm = false;

    /**
     * Send OTP for registration.
     */
    public function sendOtp(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
        ]);

        $this->ensureIsNotRateLimited();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $user->sendOneTimePassword();

        $this->showOtpForm = true;

        $this->reset('one_time_password');
    }

    /**
     * Handle OTP verification and complete registration.
     */
    public function register(): void
    {
        $this->validate();

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => __('Registration failed. Please try again.'),
            ]);
        }

        $result = $user->attemptLoginUsingOneTimePassword($this->one_time_password);

        if ($result->isOk()) {
            RateLimiter::clear($this->throttleKey());
            Session::regenerate();

            $user->markEmailAsVerified();

            Organization::create([
                'name' => $user->name,
                'user_id' => $user->id,
                'personal' => true,
            ]);

            event(new Registered($user));

            Auth::login($user);

            $this->redirectIntended(route('dashboard', absolute: false), navigate: true);

            return;
        }

        RateLimiter::hit($this->throttleKey());

        throw ValidationException::withMessages([
            'one_time_password' => $result->validationMessage(),
        ]);
    }

    /**
     * Ensure OTP request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the OTP rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    /**
     * Reset the form.
     */
    public function resetForm(): void
    {
        $this->reset(['name', 'email', 'one_time_password', 'showOtpForm']);

        $this->resetErrorBag();
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Create an account')" :description="__('Enter your details to create your account and verify your email')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    @if (!$showOtpForm)
        <!-- Registration Form -->
        <form wire:submit="sendOtp" class="flex flex-col gap-6">
            <!-- Name -->
            <flux:input
                wire:model="name"
                :label="__('Name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                :placeholder="__('Full name')"
            />

            <!-- Email Address -->
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Create account') }}
                </flux:button>
            </div>
        </form>
    @else
        <!-- OTP Verification Form -->
        <form wire:submit="register" class="flex flex-col gap-6">
            <!-- Email Display -->
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('We sent a one-time password to:') }} <strong>{{ $email }}</strong>
            </div>

            <!-- One-Time Password -->
            <div>
                <flux:otp
                    wire:model="one_time_password"
                    :label="__('One-time password')"
                    length="6"
                    submit="auto"
                />

                <flux:error name="email" />
            </div>

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary" class="flex-1">
                    {{ __('Verify and create account') }}
                </flux:button>
                
                <flux:button wire:click="resetForm" variant="outline" class="flex-1">
                    {{ __('Start over') }}
                </flux:button>
            </div>

            <!-- Resend OTP -->
            <div class="text-center">
                <flux:link 
                    wire:click="sendOtp" 
                    wire:loading.attr="disabled"
                    class="text-sm"
                >
                    {{ __("Didn't receive a code? Resend") }}
                </flux:link>
            </div>
        </form>
    @endif

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        <span>{{ __('Already have an account?') }}</span>
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>
</div>
