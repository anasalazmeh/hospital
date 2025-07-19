<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InternalDepartment;
use App\Models\Patients;
use App\Models\Room;
use App\Models\Bed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class InternalDepartmentController extends Controller
{
    /**
     * عرض جميع سجلات القسم الداخلي
     */
    public function index()
    {
        try {
            $user = $this->checkUserRole(['doctor', 'admin', 'department_head']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $internals = InternalDepartment::with('patient')->get();

            return response()->json([
                'success' => true,
                'data' => $internals
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
     * إنشاء سجل جديد في القسم الداخلي
     */
    public function store(Request $request)
    {
        try {
            $user = $this->checkUserRole(['admin', 'department_head']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $validator = Validator::make($request->all(), [
                'id_card' => 'required|string|max:255|exists:patients,id_card',
                'status' => 'required|string',
                'room_id' => 'required|exists:rooms,id',
                'bed_id' => 'required|exists:beds,id',
                'notes' => 'nullable|string',
                'discharge_date' => 'nullable|date|after_or_equal:today',
                'ecg' => 'nullable|boolean',
                'doctors' => 'nullable|array',
                'doctors.*' => 'exists:user_accounts,id',
            ], [
                'id_card.required' => 'رقم الهوية مطلوب',
                'id_card.exists' => 'رقم الهوية غير مسجل في النظام',
                'room_id.required' => 'رقم الغرفة مطلوب',
                'room_id.exists' => 'الغرفة المحددة غير موجودة',
                'bed_id.required' => 'رقم السرير مطلوب',
                'bed_id.exists' => 'السرير المحدد غير موجود',
                'doctors.*.exists' => 'احد الأطباء المحددين غير مسجل في النظام'
            ]);

            if ($validator->fails()) {
                $firstError = $validator->errors()->first();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم حفظ البيانات: ' . $firstError,
                    'errors' => $validator->errors()
                ], 422);
            }

            // التحقق من أن السرير موجود في الغرفة المحددة
            $bed = Bed::with('room')->where('id', $request->bed_id)
                ->where('room_id', $request->room_id)
                ->first();

            if (!$bed) {
                return response()->json([
                    'success' => false,
                    'message' => 'السرير غير موجود في الغرفة المحددة'
                ], 400);
            }

            // التحقق من حالة السرير
            if ($bed->status == 'occupied') {
                return response()->json([
                    'success' => false,
                    'message' => 'السرير مشغول حالياً'
                ], 400);
            }
            if ($bed->is_active === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'السرير معطلة حالياً ويحتاج الى صيانة'
                ], 400);
            }

            // التحقق من حالة الغرفة
            $room = Room::find($request->room_id);

            if ($room->status == 'occupied') {
                return response()->json([
                    'success' => false,
                    'message' => 'الغرفة مشغولة بكامل طاقتها'
                ], 400);
            }
            if ($room->is_active === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'الغرفة معطلة بكامل وتحتاج الى صيانة'
                ], 400);
            }

            $patient = Patients::where('id_card', $request->id_card)->first();
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود'
                ], 404);
            }

            // التحقق من عدم وجود المريض بالفعل في القسم الداخلي بنفس الغرفة والسرير
            $existingRecord = InternalDepartment::where('patient_id', $patient->id)
                ->whereNull("discharge_date")
                ->first();

            if ($existingRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض مسجل بالفعل في القسم الداخلي'
                ], 409);
            }

            $data = $validator->validated();
            $data['patient_id'] = $patient->id;

            $internal = InternalDepartment::create($data);
            $bed->update(['status' => 'occupied']);
            $internal->doctors()->attach($request->doctors);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء سجل القسم الداخلي بنجاح',
                'data' => $internal->load('patient')
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
     * عرض سجل محدد
     */
    public function show($id)
    {
        try {
            $user = $this->checkUserRole(['doctor', 'admin', 'department_head']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $internal = InternalDepartment::with(['patient', 'internalMeasurements', 'doctors', 'patient.analyses', 'patient.radiologies', 'patient.doctorReport.doctor', 'bed', 'room'])->find($id);

            if (!$internal) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل القسم الداخلي غير موجود'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $internal
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
     * تحديث سجل القسم الداخلي
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->checkUserRole(['admin', 'department_head']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $internal = InternalDepartment::find($id);

            if (!$internal) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل القسم الداخلي غير موجود'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|string',
                'room_id' => 'sometimes|required|exists:rooms,id',
                'bed_id' => 'sometimes|required|exists:beds,id',
                'notes' => 'nullable|string',
                'discharge_date' => 'nullable|date|after_or_equal:today',
                'ecg' => 'nullable|boolean',
                'doctors' => 'sometimes|array',
                'doctors.*' => 'exists:user_accounts,id',
            ], [
                'status.string' => 'حالة المريض يجب أن تكون نصاً',
                'room_id.required' => 'رقم الغرفة مطلوب عند التحديث',
                'room_id.exists' => 'الغرفة المحددة غير موجودة في السجلات',
                'bed_id.required' => 'رقم السرير مطلوب عند التحديث',
                'bed_id.exists' => 'السرير المحدد غير موجود في السجلات',
                'ecg.boolean' => 'تخطيط القلب يجب أن يكون قيمة منطقية (نعم/لا)',
                'doctors.array' => 'يجب إدخال الأطباء كمصفوفة',
                'doctors.*.exists' => 'أحد الأطباء المحددين غير مسجل في النظام'
            ]);

            if ($validator->fails()) {
                $firstError = $validator->errors()->first();

                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم الحفظ: ' . $firstError,
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->has('room_id') && $request->has('bed_id')) {
                $bed = Bed::where('id', $request->bed_id)->with('room')
                    ->where('room_id', $request->room_id)
                    ->first();

                if (!$bed) {
                    return response()->json([
                        'success' => false,
                        'message' => 'السرير غير موجود في الغرفة المحددة'
                    ], 400);
                }
            }

            // التحقق من حالة السرير الجديد إذا تم التغيير
            if ($request->has('bed_id') && $request->bed_id != $internal->bed_id) {
                $newBed = Bed::find($request->bed_id);
                if ($newBed && $newBed->status == 'occupied') {
                    return response()->json([
                        'success' => false,
                        'message' => 'السرير الجديد مشغول حالياً'
                    ], 400);
                }
            }

            // تحديث حالة السرير القديم إذا تم التغيير
            $oldBed = null;
            if ($request->has('bed_id') && $request->bed_id != $internal->bed_id) {
                $oldBed = Bed::find($internal->bed_id);
            }

            $updateData = [
                'status' => $request->status ?? $internal->status,
                'room_id' => $request->room_id ?? $internal->room_id,
                'bed_id' => $request->bed_id ?? $internal->bed_id,
                'notes' => $request->notes ?? $internal->notes,
                'discharge_date' => $request->discharge_date ?? $internal->discharge_date,
                'ecg' => $request->has('ecg')
                    ? $request->boolean('ecg')
                    : $internal->ecg,
            ];

            $internal->update($updateData);

            // تحديث حالة الأسرة
            if ($oldBed) {
                $oldBed->update(['status' => 'available']);
                Bed::find($request->bed_id)->update(['status' => 'occupied']);
            }

            // مزامنة الأطباء المشرفين (إذا تم إرسالهم)
            if ($request->has('doctors')) {
                $internal->doctors()->sync($request->doctors);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث سجل القسم الداخلي بنجاح',
                'data' => $internal->load('patient')
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
     * حذف سجل القسم الداخلي
     */
    public function destroy($id)
    {
        try {
            $user = $this->checkUserRole(['admin']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $internal = InternalDepartment::find($id);

            if (!$internal) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل القسم الداخلي غير موجود'
                ], 404);
            }

            $internal->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف سجل القسم الداخلي بنجاح'
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
            $user = $this->checkUserRole(['doctor', 'nurse', 'admin', 'department_head']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $patient = Patients::where('id_card', $id_card)->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل المريض غير موجود'
                ], 404);
            }

            $record = InternalDepartment::with('patient')
                ->where('patient_id', $patient->id)
                ->whereNull('discharge_date')
                ->latest()
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد سجل نشط لهذا المريض في القسم الداخلي'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $record
            ]);

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
    private function checkUserRole($allowedRoles)
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
            
            if (
                $user->role !== "admin" &&
                $user->department->name !== "قسم الداخلية"
            ) {
                return [
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون في القسم الداخلي'
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