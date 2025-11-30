<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;


new #[Layout('layouts::auth')] class extends Component {
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string|digits:6')]
    public string $one_time_password = '';

    public bool $showOtpForm = false;

    /**
     * Send OTP to user email.
     */
    public function sendOtp(): void
    {
        $this->validate([
            'email' => 'required|string|email',
        ]);

        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            // Don't reveal if user exists or not for security
            $this->showOtpForm = true;
            return;
        }

        $user->sendOneTimePassword();

        $this->showOtpForm = true;

        $this->reset('one_time_password');
    }

    /**
     * Handle OTP login.
     */
    public function login(): void
    {
        $this->validate();

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $result = $user->attemptLoginUsingOneTimePassword($this->one_time_password);

        if ($result->isOk()) {
            RateLimiter::clear($this->throttleKey());
            Session::regenerate();

            Auth::login($user);

            $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
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
        $this->reset(['email', 'one_time_password', 'showOtpForm']);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Log in to your account')" :description="__('Enter your email to receive a one-time password')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    @if (!$showOtpForm)
        <!-- Email Form -->
        <form wire:submit="sendOtp" class="flex flex-col gap-6">
            <!-- Email Address -->
            <flux:input
                wire:model="email"
                :label="__('Email address')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <div class="flex items-center justify-end">
                <flux:button variant="primary" type="submit" class="w-full">
                    {{ __('Send One-Time Password') }}
                </flux:button>
            </div>
        </form>
    @else
        <!-- OTP Form -->
        <form wire:submit="login" class="flex flex-col gap-6">
            <!-- Email Display -->
            <div class="text-sm text-zinc-600 dark:text-zinc-400">
                {{ __('We sent a one-time password to:') }} <strong>{{ $email }}</strong>
            </div>

            <!-- One-Time Password -->
            <flux:input
                wire:model="one_time_password"
                :label="__('One-time password')"
                type="text"
                required
                autofocus
                autocomplete="one-time-code"
                placeholder="123456"
                maxlength="6"
            />

            <div class="flex gap-3">
                <flux:button type="submit" variant="primary" class="flex-1">
                    {{ __('Log in') }}
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

    @if (Route::has('register'))
        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Don\'t have an account?') }}</span>
            <flux:link :href="route('register')" wire:navigate>{{ __('Sign up') }}</flux:link>
        </div>
    @endif
</div>