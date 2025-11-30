<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')
        ->name('login');

    Route::livewire('register', 'pages::auth.register')
        ->name('register');
});

Route::middleware('auth')->group(function () {
    // Email verification is now handled via OTP during registration
    // Password confirmation is no longer needed with OTP authentication
});

Route::post('logout', App\Livewire\Actions\Logout::class)
    ->name('logout');
