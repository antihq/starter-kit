<?php

use App\Http\Middleware\EnsureUserIsSubscribed;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('subscription-required', 'pages::billing.⚡subscription-required')->name('subscription-required');
});

Route::middleware(['auth', 'verified', EnsureUserIsSubscribed::class])->group(function () {
    Route::livewire('billing-portal', 'pages::billing.⚡billing-portal')->name('billing.portal');
});
