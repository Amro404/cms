<?php

use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\ContentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\UserController;
use App\Http\Middleware\RateLimitByRole;

Route::get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1')->group(function () {

    // Authentication routes
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/register', [AuthController::class, 'register']); 

        // Protected auth routes
        Route::middleware(['auth:sanctum'])->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/user', [AuthController::class, 'user']);
            Route::put('/profile', [AuthController::class, 'updateProfile']);
            Route::post('/users/{user}/assign-role', [AuthController::class, 'assignRole']);
            Route::post('/users/{user}/give-permission', [AuthController::class, 'givePermission']);
        });
    });

    // Protected routes
    Route::middleware(['auth:sanctum', RateLimitByRole::class])->group(function () {

        // User management routes
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('users.index');
            Route::post('/', [UserController::class, 'store'])->name('users.store');
            Route::get('/{user}', [UserController::class, 'show'])->name('users.show');
            Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        // Content management routes
        Route::prefix('contents')->group(function () {
            Route::get('/', [ContentController::class, 'index'])->name('contents.index');
            Route::post('/', [ContentController::class, 'store'])->name('contents.store');
            Route::get('/{content}', [ContentController::class, 'show'])->name('contents.show');
            Route::put('/{content}', [ContentController::class, 'update'])->name('contents.update');
            Route::delete('/{content}', [ContentController::class, 'destroy'])->name('contents.destroy');
            Route::post('/{content}/publish', [ContentController::class, 'publish'])->name('contents.publish');
            Route::post('/{content}/draft', [ContentController::class, 'draft'])->name('contents.draft');
            Route::post('/{content}/archive', [ContentController::class, 'archive'])->name('contents.archive');
            Route::get('/category/{category}', [ContentController::class, 'category'])->name('contents.category');
            Route::get('/tag/{tag}', [ContentController::class, 'tag'])->name('contents.tag');
        });
    });
});

