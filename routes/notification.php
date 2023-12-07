<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsappController;


// whatsapp
Route::post('/late_employee_notification', [WhatsappController::class, 'SendNotification']);

