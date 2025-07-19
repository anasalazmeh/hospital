<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GynecologicalExamination;
use App\Models\ObstetricsGynecology;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class GynecologicalExaminationController extends Controller
{

  /**
   * Display a listing of gynecological examinations
   */
  public function index(Request $request)
  {
    try {
      // Authenticate user
      $token = JWTAuth::getToken();
      $currentUser = JWTAuth::authenticate($token);

      if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse', 'admin'])) {
        return response()->json([
          'success' => false,
          'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً أو ممرضاً أو مديراً',
          'data' => null
        ], 403);
      }

      // Build query with filters
      $query = GynecologicalExamination::with(['doctor', 'obstetricsRecord.patient'])
        ->orderBy('created_at', 'desc');

      // Apply filters if provided
      if ($request->has('examination_type')) {
        $query->where('examination_type', $request->examination_type);
      }

      if ($request->has('doctor_id')) {
        $query->where('doctor_id', $request->doctor_id);
      }

      if ($request->has('patient_id')) {
        $query->whereHas('obstetricsRecord', function ($q) use ($request) {
          $q->where('patient_id', $request->patient_id);
        });
      }

      if ($request->has('date_from')) {
        $query->whereDate('created_at', '>=', $request->date_from);
      }

      if ($request->has('date_to')) {
        $query->whereDate('created_at', '<=', $request->date_to);
      }

      // Get all results without pagination
      $examinations = $query->get();

      return response()->json([
        'success' => true,
        'message' => 'تم جلب سجلات الفحوصات بنجاح',
        'data' => $examinations
      ]);

    } catch (\Exception $e) {
      Log::error('Error fetching gynecological examinations: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب سجلات الفحوصات',
        'error' => env('APP_DEBUG') ? $e->getMessage() : null
      ], 500);
    }
  }

  /**
   * Display the specified gynecological examination
   */
  public function show($id)
  {
    try {
      // Authenticate user
      $token = JWTAuth::getToken();
      $currentUser = JWTAuth::authenticate($token);

      if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse', 'admin'])) {
        return response()->json([
          'success' => false,
          'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً أو ممرضاً أو مديراً',
          'data' => null
        ], 403);
      }

      // Find the examination with relationships
      $examination = GynecologicalExamination::with(['doctor', 'obstetricsRecord.patient'])
        ->find($id);

      if (!$examination) {
        return response()->json([
          'success' => false,
          'message' => 'سجل الفحص غير موجود',
          'data' => null
        ], 404);
      }

      return response()->json([
        'success' => true,
        'message' => 'تم جلب سجل الفحص بنجاح',
        'data' => $examination
      ]);

    } catch (\Exception $e) {
      Log::error('Error fetching gynecological examination: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب سجل الفحص',
        'error' => env('APP_DEBUG') ? $e->getMessage() : null
      ], 500);
    }
  }

  /**
   * Store a newly created examination record
   */
  public function store(Request $request)
  {
    try {
      // التحقق من توكن المستخدم وصحته
      $token = JWTAuth::getToken();
      if (!$token) {
        return response()->json([
          'success' => false,
          'message' => 'مطلوب تسجيل الدخول للوصول إلى هذه الخدمة',
          'data' => null
        ], 401);
      }

      $currentUser = JWTAuth::authenticate($token);
      if (!$currentUser) {
        return response()->json([
          'success' => false,
          'message' => 'جلسة الدخول منتهية، يرجى تسجيل الدخول مرة أخرى',
          'data' => null
        ], 401);
      }

      // التحقق من صلاحيات المستخدم
      if (!in_array($currentUser->role, ['doctor', 'nurse', 'admin'])) {
        return response()->json([
          'success' => false,
          'message' => 'غير مصرح لك بهذه العملية: يجب أن تكون طبيباً أو ممرضاً أو مديراً',
          'data' => null
        ], 403);
      }

      // التحقق من صحة البيانات المدخلة
      $validator = Validator::make($request->all(), [
        'obstetrics_gynecology_id' => 'required|exists:obstetrics_gynecology,id',
        'examination_type' => 'required|in:prenatal,postnatal',
        'pregnancy_week' => [
          'nullable',
          'integer',
          'min:1',
          'max:40',
          function ($attribute, $value, $fail) use ($request) {
            if ($request->examination_type === 'prenatal' && empty($value)) {
              $fail('حقل أسبوع الحمل مطلوب للفحوصات قبل الولادة');
            }
          }
        ],
        'fetal_heart_rate' => 'nullable|string|max:50',
        'uterine_contractions' => 'nullable|string|max:100',
        'cervical_dilation' => 'nullable|string|max:50',
        'temperature' => 'nullable|numeric|between:35,45',
        'blood_pressure' => 'nullable|string|regex:/^\d{1,3}\/\d{1,3}$/',
        'fetal_movement' => 'nullable|integer|min:0',
        'fundal_height' => 'nullable|string|max:50',
        'postpartum_bleeding' => [
          'nullable',
          'string',
          'max:100',
          function ($attribute, $value, $fail) use ($request) {
            if ($request->examination_type === 'postnatal' && empty($value)) {
              $fail('حقل نزيف ما بعد الولادة مطلوب للفحوصات بعد الولادة');
            }
          }
        ],
        'ultrasound' => 'nullable|string|max:255',
        'postpartum_monitoring' => 'nullable|string',
        'medication_doses' => 'nullable|string',
        'additional_procedures' => 'nullable|string',
        'notes' => 'nullable|string'
      ], [
        'obstetrics_gynecology_id.required' => 'معرف سجل التوليد مطلوب',
        'obstetrics_gynecology_id.exists' => 'سجل التوليد المحدد غير موجود',
        'examination_type.required' => 'نوع الفحص مطلوب',
        'examination_type.in' => 'نوع الفحص يجب أن يكون قبل الولادة أو بعد الولادة',
        'pregnancy_week.integer' => 'أسبوع الحمل يجب أن يكون رقماً صحيحاً',
        'pregnancy_week.min' => 'أسبوع الحمل يجب أن يكون على الأقل 1',
        'pregnancy_week.max' => 'أسبوع الحمل يجب أن لا يتجاوز 40',
        'blood_pressure.regex' => 'ضغط الدم يجب أن يكون بالصيغة الصحيحة (مثال: 120/80)',
        'temperature.between' => 'درجة الحرارة يجب أن تكون بين 35 و 45'
      ]);

      if ($validator->fails()) {
        $errorMessages = [];

        // تجميع جميع الأخطاء في سلسلة نصية واحدة
        foreach ($validator->errors()->all() as $error) {
          $errorMessages[] = $error;
        }

        $combinedErrorMessage = implode(' - ', $errorMessages);

        return response()->json([
          'success' => false,
          'message' => 'الأخطاء التالية: ' . $combinedErrorMessage,
          'errors' => $validator->errors()
        ], 422);
      }

      // التحقق من وجود سجل التوليد
      $obstetricsRecord = ObstetricsGynecology::find($request->obstetrics_gynecology_id);
      if (!$obstetricsRecord) {
        return response()->json([
          'success' => false,
          'message' => 'سجل التوليد المطلوب غير موجود',
          'data' => null
        ], 404);
      }

      // التحقق من تطابق حالة المريضة مع نوع الفحص
      if ($request->examination_type === 'prenatal' && $obstetricsRecord->status !== 'prenatal') {
        return response()->json([
          'success' => false,
          'message' => 'لا يمكن إضافة فحص قبل الولادة لمريضة ليست في حالة حمل',
          'data' => null
        ], 400);
      }

      if ($request->examination_type === 'postnatal' && $obstetricsRecord->status !== 'postpartum') {
        return response()->json([
          'success' => false,
          'message' => 'لا يمكن إضافة فحص بعد الولادة لمريضة ليست في حالة ما بعد الولادة',
          'data' => null
        ], 400);
      }

      // إنشاء سجل الفحص
      $examData = $validator->validated();
      $examData['doctor_id'] = $currentUser->id;

      $examination = GynecologicalExamination::create($examData);

      return response()->json([
        'success' => true,
        'message' => 'تم إنشاء سجل الفحص بنجاح',
        'data' => $examination
      ], 201);

    } catch (\Exception $e) {
      Log::error('خطأ في إنشاء سجل فحص نسائي: ' . $e->getMessage());

      return response()->json([
        'success' => false,
        'message' => 'حدث خطأ غير متوقع أثناء محاولة إنشاء السجل',
        'error' => env('APP_DEBUG') ? $e->getMessage() : null
      ], 500);
    }
  }

  /**
   * Update an existing examination record
   */
  public function update(Request $request, $id)
  {
    // Authenticate and authorize user
    $token = JWTAuth::getToken();
    $currentUser = JWTAuth::authenticate($token);

    if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse', 'admin'])) {
      return response()->json([
        'success' => false,
        'message' => 'Unauthorized: Only doctors, nurses, or admins can update records',
        'data' => null
      ], 403);
    }

    // Find the examination record
    $examination = GynecologicalExamination::find($id);

    if (!$examination) {
      return response()->json([
        'success' => false,
        'message' => 'Examination record not found',
        'data' => null
      ], 404);
    }
    // التحقق من أن المستخدم هو نفس الطبيب الذي أنشأ السجل أو مدير
    if ($examination->doctor_id !== $currentUser->id) {
      return response()->json([
        'success' => false,
        'message' => 'غير مصرح لك بتعديل هذا السجل: فقط الطبيب الذي أنشأه أو المدير يمكنهم التعديل',
        'data' => null
      ], 403);
    }
    // Validate request data
    $validator = Validator::make($request->all(), [
      'examination_type' => 'sometimes|in:prenatal,postnatal',
      'pregnancy_week' => [
        'nullable',
        'integer',
        'min:1',
        'max:40',
        function ($attribute, $value, $fail) use ($request, $examination) {
          $type = $request->examination_type ?? $examination->examination_type;
          if ($type === 'prenatal' && empty($value)) {
            $fail('Pregnancy week is required for prenatal examinations');
          }
        }
      ],
      'fetal_heart_rate' => 'nullable|string|max:50',
      'uterine_contractions' => 'nullable|string|max:100',
      'cervical_dilation' => 'nullable|string|max:50',
      'temperature' => 'nullable|numeric|between:35,45',
      'blood_pressure' => 'nullable|string|max:20',
      'fetal_movement' => 'nullable|integer|min:0',
      'fundal_height' => 'nullable|string|max:50',
      'postpartum_bleeding' => [
        'nullable',
        'string',
        'max:100',
        function ($attribute, $value, $fail) use ($request, $examination) {
          $type = $request->examination_type ?? $examination->examination_type;
          if ($type === 'postnatal' && empty($value)) {
            $fail('Postpartum bleeding information is required for postnatal examinations');
          }
        }
      ],
      'ultrasound' => 'nullable|string|max:255',
      'postpartum_monitoring' => 'nullable|string',
      'medication_doses' => 'nullable|string',
      'additional_procedures' => 'nullable|string',
      'notes' => 'nullable|string'
    ]);

    if ($validator->fails()) {
      return response()->json([
        'success' => false,
        'message' => 'Validation errors',
        'errors' => $validator->errors()
      ], 422);
    }

    // Update the examination record
    $examination->update($validator->validated());

    return response()->json([
      'success' => true,
      'message' => 'Examination record updated successfully',
      'data' => $examination
    ]);
  }
}