<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FeedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);

    Route::resource('feeds', FeedController::class);

    Route::post('feed-comment/{feedId}', [FeedController::class, 'comment']);

    Route::post('feed-like/{feedId}', [FeedController::class, 'like']);

    Route::post('feed-unlike/{feedId}', [FeedController::class, 'unlike']);
});

