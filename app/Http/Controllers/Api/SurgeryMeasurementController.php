<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurgeryMeasurement;
use App\Models\SurgeryDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class SurgeryMeasurementController extends Controller
{
  /**
   * عرض جميع قياسات الجراحة
   */
  public function index()
  {
    try {
      $token = JWTAuth::getToken();
      $currentUser = JWTAuth::authenticate($token);

      if (!$currentUser) {
        return response()->json([
          'success' => false,
          'message' => 'غير مصرح بالوصول',
          'data' => null
        ], 401);
      }

      if (in_array($currentUser->role, ['admin', 'doctor', 'nurse'])) {
        $measurements = SurgeryMeasurement::with(['surgeryDepartment.patient','doctor'])->get();
      } else {
        return response()->json([
          'success' => false,
          'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً أو ممرضاً أو مديراً',
          'data' => null
        ], 403);
      }

      return response()->json([
        'status' => true,
        'data' => $measurements
      ], 200);

    } catch (JWTException $e) {
      return response()->json([
        'status' => false,
        'message' => 'خطأ في المصادقة',
        'error' => $e->getMessage()
      ], 500);
    } catch (Exception $e) {
      return response()->json([
        'status' => false,
        'message' => 'حدث خطأ غير متوقع',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * عرض قياس جراحة معين بواسطة ID
   */
  public function show($id)
  {
    try {
      $token = JWTAuth::getToken();
      $currentUser = JWTAuth::authenticate($token);

      $measurement = SurgeryMeasurement::with(['surgeryDepartment.patient'])->findOrFail($id);

      if (!in_array($currentUser->role, ['admin', 'doctor', 'nurse'])) {
        return response()->json([
          'status' => false,
          'message' => 'غير مصرح بالوصول إلى هذا القياس'
        ], 403);
      }

      return response()->json([
        'status' => true,
        'data' => $measurement
      ], 200);

    } catch (ModelNotFoundException $e) {
      return response()->json([
        'status' => false,
        'message' => 'قياس الجراحة غير موجود'
      ], 404);
    } catch (JWTException $e) {
      return response()->json([
        'status' => false,
        'message' => 'خطأ في المصادقة',
        'error' => $e->getMessage()
      ], 500);
    } catch (Exception $e) {
      return response()->json([
        'status' => false,
        'message' => 'حدث خطأ غير متوقع',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * إنشاء قياس جراحة جديد
   */
  public function store(Request $request)
  {
    try {
      $token = JWTAuth::getToken();
      $currentUser = JWTAuth::authenticate($token);

      if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse'])) {
        return response()->json([
          'success' => false,
          'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً أو ممرضاً',
          'data' => null
        ], 403);
      }

      $validator = Validator::make($request->all(), [
        'surgery_department_id' => 'required|exists:surgery_departments,id',
        'dressing_changed' => 'nullable|date',
        'wound_condition' => 'nullable|string|max:255',
        'surgical_drains' => 'nullable|string|max:255',
        'pain_level' => 'nullable|integer|min:0|max:10',
        'medication_doses' => 'nullable|string',
        'temperature' => 'nullable|numeric|min:30|max:45',
        'blood_pressure' => 'nullable|string|max:20',
        'oxygen_level' => 'nullable|integer|min:0|max:100',
        'heart_rate' => 'nullable|integer|min:30|max:200',
        'respiration_rate' => 'nullable|integer|min:0|max:60',
        'blood_sugar' => 'nullable|numeric|min:0|max:500',
      ]);

      if ($validator->fails()) {
        return response()->json([
          'status' => false,
          'message' => 'بيانات غير صالحة',
          'errors' => $validator->errors()
        ], 422);
      }

      $surgeryDepartment = SurgeryDepartment::find($request->surgery_department_id);
      if (!$surgeryDepartment) {
        return response()->json([
          'success' => false,
          'message' => 'المريض غير موجود'
        ], 404);
      }
      if ($request->dressing_changed) {
        $surgeryDepartment->update([
          "dressing_changed" =>date('Y-m-d H:i:s', strtotime($request->dressing_changed)),
        ]);
      }
      if ($request->wound_condition) {
        $surgeryDepartment->update([
          "wound_condition" => $request->wound_condition,
        ]);
      }
      if ($request->surgical_drains) {
        $surgeryDepartment->update([

          "surgical_drains" => $request->surgical_drains,

        ]);
      }
      if ($request->pain_level) {
        $surgeryDepartment->update([
          "pain_level" => $request->pain_level,
        ]);
      }
      $data = $validator->validated();
      if (isset($data['dressing_changed'])) {
        $data['dressing_changed'] = date('Y-m-d H:i:s', strtotime($data['dressing_changed']));
      }
      $data['doctor_id'] = $currentUser->id;
      $measurement = SurgeryMeasurement::create($data);
      return response()->json([
        'status' => true,
        'message' => 'تم إنشاء قياس الجراحة بنجاح',
        'data' => $measurement
      ], 201);

    } catch (JWTException $e) {
      return response()->json([
        'status' => false,
        'message' => 'خطأ في المصادقة',
        'error' => $e->getMessage()
      ], 500);
    } catch (Exception $e) {
      return response()->json([
        'status' => false,
        'message' => 'حدث خطأ أثناء إنشاء القياس',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * تحديث قياس جراحة موجود
   */
  public function update(Request $request, $id)
  {
    try {
      $token = JWTAuth::getToken();
      $currentUser = JWTAuth::authenticate($token);

      $measurement = SurgeryMeasurement::findOrFail($id);

      if (!in_array($currentUser->role, ['doctor', 'nurse'])) {
        return response()->json([
          'status' => false,
          'message' => 'غير مصرح بالتعديل: يجب أن تكون طبيباً أو ممرضاً'
        ], 403);
      }
      if ($measurement->doctor_id !== $currentUser->id) {
        return response()->json([
          'status' => false,
          'message' => 'غير مصرح بالتعديل: يجب أن تكون نفس الشخص الذي انشائه'
        ], 403);
      }
      $validator = Validator::make($request->all(), [
        'surgery_department_id' => 'sometimes|exists:surgery_departments,id',
        'dressing_changed' => 'nullable|date',
        'wound_condition' => 'nullable|string|max:255',
        'surgical_drains' => 'nullable|string|max:255',
        'pain_level' => 'nullable|integer|min:0|max:10',
        'medication_doses' => 'nullable|string',
        'temperature' => 'nullable|numeric|min:30|max:45',
        'blood_pressure' => 'nullable|string|max:20',
        'oxygen_level' => 'nullable|integer|min:0|max:100',
        'heart_rate' => 'nullable|integer|min:30|max:200',
        'respiration_rate' => 'nullable|integer|min:0|max:60',
        'blood_sugar' => 'nullable|numeric|min:0|max:500',
      ]);

      if ($validator->fails()) {
        return response()->json([
          'status' => false,
          'message' => 'بيانات غير صالحة',
          'errors' => $validator->errors()
        ], 422);
      }

      $measurement->update($validator->validated());

      return response()->json([
        'status' => true,
        'message' => 'تم تحديث قياس الجراحة بنجاح',
        'data' => $measurement
      ], 200);

    } catch (ModelNotFoundException $e) {
      return response()->json([
        'status' => false,
        'message' => 'قياس الجراحة غير موجود'
      ], 404);
    } catch (JWTException $e) {
      return response()->json([
        'status' => false,
        'message' => 'خطأ في المصادقة',
        'error' => $e->getMessage()
      ], 500);
    } catch (Exception $e) {
      return response()->json([
        'status' => false,
        'message' => 'حدث خطأ أثناء تحديث القياس',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * حذف قياس جراحة
   */
  public function destroy($id)
  {
    try {
      $token = JWTAuth::getToken();
      $currentUser = JWTAuth::authenticate($token);

      $measurement = SurgeryMeasurement::findOrFail($id);

      if (!in_array($currentUser->role, ['admin', 'doctor'])) {
        return response()->json([
          'status' => false,
          'message' => 'غير مصرح بالحذف: يجب أن تكون طبيباً أو مديراً'
        ], 403);
      }

      $measurement->delete();

      return response()->json([
        'status' => true,
        'message' => 'تم حذف قياس الجراحة بنجاح'
      ], 200);

    } catch (ModelNotFoundException $e) {
      return response()->json([
        'status' => false,
        'message' => 'قياس الجراحة غير موجود'
      ], 404);
    } catch (JWTException $e) {
      return response()->json([
        'status' => false,
        'message' => 'خطأ في المصادقة',
        'error' => $e->getMessage()
      ], 500);
    } catch (Exception $e) {
      return response()->json([
        'status' => false,
        'message' => 'حدث خطأ أثناء حذف القياس',
        'error' => $e->getMessage()
      ], 500);
    }
  }

}