<?php

use App\Http\Controllers\Dashboards\EmployeeDashboard;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::get('/employee-statistics', [EmployeeDashboard::class, 'statistics']);
Route::get('/clear-attendance-cache', [EmployeeDashboard::class, 'clearEmployeeCache']);


Route::post('employee-store', [EmployeeController::class, 'employeeStore']);
Route::get('employee-single/{id}', [EmployeeController::class, 'employeeSingle']);
Route::post('employee-update/{id}', [EmployeeController::class, 'employeeUpdate']);
Route::post('employee-login-update/{id}', [EmployeeController::class, 'employeeLoginUpdate']);

Route::get('employee-announcements/{id}', [EmployeeController::class, 'employeeAnnouncements']);
Route::get('employee-today-announcements/{id}', [EmployeeController::class, 'employeeTodayAnnouncements']);

Route::get('department-employee', [DepartmentController::class, 'departmentEmployee']);
