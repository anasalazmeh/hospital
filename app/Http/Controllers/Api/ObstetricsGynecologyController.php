<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ObstetricsGynecology;
use App\Models\Patients;
use App\Models\Room;
use App\Models\Bed;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class ObstetricsGynecologyController extends Controller
{
  /**
   * عرض جميع سجلات قسم النسائية والتوليد
   */
  public function index()
  {
    try {
      $user = $this->checkUserRole(['doctor', 'admin', 'department_head']);
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $records = ObstetricsGynecology::with(['patient', 'examinations', 'doctors'])->get();

      return response()->json([
        'success' => true,
        'data' => $records
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
   * إنشاء سجل جديد في قسم النسائية والتوليد
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
        'status' => 'required|in:prenatal,intrapartum,postpartum',
        'gestational_weeks' => 'nullable|integer|min:1|max:42',
        'delivery_type' => 'nullable|in:vaginal,cesarean,assisted',
        'delivery_date' => 'nullable|date',
        'room_id' => 'required|exists:rooms,id',
        'bed_id' => 'required|exists:beds,id',
        'discharge_date' => 'nullable|date|after_or_equal:delivery_date',
        'doctors' => 'required|array',
        'doctors.*' => 'exists:user_accounts,id',
      ], [
        'id_card.required' => 'رقم الهوية مطلوب',
        'id_card.exists' => 'رقم الهوية غير مسجل في النظام',
        'status.in' => 'حالة المريضة يجب أن تكون واحدة من: prenatal, intrapartum, postpartum',
        'gestational_weeks.min' => 'يجب أن لا يقل عدد أسابيع الحمل عن 1',
        'gestational_weeks.max' => 'يجب أن لا يزيد عدد أسابيع الحمل عن 42',
        'delivery_type.in' => 'نوع الولادة يجب أن يكون واحداً من: vaginal, cesarean, assisted',
        'room_id.required' => 'رقم الغرفة مطلوب',
        'room_id.exists' => 'الغرفة المحددة غير موجودة',
        'bed_id.required' => 'رقم السرير مطلوب',
        'bed_id.exists' => 'السرير المحدد غير موجود',
        'discharge_date.after_or_equal' => 'تاريخ الخروج يجب أن يكون بعد أو يساوي تاريخ الولادة',
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
          'message' => 'المريضة غير موجودة'
        ], 404);
      }

      // التحقق من أن المريضة أنثى
      if ($patient->gender !== 'female') {
        return response()->json([
          'success' => false,
          'message' => 'لا يمكن إضافة سجل نسائية وتوليد إلا للمرضى الإناث'
        ], 400);
      }

      // التحقق من عدم وجود المريضة بالفعل في قسم النسائية والتوليد بنفس الغرفة والسرير
      $existingRecord = ObstetricsGynecology::where('patient_id', $patient->id)
        ->whereNull("discharge_date")
        ->first();

      if ($existingRecord) {
        return response()->json([
          'success' => false,
          'message' => 'المريضة مسجلة بالفعل في قسم النسائية والتوليد'
        ], 409);
      }

      $data = $validator->validated();
      $data['patient_id'] = $patient->id;

      $record = ObstetricsGynecology::create($data);
      $bed->update(['status' => 'occupied']);
      $record->doctors()->attach($request->doctors);

      return response()->json([
        'success' => true,
        'message' => 'تم إنشاء سجل النسائية والتوليد بنجاح',
        'data' => $record->load('patient')
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

      $record = ObstetricsGynecology::with([
        'patient',
        'examinations',
        'doctors',
        'patient.analyses',
        'patient.radiologies',
        'patient.doctorReport.doctor',
        'bed',
        'room'
      ])->find($id);

      if (!$record) {
        return response()->json([
          'success' => false,
          'message' => 'سجل النسائية والتوليد غير موجود'
        ], 404);
      }

      return response()->json([
        'success' => true,
        'data' => $record
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
   * تحديث سجل قسم النسائية والتوليد
   */
  public function update(Request $request, $id)
  {
    try {
      $user = $this->checkUserRole(['admin', 'department_head']);
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $record = ObstetricsGynecology::find($id);

      if (!$record) {
        return response()->json([
          'success' => false,
          'message' => 'سجل النسائية والتوليد غير موجود'
        ], 404);
      }

      $validator = Validator::make($request->all(), [
        'status' => 'sometimes|in:prenatal,intrapartum,postpartum',
        'gestational_weeks' => 'nullable|integer|min:1|max:42',
        'delivery_type' => 'nullable|in:vaginal,cesarean,assisted',
        'delivery_date' => 'nullable|date',
        'room_id' => 'sometimes|required|exists:rooms,id',
        'bed_id' => 'sometimes|required|exists:beds,id',
        'discharge_date' => 'nullable|date|after_or_equal:delivery_date',
        'doctors' => 'sometimes|array',
        'doctors.*' => 'exists:user_accounts,id',
      ], [
        'status.in' => 'حالة المريضة يجب أن تكون واحدة من: prenatal, intrapartum, postpartum',
        'gestational_weeks.min' => 'يجب أن لا يقل عدد أسابيع الحمل عن 1',
        'gestational_weeks.max' => 'يجب أن لا يزيد عدد أسابيع الحمل عن 42',
        'delivery_type.in' => 'نوع الولادة يجب أن يكون واحداً من: vaginal, cesarean, assisted',
        'room_id.required' => 'رقم الغرفة مطلوب عند التحديث',
        'room_id.exists' => 'الغرفة المحددة غير موجودة في السجلات',
        'bed_id.required' => 'رقم السرير مطلوب عند التحديث',
        'bed_id.exists' => 'السرير المحدد غير موجود في السجلات',
        'discharge_date.after_or_equal' => 'تاريخ الخروج يجب أن يكون بعد أو يساوي تاريخ الولادة',
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
      if ($request->has('bed_id') && $request->bed_id != $record->bed_id) {
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
      if ($request->has('bed_id') && $request->bed_id != $record->bed_id) {
        $oldBed = Bed::find($record->bed_id);
      }

      $updateData = [
        'status' => $request->status ?? $record->status,
        'gestational_weeks' => $request->gestational_weeks ?? $record->gestational_weeks,
        'delivery_type' => $request->delivery_type ?? $record->delivery_type,
        'delivery_date' => $request->delivery_date ?? $record->delivery_date,
        'room_id' => $request->room_id ?? $record->room_id,
        'bed_id' => $request->bed_id ?? $record->bed_id,
        'discharge_date' => $request->discharge_date ?? $record->discharge_date,
      ];

      $record->update($updateData);

      // تحديث حالة الأسرة
      if ($oldBed) {
        $oldBed->update(['status' => 'available']);
        Bed::find($request->bed_id)->update(['status' => 'occupied']);
      }

      // مزامنة الأطباء المشرفين (إذا تم إرسالهم)
      if ($request->has('doctors')) {
        $record->doctors()->sync($request->doctors);
      }

      return response()->json([
        'success' => true,
        'message' => 'تم تحديث سجل النسائية والتوليد بنجاح',
        'data' => $record->load('patient')
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
   * حذف سجل قسم النسائية والتوليد
   */
  public function destroy($id)
  {
    try {
      $user = $this->checkUserRole(['admin']);
      if (!$user['success']) {
        return response()->json($user, 403);
      }

      $record = ObstetricsGynecology::find($id);

      if (!$record) {
        return response()->json([
          'success' => false,
          'message' => 'سجل النسائية والتوليد غير موجود'
        ], 404);
      }

      $record->delete();

      return response()->json([
        'success' => true,
        'message' => 'تم حذف سجل النسائية والتوليد بنجاح'
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
          'message' => 'سجل المريضة غير موجود'
        ], 404);
      }

      $record = ObstetricsGynecology::with('patient')
        ->where('patient_id', $patient->id)
        ->whereNull('discharge_date')
        ->latest()
        ->first();

      if (!$record) {
        return response()->json([
          'success' => false,
          'message' => 'لا يوجد سجل نشط لهذه المريضة في قسم النسائية والتوليد'
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
  private function checkUserRole($allowedRoles = ['doctor', 'nurse', 'admin', 'department_head'])
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
        $user->department->name !== "قسم النسائية"
      ) {
        return [
          'success' => false,
          'message' => 'غير مصرح بالوصول: يجب أن تكون في قسم النسائية والتوليد'
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