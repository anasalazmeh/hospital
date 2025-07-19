<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Prescriptions;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
class PrescriptionController extends Controller
{

  /**
   * إنشاء وصفة جديدة
   */
  public function store(Request $request)
  {
    $token = JWTAuth::getToken();
    $user = JWTAuth::authenticate($token);

    if (!$user || !in_array($user->role, ['doctor'])) {
      return response()->json([
        'success' => false,
        'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً',
        'data' => null
      ], 403);
    }

    $validator = Validator::make($request->all(), [
      'patient_id' => 'required|exists:patients,id',
      'prescription_text' => 'required|string|min:10',
      'notes' => 'nullable|string'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'errors' => $validator->errors()
      ], 422);
    }

    $prescription = Prescriptions::create([
      'patient_id' => $request->patient_id,
      'doctor_id' => $user->id,
      'prescription_text' => $request->prescription_text,
      'notes' => $request->notes
    ]);

    return response()->json([
      'status' => 'success',
      'data' => $prescription,
      'message' => 'تم إنشاء الوصفة بنجاح'
    ], 201);
  }


  /**
   * تحديث وصفة موجودة
   */
  public function update(Request $request, $id)
  {
    $token = JWTAuth::getToken();
    $user = JWTAuth::authenticate($token);

    if (!$user || !in_array($user->role, ['doctor'])) {
      return response()->json([
        'success' => false,
        'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً',
        'data' => null
      ], 403);
    }
    $prescription = Prescriptions::find($id);

    if (!$prescription) {
      return response()->json([
        'status' => 'error',
        'message' => 'الوصفة غير موجودة'
      ], 404);
    }

    // التحقق من أن المستخدم هو طبيب الوصفة
    if ($user->id != $prescription->doctor_id) {
      return response()->json([
        'status' => 'error',
        'message' => 'غير مصرح لك بتعديل هذه الوصفة'
      ], 403);
    }

    $validator = Validator::make($request->all(), [
      'prescription_text' => 'sometimes|required|string|min:10',
      'notes' => 'nullable|string'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'status' => 'error',
        'errors' => $validator->errors()
      ], 422);
    }

    $prescription->update($request->only(['prescription_text', 'notes']));

    return response()->json([
      'status' => 'success',
      'data' => $prescription,
      'message' => 'تم تحديث الوصفة بنجاح'
    ]);
  }
  /**
   * الحصول على وصفات مريض معين
   */
}