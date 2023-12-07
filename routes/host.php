<?php

use App\Http\Controllers\HostCompanyController;
use Illuminate\Support\Facades\Route;

Route::get('host_company_list', [HostCompanyController::class, "host_company_list"]);

Route::post('host/{id}', [HostCompanyController::class, 'update']);

Route::apiResource('host', HostCompanyController::class);
