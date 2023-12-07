<?php

use App\Http\Controllers\ThemeController;
use Illuminate\Support\Facades\Route;

Route::apiResource('theme', ThemeController::class);
Route::get('theme_count', [ThemeController::class,"theme_count"]);


