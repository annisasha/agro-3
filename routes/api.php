<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RealtimeController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\AreaController;
use App\Http\Controllers\SensorController;
use App\Http\Controllers\RegisterController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Riwayat2Controller;

Route::get('dashboard', [DashboardController::class, 'index']);
Route::get('realtime', [RealtimeController::class, 'index']);
Route::post('riwayat', [RiwayatController::class, 'index']);
Route::post('riwayat2', [Riwayat2Controller::class, 'index']);

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


Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout']);


Route::post('/register', [RegisterController::class, 'register']);
