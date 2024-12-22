<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientsController;

use App\Http\Controllers\Api\IntensiveCarePatients;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/test', function () {
  return response()->json(['message' => 'API is working']);
});
Route::get('/patients', [PatientsController::class, 'get']);
Route::get('/patient', [PatientsController::class, 'getone']);
Route::post('/patients', [PatientsController::class, 'store']);
Route::put('/patient/update/{id_card}', [PatientsController::class, 'update']);
Route::put('/patient/updateById_card', [PatientsController::class, 'updateById_card']);


Route::post('/IntensiveCarePatients', [IntensiveCarePatients::class, 'store']);
Route::get('/IntensiveCarePatients', [IntensiveCarePatients::class, 'get']);
Route::put('/IntensiveCarePatients/{id}', [IntensiveCarePatients::class, 'update']);
Route::put('/updateMeasurementAndDose/{id}', [IntensiveCarePatients::class, 'updateMeasurementAndDose']);
