<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\StatsController;
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

Route::get("user", [UserController::class, "index"]);
Route::post("user", [UserController::class, "store"]);
Route::post("login", [AuthController::class, "login"]);

Route::middleware('auth:sanctum')->get("me", [AuthController::class, "me"]);
Route::middleware('auth:sanctum')->get("logout", [AuthController::class, "logout"]);




Route::apiResource("expense", ExpenseController::class);
Route::get("monthlyChartData", [ExpenseController::class, "monthlyChartData"]);
Route::get("customExpense", [ExpenseController::class, "customExpense"]);
Route::get("todayExpense", [ExpenseController::class, "todayExpense"]);
Route::get("weeklyExpense", [ExpenseController::class, "weeklyExpense"]);
Route::get("monthlyExpense", [ExpenseController::class, "monthlyExpense"]);

Route::apiResource("income", IncomeController::class);
Route::get("todayIncome", [IncomeController::class, "todayIncome"]);
Route::get("weeklyIncome", [IncomeController::class, "weeklyIncome"]);
Route::get("monthlyIncome", [IncomeController::class, "monthlyIncome"]);


Route::get("today-stats", [StatsController::class, "getTodayStats"]);
Route::get("weekly-stats", [StatsController::class, "getWeeklyStats"]);
Route::get("monthly-stats", [StatsController::class, "getMonthlyStats"]);


Route::post("item-update/{id}", [ItemController::class, "itemUpdate"]);

Route::apiResource("item", ItemController::class);
