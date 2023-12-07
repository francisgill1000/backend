<?php

use App\Http\Controllers\Dashboards\VisitorDashboard;
use App\Http\Controllers\Reports\VisitorMonthlyController;
use App\Http\Controllers\VisitorController;
use App\Http\Controllers\VisitorMappingController;
use Illuminate\Support\Facades\Route;


Route::get('visitor-count', VisitorDashboard::class);
Route::post('visitor/{id}', [VisitorController::class, 'update']);
Route::apiResource('visitor', VisitorController::class);
Route::get('visitors_with_type', [VisitorController::class, "visitors_with_type"]);

Route::get('/get_visitors_with_timezonename', [VisitorMappingController::class, 'get_visitors_with_timezonename']);
Route::post('/visitor_timezone_mapping', [VisitorMappingController::class, "store"]);
Route::post('/visitor_test', [VisitorController::class, "store_test"]);
