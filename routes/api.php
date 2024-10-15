<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Dashboard2Controller;
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\RiwayatController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/dashboard2/{siteId}', [Dashboard2Controller::class, 'index']);
Route::get('/dashboard/{siteId}', [DashboardController::class, 'index']);
// Route::get('/dashboard', function () {
//     return 'API is working'; 
// });

Route::get('/realtime/{siteId}', [RealtimeController::class, 'index']);

Route::get('/riwayat/{siteId}/{areaId}', [RiwayatController::class, 'index']);
// Route::get('/riwayat/{areaId}/{start_date}/{end_date}', [RiwayatController::class, 'index']);

Route::get('/test', function() {
    return response()->json(['message' => 'Test successful']);
});

