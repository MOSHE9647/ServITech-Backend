<?php

use Illuminate\Support\Facades\Route;

Route::get("/",function(){
    return view("welcome");
});

Route::get('reset-password', function () {
    $token = request()->query('token');
    $email = request()->query('email');
    return view('auth.reset-password', compact('token', 'email'));
})->name('reset-password');