<?php

use App\Http\Controllers\OrganizationInvitationAcceptController;
use App\Http\Middleware\EnsureUserIsSubscribed;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::middleware(['auth', 'verified', EnsureUserIsSubscribed::class])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::livewire('settings/profile', 'pages::settings.⚡profile')->name('settings.profile');
    Route::livewire('settings/password', 'pages::settings.⚡password')->name('settings.password');
    Route::livewire('settings/appearance', 'pages::settings.⚡appearance')->name('settings.appearance');

    Route::livewire('organizations/{organization}/settings/members', 'pages::organizations.settings.⚡members')
        ->name('organizations.settings.members');
    Route::livewire('organizations/{organization}/settings/general', 'pages::organizations.settings.⚡general')
        ->name('organizations.settings.general');
    Route::livewire('organizations/{organization}', 'pages::organizations.settings.⚡general')
        ->name('organizations.show');

    Route::get('organizations/invitations/{invitation}/accept', OrganizationInvitationAcceptController::class)
        ->middleware('signed')
        ->name('organizations.invitations.accept');
});

require __DIR__.'/auth.php';

require __DIR__.'/billing.php';
