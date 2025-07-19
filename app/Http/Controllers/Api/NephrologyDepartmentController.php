<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NephrologyDepartment;
use App\Models\Patients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class NephrologyDepartmentController extends Controller
{
  /**
   * عرض جميع سجلات قسم الكلية
   */
  public function index()
  {
    try {
      $user = $this->checkUserRole();
      if (!$user['success']) {
        return response()->json($user, 403);
      }
      $nephrologyDepartments = NephrologyDepartment::with(['patient'])->get();
      return response()->json([
        'success' => true,
        'data' => $nephrologyDepartments
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ في استرجاع البيانات',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * إنشاء سجل جديد في قسم الكلية
   */
  public function store(Request $request)
  {
    try {
      $user = $this->checkUserRole();
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $validator = Validator::make($request->all(), [
        'id_card' => 'required|string|exists:patients,id_card',
        'primary_diagnosis' => 'required|string',
        'secondary_diagnosis' => 'nullable|string',
        'dialysis_days' => 'nullable|string|max:255',
        'kidney_status' => 'required|in:طبيعي,قصور بسيط,قصور متوسط,قصور شديد,فشل كلوي',
        'dialysis_type' => 'nullable|string|required_if:kidney_status,فشل كلوي',
      ]);

      if ($validator->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'بيانات غير صالحة',
          'errors' => $validator->errors()
        ], 422);
      }

      $patient = Patients::where('id_card', $request->id_card)->first();
      if (!$patient) {
        return response()->json([
          'success' => false,
          'message' => 'المريض غير موجود'
        ], 404);
      }
      if ($request->kidney_status && $request->dialysis_days) {
        $patient->update([
          'kidney_status' => $request->kidney_status,
        ]);
      }
      if ($request->dialysis_days) {
        $patient->update([
          'dialysis_days' => $request->dialysis_days,
        ]);
      }
      // التحقق من عدم وجود المريض بالفعل في قسم الكلية
      $existingRecord = NephrologyDepartment::where('patient_id', $patient->id)
        ->first();

      if ($existingRecord) {
        return response()->json([
          'success' => false,
          'message' => 'المريض مسجل بالفعل في قسم الكلية'
        ], 409);
      }

      $data = $validator->validated();
      $data['patient_id'] = $patient->id;
      $data['admission_date'] = now();

      $nephrologyDepartment = NephrologyDepartment::create($data);

      return response()->json([
        'success' => true,
        'message' => 'تم إنشاء السجل بنجاح',
        'data' => $nephrologyDepartment
      ], 201);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ أثناء إنشاء السجل',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * عرض سجل محدد في قسم الكلية
   */
  public function show($id)
  {
    try {
      $user = $this->checkUserRole();
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $nephrologyDepartment = NephrologyDepartment::with(['patient', 'nephrologyMeasurement'])->find($id);

      if (!$nephrologyDepartment) {
        return response()->json([
          'success' => false,
          'message' => 'السجل غير موجود'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => $nephrologyDepartment
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ في استرجاع البيانات',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * تحديث سجل في قسم الكلية
   */
  public function update(Request $request, $id)
  {
    try {
      $user = $this->checkUserRole();
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $nephrologyDepartment = NephrologyDepartment::find($id);

      if (!$nephrologyDepartment) {
        return response()->json([
          'success' => false,
          'message' => 'السجل غير موجود'
        ], 404);
      }

      $validator = Validator::make($request->all(), [
        'primary_diagnosis' => 'sometimes|string',
        'secondary_diagnosis' => 'nullable|string',
        'kidney_status' => 'sometimes|in:طبيعي,قصور بسيط,قصور متوسط,قصور شديد,فشل كلوي',
        'dialysis_type' => 'nullable|string|required_if:kidney_status,فشل كلوي',
        'discharge_date' => 'nullable|date',
        'dialysis_days' => 'nullable|string|max:255',
        'discharge_status' => 'nullable|in:مستمر في العلاج,تحسن,ثابت,ساءت,نقل,وفاة'
      ]);

      if ($validator->fails()) {
        return response()->json([
          'success' => false,
          'message' => 'بيانات غير صالحة',
          'errors' => $validator->errors()
        ], 422);
      }
      $patient = Patients::find($nephrologyDepartment->patient_id);
      if (!$patient) {
        return response()->json([
          'success' => false,
          'message' => 'المريض غير موجود'
        ], 404);
      }
      if ($request->kidney_status && $request->dialysis_days) {
        $patient->update([
          'kidney_status' => $request->kidney_status,
        ]);
      }
      if ($request->dialysis_days) {
        $patient->update([
          'dialysis_days' => $request->dialysis_days,
        ]);
      }
      $nephrologyDepartment->update($validator->validated());

      return response()->json([
        'success' => true,
        'message' => 'تم تحديث السجل بنجاح',
        'data' => $nephrologyDepartment
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ أثناء تحديث السجل',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * حذف سجل من قسم الكلية
   */
  public function destroy($id)
  {
    try {
      $user = $this->checkUserRole(['admin']);
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $nephrologyDepartment = NephrologyDepartment::find($id);

      if (!$nephrologyDepartment) {
        return response()->json([
          'success' => false,
          'message' => 'السجل غير موجود'
        ], 404);
      }

      $nephrologyDepartment->delete();

      return response()->json([
        'success' => true,
        'message' => 'تم حذف السجل بنجاح'
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ أثناء حذف السجل',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * البحث عن سجل باستخدام بطاقة الهوية
   */
  public function getId($id_card)
  {
    try {
      $user = $this->checkUserRole();
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $patient = Patients::where('id_card', $id_card)->first();

      if (!$patient) {
        return response()->json([
          'success' => false,
          'message' => 'المريض غير موجود'
        ], 404);
      }

      $record = NephrologyDepartment::with(['patient'])
        ->where('patient_id', $patient->id)
        ->first();

      if (!$record) {
        return response()->json([
          'success' => false,
          'message' => 'لا يوجد سجل نشط في قسم الكلية لهذا المريض'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => $record
      ], 200);

    } catch (\Exception $e) {
      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب البيانات',
        'error' => $e->getMessage()
      ], 500);
    }
  }

  /**
   * التحقق من صلاحية المستخدم
   */
  private function checkUserRole($allowedRoles = ['doctor', 'nurse', 'admin'])
  {
    try {
      $token = JWTAuth::getToken();
      $user = JWTAuth::authenticate($token);

      if (!$user) {
        return [
          'success' => false,
          'message' => 'المستخدم غير معروف'
        ];
      }

      if (!in_array($user->role, $allowedRoles)) {
        return [
          'success' => false,
          'message' => 'غير مصرح بالوصول: يجب أن تكون ' . implode(' أو ', $allowedRoles)
        ];
      }

      return [
        'success' => true,
        'user' => $user
      ];

    } catch (JWTException $e) {
      return [
        'success' => false,
        'message' => 'خطأ في المصادقة'
      ];
    }
  }
}