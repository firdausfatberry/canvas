<?php

use Canvas\Http\Controllers\Auth\ForgotPasswordController;
use Canvas\Http\Controllers\Auth\LoginController;
use Canvas\Http\Controllers\Auth\ResetPasswordController;
use Canvas\Http\Controllers\HomeController;
use Canvas\Http\Controllers\PostController;
use Canvas\Http\Controllers\SearchController;
use Canvas\Http\Controllers\StatsController;
use Canvas\Http\Controllers\TagController;
use Canvas\Http\Controllers\TopicController;
use Canvas\Http\Controllers\UploadsController;
use Canvas\Http\Controllers\UserController;
use Canvas\Http\Middleware\Admin;
use Canvas\Http\Middleware\Authorize;
use Illuminate\Support\Facades\Route;

// Authentication routes...
Route::namespace('Auth')->group(function () {
    Route::prefix('login')->group(function () {
        Route::get('/', 'LoginController@showLoginForm')->name('canvas.login');
        Route::post('/', 'LoginController@login');
    });

    Route::prefix('password')->group(function () {
        Route::get('reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('canvas.password.request');
        Route::post('email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('canvas.password.email');
        Route::get('reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('canvas.password.reset');
        Route::post('reset', [ResetPasswordController::class, 'reset'])->name('canvas.password.update');
    });

    Route::get('logout', [LoginController::class, 'logout'])->name('canvas.logout');
});

// API routes...
Route::middleware([Authorize::class])->group(function () {
    Route::prefix('api')->group(function () {
        Route::prefix('uploads')->group(function () {
            Route::post('/', [UploadsController::class, 'store']);
            Route::delete('/', [UploadsController::class, 'destroy']);
        });

        Route::prefix('posts')->group(function () {
            Route::get('/', [PostController::class, 'index']);
            Route::get('create', [PostController::class, 'create']);
            Route::get('{id}', [PostController::class, 'show']);
            Route::post('{id}', [PostController::class, 'store']);
            Route::patch('{id}', [PostController::class, 'destroy']);
        });

        Route::prefix('stats')->group(function () {
            Route::get('/', [StatsController::class, 'index']);
            Route::get('{id}', [StatsController::class, 'show']);
        });

        Route::prefix('tags')->middleware([Admin::class])->group(function () {
            Route::get('/', [TagController::class, 'index']);
            Route::get('create', [TagController::class, 'create']);
            Route::get('{id}', [TagController::class, 'show']);
            Route::get('{id}/posts', [TagController::class, 'showPosts']);
            Route::post('{id}', [TagController::class, 'store']);
            Route::patch('{id}', [TagController::class, 'destroy']);
        });

        Route::prefix('topics')->middleware([Admin::class])->group(function () {
            Route::get('/', [TopicController::class, 'index']);
            Route::get('create', [TopicController::class, 'create']);
            Route::get('{id}', [TopicController::class, 'show']);
            Route::get('{id}/posts', [TopicController::class, 'showPosts']);
            Route::post('{id}', [TopicController::class, 'store']);
            Route::patch('{id}', [TopicController::class, 'destroy']);
        });

        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])->middleware([Admin::class]);
            Route::get('create', [UserController::class, 'create'])->middleware([Admin::class]);
            Route::get('{id}', [UserController::class, 'show']);
            Route::get('{id}/posts', [UserController::class, 'showPosts']);
            Route::post('{id}', [UserController::class, 'store']);
            Route::patch('{id}', [UserController::class, 'destroy'])->middleware([Admin::class]);
        });

        Route::prefix('search')->group(function () {
            Route::get('posts', [SearchController::class, 'showPosts']);
            Route::get('tags', [SearchController::class, 'showTags'])->middleware([Admin::class]);
            Route::get('topics', [SearchController::class, 'showTopics'])->middleware([Admin::class]);
            Route::get('users', [SearchController::class, 'showUsers'])->middleware([Admin::class]);
        });
    });

    // Catch-all route...
    Route::get('/{view?}', [HomeController::class, 'index'])->where('view', '(.*)')->name('canvas');
});
