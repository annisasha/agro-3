<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Riwayat2Controller;
use App\Http\Controllers\TanamanController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\RingkasanDataController;
use App\Http\Controllers\UserSiteController;
use App\Http\Controllers\AreaOptionController;

// Route::middleware('auth:sanctum')->get('/user/sites', [UserSiteController::class, 'index']);
Route::middleware('auth:sanctum')->prefix('site')->group(function () {
    Route::get('/', [UserSiteController::class, 'index']);
    Route::post('/', [UserSiteController::class, 'store']);
    Route::get('{id}', [UserSiteController::class, 'show']);
    Route::put('{id}', [UserSiteController::class, 'update']);
    Route::delete('{id}', [UserSiteController::class, 'destroy']);
});

// Route::middleware('auth:sanctum')->get('/dashboard', [DashboardController::class, 'index']);
// Route::get('realtime', [RealtimeController::class, 'index']);
Route::middleware('auth:sanctum')->get('/realtime', [RealtimeController::class, 'index']);
Route::middleware('auth:sanctum')->post('/riwayat2', [Riwayat2Controller::class, 'index']);
Route::middleware('auth:sanctum')->get('/area-options', [AreaOptionController::class, 'index']);
// Route::post('riwayat', [RiwayatController::class, 'index']);
// Route::post('riwayat2', [Riwayat2Controller::class, 'index']);

Route::get('/area', [AreaController::class, 'index']);
Route::get('/area/{id}', [AreaController::class, 'show']);
Route::post('/area', [AreaController::class, 'store']);
Route::put('/area/{id}', [AreaController::class, 'update']);
Route::delete('/area/{id}', [AreaController::class, 'destroy']);

Route::get('/sensor', [SensorController::class, 'index']);
Route::get('/sensor/{id}', [SensorController::class, 'show']);
Route::post('/sensor', [SensorController::class, 'store']);
Route::put('/sensor/{id}', [SensorController::class, 'update']);
Route::delete('/sensor/{id}', [SensorController::class, 'destroy']);

// Route::get('/tanaman', [TanamanController::class, 'index']);
// Route::get('/tanaman/{pl_id}', [TanamanController::class, 'show']);
// Route::post('/tanaman', [TanamanController::class, 'store']);
// Route::put('/tanaman/{pl_id}', [TanamanController::class, 'update']);
// Route::delete('/tanaman/{pl_id}', [TanamanController::class, 'destroy']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/tanaman', [TanamanController::class, 'index']);
    Route::get('/tanaman/{pl_id}', [TanamanController::class, 'show']);
    Route::post('/tanaman', [TanamanController::class, 'store']);
    Route::put('/tanaman/{pl_id}', [TanamanController::class, 'update']);
    Route::delete('/tanaman/{pl_id}', [TanamanController::class, 'destroy']);
});


Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout']);
Route::middleware('auth:sanctum')->get('/profile', [AuthController::class, 'show']);
Route::middleware('auth:sanctum')->get('/dashboard', [DashboardController::class, 'index']);


Route::post('/register', [RegisterController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/chat/send', [ChatbotController::class, 'send']);
    Route::get('/chat/history/{name_chat}', [ChatbotController::class, 'getHistoryByNameChat']);
    Route::delete('/chat/history/{name_chat}', [ChatbotController::class, 'deleteByNameChat']);
    Route::get('/chat/names', [ChatbotController::class, 'listChats']);
    Route::put('/chat/rename-chat/{name_chat}', [ChatbotController::class, 'renameChat']);
    Route::post('/chat/new', [ChatbotController::class, 'newChat']);
    Route::post('/data/summary', [RingkasanDataController::class, 'getSummary']);
});
