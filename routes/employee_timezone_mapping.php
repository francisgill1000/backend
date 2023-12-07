<?php

use App\Http\Controllers\EmployeeTimezoneMappingController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/employee_timezone_mapping', EmployeeTimezoneMappingController::class);
Route::get('/getemployees_timezoneids', [EmployeeTimezoneMappingController::class, 'get_employees_timezoneids']);
Route::get('/get_employeeswith_timezonename', [EmployeeTimezoneMappingController::class, 'get_employeeswith_timezonename']);
Route::post('/deletetimezone', [EmployeeTimezoneMappingController::class, 'deleteTimezone']);
Route::get('/gettimezonesinfo', [EmployeeTimezoneMappingController::class, 'gettimezonesinfo']);
Route::get('/gettimezonesinfo/search/{key}', [EmployeeTimezoneMappingController::class, 'gettimezonesinfo_search']);
Route::get('/get_employeeswith_timezonename_id/{id}', [EmployeeTimezoneMappingController::class, 'get_employeeswith_timezonename_id']);
