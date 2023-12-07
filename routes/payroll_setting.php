<?php

use App\Http\Controllers\PayrollSettingController;
use Illuminate\Support\Facades\Route;

Route::post('/payroll_generate_date', [PayrollSettingController::class, 'store']);
Route::get('/payroll_generate_date/{id}', [PayrollSettingController::class, 'show']);
