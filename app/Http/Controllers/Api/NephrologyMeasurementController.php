<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NephrologyMeasurement;
use App\Models\NephrologyDepartment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class NephrologyMeasurementController extends Controller
{
    /**
     * عرض جميع قياسات الكلى
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

            if (in_array($currentUser->role, ['admin', 'doctor', 'nephrologist'])) {
                $measurements = NephrologyMeasurement::with(['nephrologyDepartment.patient', 'doctor'])->get();
            } else {
                $measurements = NephrologyMeasurement::where('doctor_id', $currentUser->id)
                    ->with(['nephrologyDepartment', 'doctor'])
                    ->get();
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
     * عرض قياس معين بواسطة ID
     */
    public function show($id)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            $measurement = NephrologyMeasurement::with(['nephrologyDepartment.patient', 'doctor'])->findOrFail($id);

            if (!in_array($currentUser->role, ['admin', 'doctor', 'nephrologist']) && $measurement->doctor_id != $currentUser->id) {
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
                'message' => 'القياس غير موجود'
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
     * إنشاء قياس جديد للكلى
     */
    public function store(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nephrologist', 'nurse'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً أو أخصائي كلى أو ممرضاً',
                    'data' => null
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nephrology_department_id' => 'required|exists:nephrology_departments,id',
                'type_mmeasurements' => 'required|string',
                'weight' => 'required|numeric|min:0',
                'height' => 'nullable|numeric|min:0',
                'blood_pressure' => 'nullable|string|max:20',
                'pulse' => 'nullable|integer|min:0',
                'temperature' => 'nullable|numeric',
                'creatinine' => 'nullable|numeric|min:0',
                'urea' => 'nullable|numeric|min:0',
                'gfr' => 'nullable|numeric|min:0',
                'sodium' => 'nullable|numeric',
                'potassium' => 'nullable|numeric',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['doctor_id'] = $currentUser->id;
            $measurement = NephrologyMeasurement::create($data);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء قياس الكلى بنجاح',
                'data' => $measurement->load('nephrologyDepartment.patient', 'doctor')
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
     * تحديث قياس الكلى
     */
    public function update(Request $request, $id)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            $measurement = NephrologyMeasurement::findOrFail($id);

            if (!in_array($currentUser->role, ['doctor', 'nephrologist']) && $measurement->doctor_id != $currentUser->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح بالتعديل: يجب أن تكون طبيباً أو أخصائي كلى أو مالكاً للقياس'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nephrology_department_id' => 'sometimes|exists:nephrology_departments,id',
                'before_weight' => 'sometimes|numeric|min:0',
                'after_weight' => 'sometimes|numeric|min:0',
                'height' => 'nullable|numeric|min:0',
                'blood_pressure' => 'sometimes|string|max:20',
                'pulse' => 'sometimes|integer|min:0',
                'temperature' => 'nullable|numeric',
                'creatinine' => 'sometimes|numeric|min:0',
                'urea' => 'sometimes|numeric|min:0',
                'gfr' => 'sometimes|numeric|min:0',
                'sodium' => 'sometimes|numeric',
                'potassium' => 'sometimes|numeric',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $measurement->update($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث قياس الكلى بنجاح',
                'data' => $measurement->load('nephrologyDepartment.patient', 'doctor')
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'القياس غير موجود'
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
     * حذف قياس الكلى
     */
    public function destroy($id)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            $measurement = NephrologyMeasurement::findOrFail($id);

            if (!in_array($currentUser->role, ['admin', 'doctor', 'nephrologist']) && $measurement->doctor_id != $currentUser->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح بالحذف: يجب أن تكون مديراً أو طبيباً أو أخصائي كلى أو مالكاً للقياس'
                ], 403);
            }

            $measurement->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف قياس الكلى بنجاح'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'القياس غير موجود'
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

    /**
     * الحصول على قياسات مريض معين
     */
    public function getByPatient($patientId)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!in_array($currentUser->role, ['admin', 'doctor', 'nephrologist'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح بالوصول'
                ], 403);
            }

            $measurements = NephrologyMeasurement::whereHas('nephrologyDepartment', function($query) use ($patientId) {
                $query->where('patient_id', $patientId);
            })->with(['nephrologyDepartment', 'doctor'])->get();

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
     * الحصول على قياسات قسم كلى معين
     */
    public function getByDepartment($departmentId)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!in_array($currentUser->role, ['admin', 'doctor', 'nephrologist'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح بالوصول'
                ], 403);
            }

            $measurements = NephrologyMeasurement::where('nephrology_department_id', $departmentId)
                ->with(['nephrologyDepartment', 'doctor'])
                ->get();

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
}