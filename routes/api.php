<?php

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {

    //wallet
    Route::post('/wallet', [WalletController::class, 'charge']);
    Route::post('/wallet/transfer/request', [WalletController::class, 'transferRequest']);
    Route::post('/wallet/transfer/confirm/{id}', [WalletController::class, 'confirmTransfer']);
    Route::post('/wallet/transfer/cancel/{id}', [WalletController::class, 'cancelTransfer']);

    // Transactions history
    Route::get('/transactions', [WalletController::class, 'transactions']);
    // Notifications
    Route::get('/notifications', [ServiceController::class, 'notifications']);
    Route::post('/broadcast-notification', [ServiceController::class, 'broadcastNotification']);


    // Services CRUD
    Route::apiResource('services', ServiceController::class);

    // Purchase service
    Route::post('/services/purchase/{id}', [ServiceController::class, 'purchase']);





    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
});
