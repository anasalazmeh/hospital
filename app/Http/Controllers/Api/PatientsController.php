<?php

namespace App\Http\Controllers\Api;

use App\Models\Patients;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Validator;

class PatientsController extends Controller
{
    // Get all patients
    public function index()
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);
        // التحقق من صلاحية المستخدم
        if (!$currentUser || !in_array($currentUser->role, ['admin', 'admission_head'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً للوصول إلى هذه البيانات',
                'data' => null
            ], 403);
        }
        $patients = Patients::all();
        return response()->json(
            [
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $patients,
            ]
        );
    }

    // Get a single patient
    public function show($id)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من صلاحية المستخدم
        if (!$currentUser || !in_array($currentUser->role, ['admin', 'admission_head', 'doctor'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً للوصول إلى هذه البيانات',
                'data' => null
            ], 403);
        }

        $patient = Patients::with([
            'analyses' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'radiologies' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'doctorReport' => function ($query) {
                $query->orderBy('created_at', 'desc')->with(['doctor']);
            },
            'prescriptions' => function ($query) {
                $query->orderBy('created_at', 'desc')->with(['doctor']);
            }
        ])->find($id);
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found'
            ], 404);
        }

        // تحويل chronic_diseases من JSON string إلى array إذا كانت موجودة
        if ($patient->chronic_diseases) {
            try {
                $patient->chronic_diseases = json_decode($patient->chronic_diseases, true);
            } catch (\Exception $e) {
                // إذا فشل التحويل نتركها كما هي
                $patient->chronic_diseases = $patient->chronic_diseases;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات المستخدم بنجاح',
            'data' => $patient
        ]);
    }
    public function showByIdCard($id_card)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من صلاحية المستخدم بشكل أكثر وضوحاً
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: يجب أن تكون عامل تسجيل الدخول',
                'data' => null
            ], 403);
        }
        // تصحيح الاستعلام: استخدام where بدلاً من query
        $patient = Patients::where('id_card', $id_card)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found',
                'data' => null
            ], 404);
        }
        // تحسين معالجة chronic_diseases
        if (!empty($patient->chronic_diseases)) {
            try {
                $decodedDiseases = json_decode($patient->chronic_diseases, true, 512, JSON_THROW_ON_ERROR);
                $patient->chronic_diseases = is_array($decodedDiseases) ? $decodedDiseases : $patient->chronic_diseases;
            } catch (\JsonException $e) {
                // إذا فشل التحويل، نترك القيمة كما هي
                $patient->chronic_diseases = $patient->chronic_diseases;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'تم جلب بيانات المستخدم بنجاح',
            'data' => $patient
        ]);
    }
    // post  
    public function store(Request $request)
    {
        // التحقق من المصادقة والصلاحيات
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if (!$currentUser || !in_array($currentUser->role, ['admin', 'admission_head'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: مصادقة غير صالحة',
                'data' => null
            ], 401);
        }

        // قائمة الحقول المسموح بها فقط
        $allowedFields = [
            'id_card',
            'full_name',
            'id_number',
            'phone',
            'date_of_birth',
            'blood_type',
            'gender',
            'marital_status',
            'chronic_diseases',
            'allergies',
            'current_medication',
            'emergency_contact_phone',
            'emergency_contact_name',
            'emergency_contact_relation'
        ];

        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'id_card' => 'required|string|unique:patients',
            'full_name' => 'required|string|max:255',
            'id_number' => 'required|string|max:50',
            'phone' => 'required|string|max:20',
            'date_of_birth' => 'required|date',
            'blood_type' => 'nullable|in:A+,A-,AB+,AB-,O+,O-',
            'gender' => 'required|in:male,female',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'chronic_diseases' => 'nullable|array',
            'allergies' => 'nullable|string|max:255',
            'current_medication' => 'nullable|string',
            'emergency_contact_phone' => 'required|string|max:20',
            'emergency_contact_name' => 'required|string|max:100',
            'emergency_contact_relation' => 'required|in:father,mother,brother,sister,son,daughter,husband,wife,uncle,aunt,cousin,grandfather,grandmother,other'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 400);
        }

        // أخذ فقط الحقول المسموح بها
        $data = $request->only($allowedFields);

        // معالجة تاريخ الميلاد
        if (isset($data['date_of_birth'])) {
            $data['date_of_birth'] = Carbon::parse($data['date_of_birth'])->format('Y-m-d');
        }

        // تحويل chronic_diseases من array إلى JSON string إذا كانت موجودة
        if (isset($data['chronic_diseases']) && is_array($data['chronic_diseases'])) {
            $data['chronic_diseases'] = json_encode($data['chronic_diseases']);
        }

        // إنشاء المريض
        try {
            $patient = Patients::create($data);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء المريض بنجاح',
                'data' => $patient
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء المريض',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function createAnonymousPatient(Request $request)
    {
        try {
            // Validate the request data
            $validator = Validator::make($request->all(), [
                'id_card' => 'required|string|unique:patients,id_card',
                'gender' => 'required|string|in:male,female', // assuming these are your allowed values
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422); // 422 is more appropriate for validation errors
            }

            // Create anonymous patient data
            $anonymousData = [
                'id_card' => $request->id_card,
                'full_name' => 'مجهول الهوية', // Anonymous name
                'gender' => $request->gender,
                // Consider adding other default fields if needed
            ];

            // Create the patient record
            $patient = Patients::create($anonymousData);

            return response()->json([
                'success' => true,
                'message' => 'Anonymous patient created successfully',
                'data' => $patient
            ], 201);

        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Error creating anonymous patient: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the anonymous patient',
                'error' => env('APP_DEBUG') ? $e->getMessage() : 'Server error' // Only show details in debug mode
            ], 500);
        }
    }
    // Update a patient
    public function update(Request $request, $id)
    {
        // التحقق من المصادقة والصلاحيات
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if (!$currentUser || !in_array($currentUser->role, ['admin', 'admission_head'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: مصادقة غير صالحة',
                'data' => null
            ], 401);
        }

        // البحث عن المريض
        $patient = Patients::find($id);
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient not found',
                'data' => null
            ], 404);
        }

        // قائمة الحقول المسموح بها فقط
        $allowedFields = [
            'id_card',
            'full_name',
            'id_number',
            'phone',
            'date_of_birth',
            'blood_type',
            'status',
            'gender',
            'marital_status',
            'chronic_diseases',
            'allergies',
            'current_medication',
            'emergency_contact_phone',
            'emergency_contact_name',
            'emergency_contact_relation'
        ];

        // التحقق من صحة البيانات
        $validator = Validator::make($request->all(), [
            'id_card' => 'string|unique:patients,id_card,' . $id,
            'full_name' => 'nullable|string|max:255',
            'id_number' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'blood_type' => 'nullable|in:A+,A-,AB+,AB-,O+,O-',
            'status' => 'nullable|boolean',
            'gender' => 'in:male,female',
            'marital_status' => 'nullable|in:single,married,divorced,widowed',
            'chronic_diseases' => 'nullable|array',
            'allergies' => 'nullable|string|max:255',
            'current_medication' => 'nullable|string',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_relation' => 'nullable|in:father,mother,brother,sister,son,daughter,husband,wife,uncle,aunt,cousin,grandfather,grandmother,other'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في التحقق من البيانات',
                'errors' => $validator->errors()
            ], 400);
        }

        // أخذ فقط الحقول المسموح بها
        $data = $request->only($allowedFields);

        // معالجة تاريخ الميلاد
        if (isset($data['date_of_birth'])) {
            $data['date_of_birth'] = Carbon::parse($data['date_of_birth'])->format('Y-m-d');
        }

        // تحويل chronic_diseases من array إلى JSON string إذا كانت موجودة
        if (isset($data['chronic_diseases']) && is_array($data['chronic_diseases'])) {
            $data['chronic_diseases'] = json_encode($data['chronic_diseases']);
        }

        // تحديث بيانات المريض
        try {
            $patient->update($data);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات المريض بنجاح',
                'data' => $patient
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث بيانات المريض',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // update by just id card
    public function updateById_card(Request $request)
    {
        // البحث عن المريض باستخدام id_card إذا تم إرسال id_card
        $query = Patients::query();
        if ($request->has('id_number')) {
            // إذا كانت full_name موجودة، يتم البحث باستخدامها
            $id_number = $request->input('id_number');
            $query->where('id_number', $id_number);
        }
        if ($request->has('full_name')) {
            // إذا كانت full_name موجودة، يتم البحث باستخدامها
            $fullName = $request->input('full_name');
            $query->where('full_name', 'like', '%' . $fullName . '%');
        }

        // تنفيذ الاستعلام
        $patient = $query->first();
        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found'
            ], 404);  // 404 تعني Not Found
        }

        // التحقق من البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'id_card' => 'sometimes|string|unique:patients,id_card,' . $patient->id,
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // إذا تم إرسال full_name، سيتم تعديل الـ id_card فقط
        if ($request->has('id_card')) {
            $patient->id_card = $request->input('id_card');
        }

        // حفظ التعديلات
        $patient->save();

        // إرسال استجابة
        return response()->json([
            'message' => 'Patient updated successfully',
            'data' => $patient
        ], 200);
    }
    // public function destroy($id)
    // {
    //     $token = JWTAuth::getToken();
    //     $currentUser = JWTAuth::authenticate($token);
    //     // التحقق من صلاحية المستخدم
    //     if (!$currentUser || $currentUser->role !== 'admin' || $currentUser->role !== 'admission_head') {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً للوصول إلى هذه البيانات',
    //             'data' => null
    //         ], 403);
    //     }
    //     // البحث عن المريض
    //     $patient = Patients::find($id);

    //     // إذا لم يتم العثور على المريض
    //     if (!$patient) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Patient not found'
    //         ], 404);
    //     }
    //     if ($patient->intensiveCarePatient()->count() > 0) { // إذا كان هناك علاقة مع جدول الأطباء
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'لا يمكن الحذف بسبب وجود مرضه مرتبطين بهذه حساب'
    //         ], 422);
    //     }
    //     // حذف المريض
    //     $patient->delete();

    //     // رسالة نجاح
    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Patient deleted successfully'
    //     ]);
    // }
}

