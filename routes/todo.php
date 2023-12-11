<?php

use App\Http\Controllers\TodoController;
use Illuminate\Support\Facades\Route;

Route::apiResource('todo', TodoController::class);
