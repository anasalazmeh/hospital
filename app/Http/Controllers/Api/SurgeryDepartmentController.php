<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SurgeryDepartment;
use App\Models\Patients;
use App\Models\Room;
use App\Models\Bed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class SurgeryDepartmentController extends Controller
{
    /**
     * عرض جميع سجلات قسم الجراحة
     */
    public function index()
    {
        try {
            $user = $this->checkUserRole(['doctor', 'admin', 'department_head']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $surgeries = SurgeryDepartment::with(['patient', 'doctors', 'surgeryMeasurement'])->get();

            return response()->json([
                'success' => true,
                'data' => $surgeries
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
     * إنشاء سجل جديد في قسم الجراحة
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
                'surgeryType' => 'required|string',
                'surgeryDate' => 'required|date',
                'room_id' => 'required|exists:rooms,id',
                'bed_id' => 'required|exists:beds,id',
                'notes' => 'nullable|string',
                'dressing_changed' => 'nullable|date',
                'wound_condition' => 'nullable|string',
                'surgical_drains' => 'nullable|string',
                'pain_level' => 'nullable|integer|min:0|max:10',
                'discharge_date' => 'nullable|date|after_or_equal:surgeryDate',
                'medications' => 'nullable|string',
                'doctors' => 'required|array',
                'doctors.*' => 'exists:user_accounts,id',
            ], [
                'id_card.required' => 'رقم الهوية مطلوب',
                'id_card.exists' => 'رقم الهوية غير مسجل في النظام',
                'surgeryType.required' => 'نوع الجراحة مطلوب',
                'surgeryDate.required' => 'تاريخ الجراحة مطلوب',
                'surgeryDate.date' => 'تاريخ الجراحة يجب أن يكون تاريخاً صالحاً',
                'room_id.required' => 'رقم الغرفة مطلوب',
                'room_id.exists' => 'الغرفة المحددة غير موجودة',
                'bed_id.required' => 'رقم السرير مطلوب',
                'bed_id.exists' => 'السرير المحدد غير موجود',
                'pain_level.min' => 'يجب أن لا يقل مستوى الألم عن 0',
                'pain_level.max' => 'يجب أن لا يزيد مستوى الألم عن 10',
                'discharge_date.after_or_equal' => 'تاريخ الخروج يجب أن يكون بعد أو يساوي تاريخ الجراحة',
                'doctors.required' => 'يجب تحديد الأطباء المشرفين',
                'doctors.*.exists' => 'أحد الأطباء المحددين غير مسجل في النظام'
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

            // التحقق من عدم وجود المريض بالفعل في قسم الجراحة بنفس الغرفة والسرير
            $existingRecord = SurgeryDepartment::where('patient_id', $patient->id)
                ->whereNull("discharge_date")
                ->first();

            if ($existingRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض مسجل بالفعل في قسم الجراحة'
                ], 409);
            }

            $data = $validator->validated();
            $data['patient_id'] = $patient->id;

            $surgery = SurgeryDepartment::create($data);
            $bed->update(['status' => 'occupied']);
            $surgery->doctors()->attach($request->doctors);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء سجل الجراحة بنجاح',
                'data' => $surgery->load(['patient', 'doctors'])
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

            $surgery = SurgeryDepartment::with([
                'patient',
                'doctors',
                'surgeryMeasurement',
                'patient.radiologies',
                'patient.analyses',
                'bed',
                'room'
            ])->find($id);

            if (!$surgery) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل الجراحة غير موجود'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $surgery
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
     * تحديث سجل الجراحة
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->checkUserRole(['admin', 'department_head']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $surgery = SurgeryDepartment::find($id);

            if (!$surgery) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل الجراحة غير موجود'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|string',
                'surgeryType' => 'sometimes|string',
                'surgeryDate' => 'sometimes|date',
                'room_id' => 'sometimes|required|exists:rooms,id',
                'bed_id' => 'sometimes|required|exists:beds,id',
                'notes' => 'nullable|string',
                'dressing_changed' => 'nullable|date',
                'wound_condition' => 'nullable|string',
                'surgical_drains' => 'nullable|string',
                'pain_level' => 'nullable|integer|min:0|max:10',
                'discharge_date' => 'nullable|date|after_or_equal:surgeryDate',
                'medications' => 'nullable|string',
                'doctors' => 'sometimes|array',
                'doctors.*' => 'exists:user_accounts,id',
            ], [
                'surgeryType.string' => 'نوع الجراحة يجب أن يكون نصاً',
                'surgeryDate.date' => 'تاريخ الجراحة يجب أن يكون تاريخاً صالحاً',
                'room_id.required' => 'رقم الغرفة مطلوب عند التحديث',
                'room_id.exists' => 'الغرفة المحددة غير موجودة في السجلات',
                'bed_id.required' => 'رقم السرير مطلوب عند التحديث',
                'bed_id.exists' => 'السرير المحدد غير موجود في السجلات',
                'pain_level.min' => 'يجب أن لا يقل مستوى الألم عن 0',
                'pain_level.max' => 'يجب أن لا يزيد مستوى الألم عن 10',
                'discharge_date.after_or_equal' => 'تاريخ الخروج يجب أن يكون بعد أو يساوي تاريخ الجراحة',
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
            if ($request->has('bed_id') && $request->bed_id != $surgery->bed_id) {
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
            if ($request->has('bed_id') && $request->bed_id != $surgery->bed_id) {
                $oldBed = Bed::find($surgery->bed_id);
            }

            $updateData = [
                'status' => $request->status ?? $surgery->status,
                'surgeryType' => $request->surgeryType ?? $surgery->surgeryType,
                'surgeryDate' => $request->surgeryDate ?? $surgery->surgeryDate,
                'room_id' => $request->room_id ?? $surgery->room_id,
                'bed_id' => $request->bed_id ?? $surgery->bed_id,
                'notes' => $request->notes ?? $surgery->notes,
                'dressing_changed' => $request->dressing_changed ?? $surgery->dressing_changed,
                'wound_condition' => $request->wound_condition ?? $surgery->wound_condition,
                'surgical_drains' => $request->surgical_drains ?? $surgery->surgical_drains,
                'pain_level' => $request->pain_level ?? $surgery->pain_level,
                'discharge_date' => $request->discharge_date ?? $surgery->discharge_date,
                'medications' => $request->medications ?? $surgery->medications,
            ];

            $surgery->update($updateData);

            // تحديث حالة الأسرة
            if ($oldBed) {
                $oldBed->update(['status' => 'available']);
                Bed::find($request->bed_id)->update(['status' => 'occupied']);
            }

            // مزامنة الأطباء المشرفين (إذا تم إرسالهم)
            if ($request->has('doctors')) {
                $surgery->doctors()->sync($request->doctors);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث سجل الجراحة بنجاح',
                'data' => $surgery->load(['patient', 'doctors'])
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
     * حذف سجل الجراحة
     */
    public function destroy($id)
    {
        try {
            $user = $this->checkUserRole(['admin']);
            if (!$user['success']) {
                return response()->json($user, 403);
            }

            $surgery = SurgeryDepartment::find($id);

            if (!$surgery) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل الجراحة غير موجود'
                ], 404);
            }

            $surgery->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف سجل الجراحة بنجاح'
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
     * البحث عن سجل جراحة باستخدام بطاقة الهوية
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

            $record = SurgeryDepartment::with(['patient', 'doctors'])
                ->where('patient_id', $patient->id)
                ->whereNull('discharge_date')
                ->latest()
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يوجد سجل جراحة نشط لهذا المريض'
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
                $user->department->name !== "قسم الجراحة"
            ) {
                return [
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون في قسم الجراحة'
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