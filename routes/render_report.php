<?php

use App\Http\Controllers\Shift\RenderController;
use Illuminate\Support\Facades\Route;

Route::get('render_multi_inout_report', [RenderController::class, 'renderMultiInOut']);
Route::get('render_general_report', [RenderController::class, 'renderGeneral']);

Route::get('render_off', [RenderController::class, 'renderOff']);
Route::get('render_absent', [RenderController::class, 'renderAbsent']);
Route::get('render_leaves/{company_id}', [RenderController::class, 'renderLeaves']);
Route::get('render_holidays/{company_id}', [RenderController::class, 'renderHolidays']);
Route::get('renderLeavesCron/{company_id}', [RenderController::class, 'renderLeavesCron']);
Route::get('renderHolidaysCron/{company_id}', [RenderController::class, 'renderHolidaysCron']);

Route::post('render_employee_report', [RenderController::class, 'renderEmployeeReport']);
