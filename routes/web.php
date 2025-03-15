<?php

use Illuminate\Support\Facades\Route;

Route::get('/reset-password/{token}', function ($token) {
    return view('auth.reset', ['reset_token' => $token]);
})->name('reset-password');