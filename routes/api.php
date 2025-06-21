<?php

use App\Enums\UserRoles;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RepairRequestController;

/**
 * This file defines the API routes for the application.
 * 
 * Use this file to organize and manage endpoints that will be consumed by external clients,
 * such as mobile applications or third-party services. Ensure that all routes are properly
 * secured and follow RESTful conventions for maintainability and scalability.
 * 
 * All the routes are 'localized', which means
 * they automatically adapt to the user's preferred language based on the request.
 * This enables seamless multi-language support for all API endpoints.
 * 
 * The localization language can be provided by a parameter in the URL, with a cookie,
 * in session, or by an 'Accept-Language' header provided by the client in format 'es'
 * or 'en'.
 */
Route::localizedGroup(function () {
    // Authentication Routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::post('reset-password', [AuthController::class, 'sendResetLink'])->name('auth.send-reset-link');
        Route::put('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');
    });

    // Article Routes
    Route::prefix('articles')->group(function () {
        Route::get('', [ArticleController::class, 'index'])->name('articles.index');
        Route::post('', [ArticleController::class, 'store'])->name('articles.store');
        Route::get('{article:category}', [ArticleController::class, 'show'])->name('articles.show');
        Route::put('{article}', [ArticleController::class, 'update'])->name('articles.update');
        Route::delete('{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
        Route::get('id/{article}', [ArticleController::class, 'showById'])->name('articles.showById');
    });

    // Subcategory routes
    Route::prefix('subcategories')->group(function () {
        Route::get('', [SubcategoryController::class, 'index'])->name('subcategories.index');
        Route::post('', [SubcategoryController::class, 'store'])->name('subcategories.store');
        Route::get('{subcategory}', [SubcategoryController::class, 'show'])->name('subcategories.show');
        Route::put('{subcategory}', [SubcategoryController::class, 'update'])->name('subcategories.update');
        Route::delete('{subcategory}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');
    });

    // Protected Routes (need to be logged in)
    Route::middleware('auth:api')->group(function () {
        // SupportRequest routes
        Route::prefix('support-request')->group(function () {
            Route::get('', [SupportRequestController::class, 'index'])->name('support-request.index');
            Route::post('', [SupportRequestController::class, 'store'])->name('support-request.store');
            Route::get('{supportRequest}', [SupportRequestController::class, 'show'])->name('support-request.show');
            Route::put('{supportRequest}', [SupportRequestController::class, 'update'])->name('support-request.update');
            Route::delete('{supportRequest}', [SupportRequestController::class, 'destroy'])->name('support-request.destroy');
        });

        // User Routes
        Route::prefix('user')->group(function () {
            Route::get('profile', [UserController::class, 'profile'])->name('user.profile');
            Route::put('profile', [UserController::class, 'updateBasicInformation'])->name('user.profile.update');
            Route::put('password', [UserController::class, 'updatePassword'])->name('user.password.update');
        });

        // ADMIN Role Protected Routes (must have been logged in as ADMIN)
        Route::middleware("role:" . UserRoles::ADMIN->value)->group(function () {
            // Category Routes
            Route::prefix('category')->group(function () {
                Route::get('', [CategoryController::class, 'index'])->name('category.index');
                Route::post('', [CategoryController::class, 'store'])->name('category.store');
                Route::get('{category:name}', [CategoryController::class, 'show'])->name('category.show');
                Route::put('{category:name}', [CategoryController::class, 'update'])->name('category.update');
                Route::delete('{category:name}', [CategoryController::class, 'destroy'])->name('category.destroy');
            });

            // RepairRequest Routes
            Route::prefix('repair-request')->group(function () {
                Route::get('', [RepairRequestController::class, 'index'])->name('repair-request.index');
                Route::post('', [RepairRequestController::class, 'store'])->name('repair-request.store');
                Route::get('{repairRequest:receipt_number}', [RepairRequestController::class, 'show'])->name('repair-request.show');
                Route::put('{repairRequest:receipt_number}', [RepairRequestController::class, 'update'])->name('repair-request.update');
                Route::delete('{repairRequest:receipt_number}', [RepairRequestController::class, 'destroy'])->name('repair-request.destroy');
            });
        });
    });
});