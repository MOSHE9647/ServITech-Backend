<?php

use App\Enums\UserRoles;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Model\ArticleController;
use App\Http\Controllers\Model\CategoryController;
use App\Http\Controllers\Model\RepairRequestController;
use App\Http\Controllers\Model\SubcategoryController;
use App\Http\Controllers\Model\SupportRequestController;
use App\Http\Controllers\Model\UserController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('auth.login');
    Route::post('register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('logout', [AuthController::class,'logout'])->name('auth.logout');
    Route::post('reset-password', [AuthController::class,'sendResetLink'])->name('auth.send-reset-link');
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
    
    // SupportRequest routes
    Route::prefix('support-request')->group(function () {
        Route::get('', [SupportRequestController::class, 'index'])->name('support-request.index');
        Route::post('', [SupportRequestController::class, 'store'])->name('support-request.store');
        Route::get('{supportRequest}', [SupportRequestController::class, 'show'])->name('support-request.show');
        Route::put('{supportRequest}', [SupportRequestController::class, 'update'])->name('support-request.update');
        Route::delete('{supportRequest}', [SupportRequestController::class, 'destroy'])->name('support-request.destroy');
    });

    // ADMIN Role Protected Routes
    Route::middleware("role:" . UserRoles::ADMIN->value)->group(function () {
        // Category routes
        Route::prefix('category')->group(function () {
            Route::get('', [CategoryController::class,'index'])->name('category.index');
            Route::post('', [CategoryController::class,'store'])->name('category.store');
            Route::get('{category:name}', [CategoryController::class,'show'])->name('category.show');
            Route::put('{category:name}', [CategoryController::class,'update'])->name('category.update');
            Route::delete('{category:name}', [CategoryController::class,'destroy'])->name('category.destroy');
        });
    
        // Subcategory routes
        Route::prefix('subcategories')->group(function () {
            Route::get('', [SubcategoryController::class, 'index'])->name('subcategories.index');
            Route::post('', [SubcategoryController::class, 'store'])->name('subcategories.store');
            Route::get('{subcategory}', [SubcategoryController::class, 'show'])->name('subcategories.show');
            Route::put('{subcategory}', [SubcategoryController::class, 'update'])->name('subcategories.update');
            Route::delete('{subcategory}', [SubcategoryController::class, 'destroy'])->name('subcategories.destroy');
        });
    
        // Article routes
        Route::prefix('articles')->group(function () {
            Route::get('', [ArticleController::class, 'index'])->name('articles.index');
            Route::post('', [ArticleController::class, 'store'])->name('articles.store');
            Route::get('{article}', [ArticleController::class, 'show'])->name('articles.show');
            Route::put('{article}', [ArticleController::class, 'update'])->name('articles.update');
            Route::delete('{article}', [ArticleController::class, 'destroy'])->name('articles.destroy');
        });

        // RepairRequest routes
        Route::prefix('repair-request')->group(function () {
            Route::get('', [RepairRequestController::class, 'index'])->name('repair-request.index');
            Route::post('', [RepairRequestController::class, 'store'])->name('repair-request.store');
            Route::get('{repairRequest:receipt_number}', [RepairRequestController::class, 'show'])->name('repair-request.show');
            Route::put('{repairRequest:receipt_number}', [RepairRequestController::class, 'update'])->name('repair-request.update');
            Route::delete('{repairRequest:receipt_number}', [RepairRequestController::class, 'destroy'])->name('repair-request.destroy');
        });
    });
});