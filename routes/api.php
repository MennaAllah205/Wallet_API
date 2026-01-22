<?php

use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WalletController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::post('/register', [AuthController::class, 'register'])->middleware('custom.throttle:3,1'); 
Route::post('/login', [AuthController::class, 'login'])->middleware('custom.throttle:5,1'); 

Route::middleware(['auth:sanctum', 'custom.throttle:60,1'])->group(function () {

    Route::post('/wallet', [WalletController::class, 'charge'])->middleware('custom.throttle:10,1'); 
    Route::post('/wallet/transfer/request', [WalletController::class, 'transferRequest'])->middleware('custom.throttle:5,1'); 
    Route::post('/wallet/transfer/confirm/{id}', [WalletController::class, 'confirmTransfer'])->middleware('custom.throttle:10,1'); 
    Route::post('/wallet/transfer/cancel/{id}', [WalletController::class, 'cancelTransfer'])->middleware('custom.throttle:10,1'); 

    // Transactions history
    Route::get('/transactions', [WalletController::class, 'transactions'])->middleware('custom.throttle:30,1'); 
    // Notifications
    Route::get('/notifications', [ServiceController::class, 'notifications'])->middleware('custom.throttle:30,1'); 
    Route::post('/broadcast-notification', [ServiceController::class, 'broadcastNotification'])->middleware('custom.throttle:10,1'); 


    // Services CRUD
    Route::apiResource('services', ServiceController::class)->middleware('custom.throttle:20,1'); 

    // Purchase service
    Route::post('/services/purchase/{id}', [ServiceController::class, 'purchase'])->middleware('custom.throttle:15,1'); 





    Route::post('/logout', [AuthController::class, 'logout'])->middleware('custom.throttle:20,1'); 
    Route::get('/user', function (Request $request) {
        return $request->user();
    })->middleware('custom.throttle:60,1'); 
});
