<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// API version
$API_VERSION = 'v1';

// Register and Login routes
Route::group(["prefix" => "/$API_VERSION/auth"], function () {
    Route::post("register", [AuthController::class, "register"]);
    Route::post("login", [AuthController::class, "login"]);
});

// Authenticated routes
Route::group(
    [
        'middleware' => ['auth:sanctum'],
        'prefix' => "$API_VERSION/user",
    ],
    function () {
        Route::get('profile', [AuthController::class, 'profile']);
        Route::get('logout', [AuthController::class, 'logout']);
    }
);