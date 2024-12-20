<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientsController;

Route::get('/', function () {
    return view('welcome');
});



// Route::get('/patients', [PatientsController::class, 'get']);
// Route::post('/patients', [PatientsController::class, 'post']);