<?php

use App\Http\Controllers\DocumentController;
use Illuminate\Support\Facades\Route;

Route::apiResource('document', DocumentController::class);
