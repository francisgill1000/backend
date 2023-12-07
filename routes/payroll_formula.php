<?php

use App\Http\Controllers\PayrollFormulaController;
use Illuminate\Support\Facades\Route;


// whatsapp
Route::post('/payroll_formula', [PayrollFormulaController::class, 'store']);
Route::get('/payroll_formula/{id}', [PayrollFormulaController::class, 'show']);

