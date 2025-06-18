<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

/**
 * Localization routes for the reset password page.
 * This route will handle the display of the reset password form
 * with the token and email parameters passed in the query string.
 */
Route::localizedGroup(function () {
    Route::get('reset-password', function () {
        $authToken = request()->query('token');
        $email = request()->query('email');
        return view('auth.reset-password', compact('authToken', 'email'));
    })->name('reset-password');
});