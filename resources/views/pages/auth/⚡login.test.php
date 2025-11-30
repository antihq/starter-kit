<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;

it('renders the login screen', function () {
    $response = get('/login');

    $response->assertStatus(200);
});

it('sends OTP when user enters valid email', function () {
    $user = User::factory()->create();

    Notification::fake();

    $response = Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->call('sendOtp');

    $response
        ->assertHasNoErrors()
        ->assertSet('showOtpForm', true);

    Notification::assertSentTo($user, OneTimePasswordNotification::class);
});

it('shows OTP form after sending email', function () {
    $user = User::factory()->create();

    Notification::fake();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->call('sendOtp')
        ->assertSee('One-time password');
});

it('authenticates users with valid OTP', function () {
    $user = User::factory()->create();

    // Generate OTP manually for testing
    $otp = $user->createOneTimePassword()->password;

    $response = Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('one_time_password', $otp)
        ->set('showOtpForm', true)
        ->call('login');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();
});

it('rejects authentication with an invalid OTP', function () {
    $user = User::factory()->create();

    $response = Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('one_time_password', '123456')
        ->set('showOtpForm', true)
        ->call('login');

    $response->assertHasErrors('one_time_password');

    assertGuest();
});

it('does not reveal if user exists when sending OTP', function () {
    $response = Livewire::test('pages::auth.login')
        ->set('email', 'nonexistent@example.com')
        ->call('sendOtp');

    // Should still show OTP form for security
    $response->assertSet('showOtpForm', true);
});

it('logs out authenticated users', function () {
    /** @var User $user */
    $user = User::factory()->create();

    $response = actingAs($user)->post('/logout');

    $response->assertRedirect('/');

    assertGuest();
});
