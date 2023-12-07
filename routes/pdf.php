<?php

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TestController;
use App\Http\Controllers\Reports\DailyController;
use App\Http\Controllers\Reports\ReportController;
use App\Http\Controllers\Reports\WeeklyController;
use App\Http\Controllers\Reports\MonthlyController;
use App\Http\Controllers\Reports\MonthlyMimoController;
use App\Http\Controllers\Reports\WeeklyMimoController;


Route::get('/process_reports', [DailyController::class, 'process_reports']);


Route::get('report', [ReportController::class, 'index']);


//daily
Route::get('/daily', [DailyController::class, 'daily']);
Route::get('/daily_download_pdf', [DailyController::class, 'daily_download_pdf']);
Route::get('/daily_download_csv', [ReportController::class, 'general_download_csv']);

//multi in out
// -> csv
Route::get('/multi_in_out_daily_download_csv', [ReportController::class, 'multi_in_out_daily_download_csv']);
Route::get('/multi_in_out_monthly_download_csv', [MonthlyController::class, 'multi_in_out_monthly_download_csv']);
Route::get('/multi_in_out_weekly_download_csv', [WeeklyController::class, 'multi_in_out_weekly_download_csv']);

// -> pdf view
Route::get('/multi_in_out_daily', [DailyController::class, 'mimo_daily_pdf']);
Route::get('/multi_in_out_weekly', [WeeklyController::class, 'multi_in_out_weekly_pdf']);
Route::get('/multi_in_out_monthly', [MonthlyController::class, 'multi_in_out_monthly_pdf']);


// -> pdf download
Route::get('/multi_in_out_daily_download_pdf', [DailyController::class, 'mimo_daily_download']);
Route::get('/multi_in_out_weekly_download_pdf', [WeeklyController::class, 'multi_in_out_weekly_download_pdf']);
Route::get('/multi_in_out_monthly_download_pdf', [MonthlyController::class, 'multi_in_out_monthly_download_pdf']);


// -> pdf cron
Route::get('report_multi_in_out', [ReportController::class, 'multiInOut']);
Route::get('csv_pdf', [MonthlyController::class, 'csvPdf']);



Route::get('/generateSummaryReport', [DailyController::class, 'generateSummaryReport']);
Route::get('/generatePresentReport', [DailyController::class, 'generatePresentReport']);
Route::get('/generateAbsentReport', [DailyController::class, 'generateAbsentReport']);
Route::get('/generateMissingReport', [DailyController::class, 'generateMissingReport']);
Route::get('/generateManualReport', [DailyController::class, 'generateManualReport']);

// weekly
Route::get('/weekly', [WeeklyController::class, 'weekly']);
Route::get('/weekly_download_pdf', [WeeklyController::class, 'weekly_download_pdf']);
Route::get('/weekly_download_csv', [WeeklyController::class, 'weekly_download_csv']);


//monthly
Route::get('/monthly', [MonthlyController::class, 'monthly']);
Route::get('/monthly_download_pdf', [MonthlyController::class, 'monthly_download_pdf']);
Route::get('/monthly_download_csv', [MonthlyController::class, 'monthly_download_csv']);

//multi in out


//for testing static
Route::get('/daily_html', [Controller::class, 'daily_html']);
Route::get('/weekly_html', [WeeklyController::class, 'weekly_html']);
Route::get('/monthly_html', [MonthlyController::class, 'monthly_html']);




Route::get('/chart_html', [Controller::class, 'chart_html']);
Route::get('/test_chart', [TestController::class, 'index']);

Route::get('/test_week', [TestController::class, 'test_week']);


Route::get('/daily_mimo', [Controller::class, 'mimo']);
Route::get('/weekly_mimo', [WeeklyMimoController::class, 'weekly']);
Route::get('/monthly_mimo', [MonthlyMimoController::class, 'monthly']);
