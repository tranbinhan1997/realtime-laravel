<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LinkPreviewController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UploadController;






Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::post('/posts', [PostController::class, 'store']);
    Route::get('/posts', [PostController::class, 'index']);
    Route::post('/upload-image', [UploadController::class, 'upload']);
    Route::post('/preview-link', [LinkPreviewController::class, 'preview']);
});

