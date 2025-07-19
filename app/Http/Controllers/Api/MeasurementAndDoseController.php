<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MeasurementAndDose;
use App\Models\IntensiveCarePatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class MeasurementAndDoseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // التحقق من المستخدم المسجل دخوله
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);
            if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }

            // بناء الاستعلام الأساسي
            $query = MeasurementAndDose::query()->with(['intensiveCarePatient.bed','intensiveCarePatient.patient']);
            if ($currentUser->role == 'nurse') {
                $query->where('user_account_id', $currentUser->id);
            }

            // التصنيف (الافتراضي: الأحدث أولاً)
            $sortField = $request->get('sort_by', 'created_at');
            $sortDirection = $request->get('sort_dir', 'desc');
            $query->orderBy($sortField, $sortDirection);

            // جلب جميع البيانات بدون تقسيم إلى صفحات
            $measurements = $query->get();

            // تحسين هيكل الاستجابة
            $responseData = [
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $measurements,
            ];

            return response()->json($responseData, 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // التحقق من المستخدم المسجل دخوله
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من صلاحية المستخدم
        if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: يجب أن تكون طبيباً أو ممرضاً للوصول إلى هذه البيانات',
                'data' => null
            ], 403);
        }

        // قواعد التحقق مع رسائل مخصصة
        $validationRules = [
            'id_card' => 'required|string|max:255',
            'temperature' => 'nullable|numeric|between:30,45',
            'blood_pressure' => 'nullable|string|regex:/^\d{1,3}\/\d{1,3}(?:\s?mmHg)?$/',
            'oxygen_level' => 'nullable|integer|between:70,100',
            'blood_sugar' => 'nullable|numeric|min:0|max:500',
            'heart_rate' => 'nullable|integer|between:30,200',
            'respiratory_rate' => 'nullable|integer|between:10,60',
            'urine_output' => 'nullable|numeric|min:0',
            'cvp' => 'nullable|numeric|between:0,20',
            'doses' => 'nullable|string',
            'serone' => 'nullable|string',
            'echocardiography_results' => 'nullable|string',
            'echo_findings_results' => 'nullable|string',
            'requires_dialysis' => 'nullable|boolean',
            'additional_procedures' => 'nullable|string',
        ];

        $customMessages = [
            'id_card.required' => 'رقم البطاقة مطلوب',
            'temperature.numeric' => 'درجة الحرارة يجب أن تكون رقماً',
            'temperature.between' => 'درجة الحرارة يجب أن تكون بين 30 و 45 درجة',
            'blood_pressure.regex' => 'صيغة ضغط الدم غير صحيحة. استخدم الصيغة 120/80 أو 120/80 mmHg',
            'oxygen_level.integer' => 'نسبة الأكسجين يجب أن تكون رقماً صحيحاً',
            'oxygen_level.between' => 'نسبة الأكسجين يجب أن تكون بين 70% و 100%',
            'blood_sugar.numeric' => 'نسبة السكر يجب أن تكون رقماً',
            'blood_sugar.min' => 'نسبة السكر لا يمكن أن تكون أقل من 0',
            'blood_sugar.max' => 'نسبة السكر لا يمكن أن تتجاوز 500',
            'heart_rate.integer' => 'معدل ضربات القلب يجب أن يكون رقماً صحيحاً',
            'heart_rate.between' => 'معدل ضربات القلب يجب أن يكون بين 30 و 200',
            'respiratory_rate.integer' => 'معدل التنفس يجب أن يكون رقماً صحيحاً',
            'respiratory_rate.between' => 'معدل التنفس يجب أن يكون بين 10 و 60',
            'urine_output.numeric' => 'إخراج البول يجب أن يكون رقماً',
            'urine_output.min' => 'إخراج البول لا يمكن أن يكون أقل من 0',
            'cvp.numeric' => 'الضغط الوريدي المركزي يجب أن يكون رقماً',
            'cvp.between' => 'الضغط الوريدي المركزي يجب أن يكون بين 0 و 20',
            'requires_dialysis.boolean' => 'حقل غسيل الكلى يجب أن يكون نعم أو لا',
        ];

        $validator = Validator::make($request->all(), $validationRules, $customMessages);

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

        // البحث عن المريض في العناية المشددة
        $icup = IntensiveCarePatient::where('id_card', $request->id_card)
            ->whereNull('discharge_date')
            ->orderByDesc('created_at')
            ->first();

        if (!$icup) {
            return response()->json([
                'success' => false,
                'message' => 'المريض غير موجود في العناية المشددة أو تم خروجه'
            ], 404);
        }

        try {
            // إنشاء القياس مع إضافة user_id تلقائياً
            $measurementData = $request->all();
            $measurementData['user_account_id'] = $currentUser->id;
            $measurementData['icup_id'] = $icup->id;

            $measurement = MeasurementAndDose::create($measurementData);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل القياسات بنجاح',
                'data' => $measurement
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء محاولة حفظ البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            // التحقق من المستخدم المسجل دخوله
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse', 'admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }

            // البحث عن السجل المطلوب
            $measurement = MeasurementAndDose::find($id);

            // إذا كان المستخدم ممرض، نتحقق أنه صاحب السجل
            if ($currentUser->role == 'nurse' && $measurement->user_account_id != $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول لهذا السجل',
                    'data' => null
                ], 403);
            }

            // إذا لم يتم العثور على السجل
            if (!$measurement) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم العثور على السجل المطلوب',
                    'data' => null
                ], 404);
            }

            // إرجاع البيانات بنجاح
            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $measurement
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        // التحقق من المستخدم المسجل دخوله
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        if (!$currentUser || !in_array($currentUser->role, ['doctor', 'nurse'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول',
                'data' => null
            ], 403);
        }

        // البحث عن القياس
        $measurement = MeasurementAndDose::with(['intensiveCarePatient'])->find($id);

        if (!$measurement) {
            return response()->json([
                'success' => false,
                'message' => 'القياس غير موجود'
            ], 404);
        }
        // تحقق من تاريخ خروج المريض
        if (!is_null($measurement->intensive_care_patient->discharge_date)) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل قياسات مريض تم خروجه من العناية المشددة'
            ], 403);
        }
        // التحقق من أن المستخدم هو من أنشأ القياس
        if ($measurement->user_account_id != $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح لك بتعديل هذا القياس'
            ], 403);
        }

        // التحقق من أن الوقت لم يتجاوز 10 دقائق
        $creationTime = $measurement->created_at;
        $currentTime = now();
        $diffInMinutes = $currentTime->diffInMinutes($creationTime);

        if ($diffInMinutes > 15) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن التعديل بعد مرور أكثر من 15 دقائق على الإنشاء'
            ], 403);
        }

        // التحقق من البيانات
        // قواعد التحقق مع رسائل مخصصة
        $validationRules = [
            'temperature' => 'nullable|numeric|between:30,45',
            'blood_pressure' => 'nullable|string|regex:/^\d{1,3}\/\d{1,3}(?:\s?mmHg)?$/',
            'oxygen_level' => 'nullable|integer|between:70,100',
            'blood_sugar' => 'nullable|numeric|min:0|max:500',
            'heart_rate' => 'nullable|integer|between:30,200',
            'respiratory_rate' => 'nullable|integer|between:10,60',
            'urine_output' => 'nullable|numeric|min:0',
            'cvp' => 'nullable|numeric|between:0,20',
            'doses' => 'nullable|string',
            'serone' => 'nullable|string',
            'echocardiography_results' => 'nullable|string',
            'echo_findings_results' => 'nullable|string',
            'requires_dialysis' => 'nullable|boolean',
            'additional_procedures' => 'nullable|string',
        ];

        $customMessages = [
            'id_card.required' => 'رقم البطاقة مطلوب',
            'temperature.numeric' => 'درجة الحرارة يجب أن تكون رقماً',
            'temperature.between' => 'درجة الحرارة يجب أن تكون بين 30 و 45 درجة',
            'blood_pressure.regex' => 'صيغة ضغط الدم غير صحيحة. استخدم الصيغة 120/80 أو 120/80 mmHg',
            'oxygen_level.integer' => 'نسبة الأكسجين يجب أن تكون رقماً صحيحاً',
            'oxygen_level.between' => 'نسبة الأكسجين يجب أن تكون بين 70% و 100%',
            'blood_sugar.numeric' => 'نسبة السكر يجب أن تكون رقماً',
            'blood_sugar.min' => 'نسبة السكر لا يمكن أن تكون أقل من 0',
            'blood_sugar.max' => 'نسبة السكر لا يمكن أن تتجاوز 500',
            'heart_rate.integer' => 'معدل ضربات القلب يجب أن يكون رقماً صحيحاً',
            'heart_rate.between' => 'معدل ضربات القلب يجب أن يكون بين 30 و 200',
            'respiratory_rate.integer' => 'معدل التنفس يجب أن يكون رقماً صحيحاً',
            'respiratory_rate.between' => 'معدل التنفس يجب أن يكون بين 10 و 60',
            'urine_output.numeric' => 'إخراج البول يجب أن يكون رقماً',
            'urine_output.min' => 'إخراج البول لا يمكن أن يكون أقل من 0',
            'cvp.numeric' => 'الضغط الوريدي المركزي يجب أن يكون رقماً',
            'cvp.between' => 'الضغط الوريدي المركزي يجب أن يكون بين 0 و 20',
            'requires_dialysis.boolean' => 'حقل غسيل الكلى يجب أن يكون نعم أو لا',
        ];

        $validator = Validator::make($request->all(), $validationRules, $customMessages);

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

        try {
            // تحديث البيانات
            $measurement->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث القياس بنجاح',
                'data' => $measurement
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في تحديث القياس',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
