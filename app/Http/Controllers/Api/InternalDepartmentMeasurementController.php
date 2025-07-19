<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternalMeasurement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class InternalDepartmentMeasurementController extends Controller
{
    /**
     * عرض جميع القياسات
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

            if (in_array($currentUser->role, ['admin', 'doctor'])) {
                $measurements = InternalMeasurement::with(['internalDepartment.patient', 'doctor'])->get();
            } else {
                $measurements = InternalMeasurement::where('doctor_id', $currentUser->id)
                    ->with(['internalDepartment', 'doctor','internalDepartment.patient'])
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

            $measurement = InternalMeasurement::with(['internalDepartment', 'doctor'])->findOrFail($id);

            if (!in_array($currentUser->role, ['admin', 'doctor']) && $measurement->doctor_id != $currentUser->id) {
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
     * إنشاء قياس جديد
     */
    public function store(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً أو ممرضاً للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'internal_id' => 'required|exists:internal_departments,id',
                'temperature' => 'nullable|numeric',
                'blood_pressure' => 'nullable|string',
                'oxygen_level' => 'nullable|numeric',
                'heart_rate' => 'nullable|integer',
                'respiration_rate' => 'nullable|integer',
                'blood_sugar' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
                'blood_tests' => 'nullable|string',
                'medication_doses' => 'nullable|string',
                'medical_procedures' => 'nullable|string',
                'ecg' => 'nullable|string',
                'kidney_functions' => 'nullable|string',
                'liver_functions' => 'nullable|string',
                'blood_count' => 'nullable|string',
                'new_measurement' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $data = $validator->validated();
            $data['doctor_id'] = $currentUser->id;
            $measurement = InternalMeasurement::create($data);

            return response()->json([
                'status' => true,
                'message' => 'تم إنشاء القياس بنجاح',
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
     * تحديث قياس موجود
     */
    public function update(Request $request, $id)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            $measurement = InternalMeasurement::findOrFail($id);

            if (!in_array($currentUser->role, ['doctor', 'nurse']) && $measurement->doctor_id != $currentUser->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح بالتعديل: يمكنك فقط تعديل القياسات التي قمت بإنشائها'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'internal_id' => 'sometimes|exists:internal_departments,id',
                'temperature' => 'nullable|numeric',
                'blood_pressure' => 'nullable|string',
                'oxygen_level' => 'nullable|numeric',
                'heart_rate' => 'nullable|integer',
                'respiration_rate' => 'nullable|integer',
                'blood_sugar' => 'nullable|numeric',
                'weight' => 'nullable|numeric',
                'blood_tests' => 'nullable|string',
                'medication_doses' => 'nullable|string',
                'medical_procedures' => 'nullable|string',
                'ecg' => 'nullable|string',
                'kidney_functions' => 'nullable|string',
                'liver_functions' => 'nullable|string',
                'blood_count' => 'nullable|string',
                'new_measurement' => 'nullable|string',
                'notes' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 400);
            }

            $measurement->update($validator->validated());

            return response()->json([
                'status' => true,
                'message' => 'تم تحديث القياس بنجاح',
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
                'message' => 'حدث خطأ أثناء تحديث القياس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف قياس
     */
    public function destroy($id)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            $measurement = InternalMeasurement::findOrFail($id);

            if (!in_array($currentUser->role, ['admin', 'doctor']) && $measurement->doctor_id != $currentUser->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'غير مصرح بالحذف: يمكنك فقط حذف القياسات التي قمت بإنشائها'
                ], 403);
            }

            $measurement->delete();

            return response()->json([
                'status' => true,
                'message' => 'تم حذف القياس بنجاح'
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
}