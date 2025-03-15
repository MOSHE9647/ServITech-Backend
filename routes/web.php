<?php

use Illuminate\Support\Facades\Route;

Route::get('/reset-password/{token}/{email}', function ($token, $email) {
    return view('auth.reset', ['user_email' => $email, 'reset_token' => $token]);
})->name('reset-password');

// Route::get('/reset-password', function () {
//     $user_email = session('reset_password_email');
//     $reset_token = session('reset_password_token');

//     return view('auth.reset', compact('user_email', 'reset_token'));
// })->name('reset-password');