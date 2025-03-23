<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Model\CategoryController;
use App\Http\Controllers\Model\SubcategoryController;
use App\Http\Controllers\Model\UserController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('logout', [AuthController::class,'logout'])->name('auth.logout');
    Route::post('reset-password', [AuthController::class,'sendResetLink'])->name('auth.reset-password');
    Route::put('reset-password', [AuthController::class,'resetPassword'])->name('auth.reset-password');
});

// Protected routes
Route::middleware('auth:api')->group(function () {
    // User routes
    Route::prefix('user')->group(function () {
        Route::get('profile', [UserController::class, 'profile'])->name('user.profile');
        Route::put('profile', [UserController::class, 'updateBasicInformation'])->name('profile.update');
        Route::put('password', [UserController::class, 'updatePassword'])->name('password.update');
    });

    // Category routes
    Route::prefix('category')->group(function () {
        Route::get('', [CategoryController::class,'index'])->name('category.index');
        Route::post('', [CategoryController::class,'store'])->name('category.store');
        Route::get('{category:name}', [CategoryController::class,'show'])->name('category.show');
        Route::put('{category:name}', [CategoryController::class,'update'])->name('category.update');
        Route::delete('{category:name}', [CategoryController::class,'destroy'])->name('category.destroy');
    });
});

// Subcategory routes
Route::prefix('subcategories')->group(function () {
    Route::get('', [SubcategoryController::class, 'index'])->name('subcategories.index');
});