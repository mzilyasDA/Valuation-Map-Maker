<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Https\Controllers\LocationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
// route user
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/location', [LocationController::class, 'index']);
Route::post('/location', [LocationController::class, 'store']);
