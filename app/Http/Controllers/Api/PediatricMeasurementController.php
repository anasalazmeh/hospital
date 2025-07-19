<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PediatricMeasurement;
use App\Models\Pediatric;
use App\Models\Patients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PediatricMeasurementController extends Controller
{
    /**
     * عرض جميع قياسات الأطفال
     */
    public function index()
    {
        try {
            $user = $this->checkUserRole(['admin', 'doctor', 'nurse']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $measurements = PediatricMeasurement::with(['pediatric.patient', 'doctor'])
                ->when(!in_array($user['user']->role, ['admin', 'doctor']), function($query) use ($user) {
                    return $query->where('doctor_id', $user['user']->id);
                })
                ->get();

            return response()->json([
                'success' => true,
                'data' => $measurements
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
     * عرض قياس محدد
     */
    public function show($id)
    {
        try {
            $user = $this->checkUserRole(['admin', 'doctor', 'nurse']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $measurement = PediatricMeasurement::with(['pediatric.patient', 'doctor'])->findOrFail($id);

            // التحقق من صلاحية المستخدم
            if (!in_array($user['user']->role, ['admin', 'doctor'])) {
                if ($measurement->doctor_id != $user['user']->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'غير مصرح بالوصول إلى هذا القياس'
                    ], 403);
                }
            }

            return response()->json([
                'success' => true,
                'data' => $measurement
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'القياس غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في استرجاع البيانات',
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
            $user = $this->checkUserRole(['doctor', 'nurse']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $validator = Validator::make($request->all(), [
                'pediatric_id' => 'required|exists:pediatrics,id',
                'temperature' => 'nullable|numeric',
                'heart_rate' => 'nullable|integer',
                'blood_pressure' => 'nullable|string|max:20',
                'respiratory_rate' => 'nullable|integer',
                'oxygen_saturation' => 'nullable|numeric|between:0,100',
                'glucose_level' => 'nullable|numeric',
                'urine_output' => 'nullable|numeric',
                'serum' => 'nullable|string',
                'medications' => 'nullable|string',
                'new_measurement' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            // $pediatric = Pediatric::find( $request->pediatric_id);
            // if (!$pediatric) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'المريض غير مسجل في قسم الأطفال'
            //     ], 404);
            // }

            $data = $validator->validated();
            $data['doctor_id'] = $user['user']->id;

            $measurement = PediatricMeasurement::create($data);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء القياس بنجاح',
                'data' => $measurement->load(['pediatric', 'doctor'])
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
            $user = $this->checkUserRole(['doctor', 'nurse']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $measurement = PediatricMeasurement::findOrFail($id);

            // التحقق من صلاحية المستخدم
            if ($measurement->doctor_id != $user['user']->id && !in_array($user['user']->role, ['admin', 'doctor'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بتعديل هذا القياس'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'temperature' => 'nullable|numeric',
                'heart_rate' => 'nullable|integer',
                'blood_pressure' => 'nullable|string|max:20',
                'respiratory_rate' => 'nullable|integer',
                'oxygen_saturation' => 'nullable|numeric|between:0,100',
                'glucose_level' => 'nullable|numeric',
                'urine_output' => 'nullable|numeric',
                'serum' => 'nullable|string',
                'medications' => 'nullable|string',
                'new_measurement' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات غير صالحة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $measurement->update($validator->validated());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث القياس بنجاح',
                'data' => $measurement->load(['pediatric', 'doctor'])
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'القياس غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
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
            $user = $this->checkUserRole(['admin', 'doctor']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $measurement = PediatricMeasurement::findOrFail($id);

            // التحقق من صلاحية المستخدم
            if ($measurement->doctor_id != $user['user']->id && $user['user']->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بحذف هذا القياس'
                ], 403);
            }

            $measurement->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف القياس بنجاح'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'القياس غير موجود'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف القياس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * البحث عن قياسات المريض باستخدام بطاقة الهوية
     */
    public function getByPatientId($id_card)
    {
        try {
            $user = $this->checkUserRole(['admin', 'doctor', 'nurse']);
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

            $pediatric = Pediatric::where('patient_id', $patient->id)->first();

            if (!$pediatric) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير مسجل في قسم الأطفال'
                ], 404);
            }

            $measurements = PediatricMeasurement::with(['doctor'])
                ->where('pediatric_id', $pediatric->id)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $measurements
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
     * التحقق من صلاحية المستخدم
     */
    private function checkUserRole($allowedRoles = [])
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