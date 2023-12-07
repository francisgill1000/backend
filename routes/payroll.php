<?php

use App\Http\Controllers\PayrollController;
use Illuminate\Support\Facades\Route;


// whatsapp
Route::post('/payroll', [PayrollController::class, 'store']);
Route::get('/payroll/{id}', [PayrollController::class, 'show']);

