<?php

namespace App\Http\Controllers\Api;

use App\Models\Patients;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Validator;

class PatientsController extends Controller
{

// post  
    public function store(Request $request)
    {
        // التحقق من اللغة من الـ Header
        $language = $request->header('Content-Language', 'en'); // القيمة الافتراضية "en"
    
        // التحقق من البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'id_card'       => 'required|unique:patients|string',
            'full_name'     => 'required|array', // التأكد من أن full_name هو مصفوفة لغات
            'id_number'     => 'required|string',
            'phone_number'  => 'required|string',
            'date_of_birth' => 'required|date',
            'medical_info'  => 'nullable|string',
            'blood_type'    => 'nullable|string|max:3',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // إنشاء المريض
        $patient = new Patients();
        $patient->id_card = $request->input('id_card');
    
        // تعيين full_name كـ JSON بناءً على اللغات المدخلة
        $fullName = $request->input('full_name'); // بيانات الـ full_name كـ JSON
        
        // تحويل full_name إلى JSON قبل حفظه
        $patient->full_name = json_encode($fullName); // تحويل المصفوفة إلى سلسلة بتنسيق JSON
    
        $patient->id_number = $request->input('id_number');
        $patient->phone_number = $request->input('phone_number');
        $patient->date_of_birth = $request->input('date_of_birth');
        $patient->medical_info = $request->input('medical_info') ? $request->input('medical_info') : null;
        $patient->blood_type = $request->input('blood_type');
        $patient->card_status = true;
    
        // حفظ البيانات
        $patient->save();
    
        // إرسال استجابة
        return response()->json([
            'message' => 'Patient added successfully',
            'data'    => $patient
        ], 201);
    }
// update all data
    public function update(Request $request, $id_card)
    {
        // البحث عن المريض باستخدام id_card
        $patient = Patients::where('id_card', $id_card)->first();
    
        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found'
            ], 404); // 404 تعني Not Found
        }
    
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'full_name'     => 'sometimes|array', // التأكد من أن full_name هو مصفوفة لغات
            'id_number'     => 'sometimes|string',
            'phone_number'  => 'sometimes|string',
            'date_of_birth' => 'sometimes|date',
            'medical_info'  => 'nullable|string',
            'blood_type'    => 'nullable|string|max:3',
            'card_status'   => 'sometimes|boolean',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        // إعداد البيانات للتحديث
        $dataToUpdate = $request->only([
            'id_number',
            'phone_number',
            'date_of_birth',
            'medical_info',
            'blood_type',
            'card_status'
        ]);
    
        // معالجة حقل full_name إذا كان موجودًا
        if ($request->has('full_name')) {
            $dataToUpdate['full_name'] = json_encode($request->input('full_name'));
        }
    
        // تحديث بيانات المريض
        $patient->update($dataToUpdate);
    
        // إرسال استجابة
        return response()->json([
            'message' => 'Patient updated successfully',
            'data'    => $patient
        ], 200);
    }
// update by just id card
public function updateById_card(Request $request)
{
    // البحث عن المريض باستخدام id_card إذا تم إرسال id_card
    $query = Patients::query();
    if ($request->has('id_number')) {
        // إذا كانت full_name موجودة، يتم البحث باستخدامها
        $id_number = $request->input('id_number');
        $query->where('id_number',  $id_number );
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
        'id_card'       => 'sometimes|string|unique:patients,id_card,' . $patient->id,
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
        'data'    => $patient
    ], 200);
}
    // get all patients or one patient by id card or full name
    //الحصول على جميع المرضى أو مريض واحد عن طريق بطاقة الهوية أو الاسم الكامل

    public function get(Request $request)
{
    // التحقق من اللغة من الـ Header
    if ($request->has('id_card')) {
        $query = Patients::query();
        $query->where('id_card', $request->input('id_card'));
         // استرجاع المرضى بناءً على المعايير
    $patients = $query->get();

    // التحقق إذا كانت هناك نتائج
    if ($patients->isEmpty()) {
        return response()->json([
            'message' => 'No patients found'
        ], 404);
    }

    // إرسال البيانات المسترجعة
    return response()->json([
        'message' => 'Patients retrieved successfully',
        'data'    => $patients
    ], 200);
    }
    if ($request->has('full_name')) {
        $query = Patients::query();
        $query->where('full_name', 'like', '%'. $request->input('full_name') .'%');

         // استرجاع المرضى بناءً على المعايير
    $patients = $query->get();

    // التحقق إذا كانت هناك نتائج
    if ($patients->isEmpty()) {
        return response()->json([
            'message' => 'No patients found'
        ], 404);
    }

    // إرسال البيانات المسترجعة
    return response()->json([
        'message' => 'Patients retrieved successfully',
        'data'    => $patients
    ], 200);
    }
    $languages = explode(',', $request->header('Content-Language', 'en')); // قراءة اللغات المدعومة
    $patients = Patients::all(); // استرجاع جميع المرضى

    // تعديل بيانات الـ full_name حسب اللغة المطلوبة
    $patientsData = $patients->map(function ($patient) use ($languages) {
        $fullName = json_decode($patient->full_name, true); // تحويل JSON إلى مصفوفة

        // إرجاع قيمة full_name لجميع اللغات المدخلة
        $patient->full_name = $fullName;
        
        return $patient;
    });

    return response()->json([
        'message' => 'Patients retrieved successfully',
        'data'    => $patientsData
    ], 200);
}

}
