<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\get;

it('renders the registration screen', function () {
    $response = get('/register');

    $response->assertStatus(200);
});

it('creates a user and sends OTP with valid data', function () {
    Notification::fake();

    $response = Livewire::test('pages::auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('sendOtp');

    $response
        ->assertHasNoErrors()
        ->assertSet('showOtpForm', true);

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    Notification::assertSentTo($user, \Spatie\OneTimePasswords\Notifications\OneTimePasswordNotification::class);
});

it('completes registration with valid OTP', function () {
    Notification::fake();

    // First step: create user
    $livewire = Livewire::test('pages::auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('sendOtp');

    $user = User::where('email', 'test@example.com')->first();
    $otp = $user->createOneTimePassword()->password;

    // Second step: verify OTP
    $response = $livewire
        ->set('one_time_password', $otp)
        ->set('showOtpForm', true)
        ->call('register');

    $response
        ->assertHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('creates a personal organization for the new user on registration', function () {
    Notification::fake();

    $userName = 'Test User';
    $userEmail = 'test2@example.com';

    // First step: create user
    $livewire = Livewire::test('pages::auth.register')
        ->set('name', $userName)
        ->set('email', $userEmail)
        ->call('sendOtp');

    $user = User::where('email', $userEmail)->first();
    $otp = $user->createOneTimePassword()->password;

    // Second step: verify OTP
    $livewire
        ->set('one_time_password', $otp)
        ->set('showOtpForm', true)
        ->call('register');

    $organization = Organization::first();

    expect($organization)->not->toBeNull();
    expect($organization->user->is($user))->toBeTrue();
    expect($organization->personal)->toBeTrue();
    expect($user->fresh()->currentOrganization->is($organization))->toBeTrue();
});

it('rejects registration with invalid OTP', function () {
    Notification::fake();

    // First step: create user
    $livewire = Livewire::test('pages::auth.register')
        ->set('name', 'Test User')
        ->set('email', 'test@example.com')
        ->call('sendOtp');

    $response = $livewire
        ->set('one_time_password', '123456')
        ->set('showOtpForm', true)
        ->call('register');

    $response->assertHasErrors('one_time_password');
});

it('validates unique email during registration', function () {
    User::factory()->create(['email' => 'existing@example.com']);

    $response = Livewire::test('pages::auth.register')
        ->set('name', 'Test User')
        ->set('email', 'existing@example.com')
        ->call('sendOtp');

    $response->assertHasErrors('email');
});
