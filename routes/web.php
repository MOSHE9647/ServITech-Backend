<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return view('welcome');
});

Route::get('reset-password/{token}/{email}', function ($token, $email) {
    return view('auth.reset', ['user_email' => $email, 'reset_token' => $token]);
})->name('reset-password-view');

Route::post('reset-password', [AuthController::class, 'resetPassword'])
    ->prefix('auth')
    ->name('reset-password');