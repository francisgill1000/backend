<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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


Route::post("user", [UserController::class, "store"]);
Route::post("login", [AuthController::class, "login"]);

Route::middleware('auth:sanctum')->get("me", [AuthController::class, "me"]);
Route::middleware('auth:sanctum')->get("logout", [AuthController::class, "logout"]);




Route::apiResource("expense", ExpenseController::class);
