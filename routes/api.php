<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::controller(AiController::class)->group(function() {
    Route::post('/chat', 'chat');
    Route::post('/thread', 'thread');
});