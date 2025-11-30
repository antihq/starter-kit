<?php

use App\Models\User;

it('allows OTP login without email verification requirement', function () {
    // This test verifies that OTP authentication works independently
    // of the old email verification system

    $user = User::factory()->unverified()->create();

    // Simulate OTP verification
    $otp = $user->createOneTimePassword()->password;
    $result = $user->attemptLoginUsingOneTimePassword($otp);

    expect($result->isOk())->toBeTrue();

    // Email verification is now handled during registration via OTP
    // For existing users, OTP login works regardless of verification status
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});
