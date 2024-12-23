<?php

namespace App\Http\Controllers\Api;

use App\Models\IntensiveCarePatient;
use App\Models\MeasurementAndDose;
use App\Http\Controllers\Controller;
use App\Models\Patients;
use Illuminate\Http\Request;
use Validator;
class IntensiveCarePatients extends Controller
{
    public function store(Request $request)
    {
        // التحقق من صحة البيانات المدخلة
        $validatedData = Validator::make($request->all(), [  // يقوم هذا السطر بالتحقق من صحة البيانات المدخلة من قبل المستخدم باستخدام قاعدة التحقق.
            "id_card" => "required|string",  // تحقق أن "id_card" مطلوب وأنه فريد في جدول "patients".
            "specialties" => "required|string",  // تحقق أن "specialties" مطلوب وأنه نص (string).
            "health_condition" => "required|string",  // تحقق أن "health_condition" مطلوب وأنه نص (string).
            "room_number" => "required|string",  // تحقق أن "room_number" مطلوب وأنه نص (string).
            "bed_number" => "required|string",  // تحقق أن "bed_number" مطلوب وأنه نص (string).
            "blood_pressure" => "nullable|string",  // تحقق أن "blood_pressure" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "blood_sugar" => "nullable|string",  // تحقق أن "blood_sugar" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "temperature" => "nullable|string",  // تحقق أن "temperature" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "blood_analysis" => "nullable|string",  // تحقق أن "blood_analysis" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "urine_output" => "nullable|string",  // تحقق أن "urine_output" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "doses" => "nullable|string",  // تحقق أن "doses" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "oxygen_level" => "nullable|string",  // تحقق أن "oxygen_level" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "doctor_report" => "nullable|string",  // تحقق أن "doctor_report" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            "discharge_date" => "nullable|date",  // تحقق أن "discharge_date" غير مطلوب ولكن إذا تم تقديمه، يجب أن يكون تاريخًا صحيحًا.
        ]);        
        if ($validatedData->fails()) {
            return response()->json(['errors' => $validatedData->errors()], 422);
        }

        // 1. إنشاء سجل في جدول المرضى
        $patient = Patients::where('id_card', $request->input('id_card'))->first();
        // التحقق إذا كانت هناك نتائج
        if (!$patient) {
            return response()->json([
                'message' => 'No patients found'
            ], 404);
        }
    

        // 2. إنشاء سجل في جدول القياسات والجرعات
        $measurementAndDose = MeasurementAndDose::create([
            'blood_pressure' => $request['blood_pressure'],
            'blood_sugar' => $request['blood_sugar'],
            'temperature' => $request['temperature'],
            'blood_analysis' => $request['blood_analysis'],
            'urine_output' => $request['urine_output'],
            'doses' => $request['doses'],
            'oxygen_level' => $request['oxygen_level'],
        ]);

        // 3. إنشاء سجل في جدول العناية المركزة وربطه مع السجلات الأخرى
        $intensiveCarePatient = IntensiveCarePatient::create([
            'id_patients' => $patient->id,
            'id_card' => $request['id_card'],
            'specialties' => $request['specialties'],
            'health_condition' => $request['health_condition'],
            'room_number' => $request['room_number'],
            'bed_number' => $request['bed_number'],
            'id_measurements_and_surgeries' => $measurementAndDose->id,
            'doctor_report' => $request['doctor_report'],
            'discharge_date' => $request['discharge_date'],
        ]);

        return response()->json([
            'message' => 'Patient and related records created successfully.',
            'data' => $intensiveCarePatient
        ], 201);
    }

    public function get(Request $request)
    {
        try {
            $intensiveCarePatients = IntensiveCarePatient::with(['Patients', 'MeasurementAndDose'])->get();
    
            return response()->json([
                'message' => 'Intensive care patients retrieved successfully',
                'data' => $intensiveCarePatients
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function getById(Request $request,$id)
    {
        try {
            $intensiveCarePatients = IntensiveCarePatient::with(['Patients', 'MeasurementAndDose'])->find($id);
            if (!$intensiveCarePatients) {
                return response()->json([
                    'message' => 'intensive Care Patient not found'
                ], 404);
            }
            return response()->json([
                'message' => 'Intensive care patients retrieved successfully',
                'data' => $intensiveCarePatients
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getActive(Request $request)
    {
        try {
            // استرجاع المرضى النشطين (الذين ليس لديهم تاريخ خروج)
            $intensiveCarePatients = IntensiveCarePatient::with(['Patients', 'MeasurementAndDose'])
                                                        ->whereNull('discharge_date')
                                                        ->get();
            // $intensiveCarePatients = IntensiveCarePatient::whereNull('discharge_date')->get();
            // التحقق مما إذا كانت النتيجة فارغة
            if ($intensiveCarePatients->isEmpty()) {
                return response()->json([
                    'message' => 'No active intensive care patients found'
                ], 404);
            }
    
            // إرجاع المرضى الذين لم يتم تفريغهم بنجاح
            return response()->json([
                'message' => 'Active intensive care patients retrieved successfully',
                'data' => $intensiveCarePatients
            ], 200);
        } catch (\Exception $e) {
            // إذا حدث استثناء في العملية
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    




    public function update(Request $request, $id)

    {
        // التحقق من البيانات المدخلة
        $validator = Validator::make($request->all(), [

            'specialties' => 'required|string',
            'health_condition' => 'required|string',
            'room_number' => 'required|string',
            'bed_number' => 'required|string',
            'blood_pressure' => 'nullable|string',
            'blood_sugar' => 'nullable|string',
            'temperature' => 'nullable|string',
            'blood_analysis' => 'nullable|string',
            'urine_output' => 'nullable|string',
            'doses' => 'nullable|string',
            'oxygen_level' => 'nullable|string',
            'doctor_report' => 'nullable|string',
            'discharge_date' => 'nullable|date',
        ]);

        // إذا كانت البيانات غير صالحة، أعد الاستجابة بخطأ
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);  // 422 يعني أن البيانات غير صالحة
        }

        // البحث عن المريض باستخدام id
        $intensiveCarePatient = IntensiveCarePatient::find($id);

        if (!$intensiveCarePatient) {
            return response()->json([
                'message' => 'Patient not found'
            ], 404);
        }

        // تحديث بيانات المريض
        $intensiveCarePatient->update($request->only([
            'id_card', 'specialties', 'health_condition', 'room_number', 'bed_number', 
            'blood_pressure', 'blood_sugar', 'temperature', 'blood_analysis', 'urine_output',
            'doses', 'oxygen_level', 'doctor_report', 'discharge_date'
        ]));

        // إرجاع الاستجابة بنجاح
        return response()->json([
            'message' => 'Patient updated successfully',
            'patient' => $intensiveCarePatient
        ], 200);
    } 

 

    public function updateMeasurementAndDose(Request $request, $id)
    {
        // التحقق من البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'blood_pressure' => 'nullable|string',
            'blood_sugar' => 'nullable|string',
            'temperature' => 'nullable|string',
            'blood_analysis' => 'nullable|string',
            'urine_output' => 'nullable|string',
            'doses' => 'nullable|string',
            'oxygen_level' => 'nullable|string',
        ]);
    
        // إذا كانت البيانات غير صالحة، أعد الاستجابة بخطأ
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }
    
        // البحث عن المريض باستخدام id
        $intensiveCarePatient = IntensiveCarePatient::find($id);
    
        if (!$intensiveCarePatient) {
            return response()->json([
                'message' => 'Patient not found',
            ], 404);
        }
    
        // البحث عن بيانات القياسات والجرعات
        $measurementAndDose = MeasurementAndDose::find($intensiveCarePatient->id_measurements_and_surgeries);
    
        if (!$measurementAndDose) {
            return response()->json([
                'message' => 'Measurements and Doses not found',
            ], 404);
        }
    
        // الحقول القابلة للتحديث
        $fields = ['blood_pressure', 'blood_sugar', 'temperature', 'blood_analysis', 'urine_output', 'doses', 'oxygen_level'];
    
        $updatedData = []; // لتخزين القيم الجديدة المحدثة
    
        foreach ($fields as $field) {
            if ($request->filled($field)) {
                // استرجاع القيم القديمة
                $existingData = $measurementAndDose->$field ?? '';
    
                // التحقق من أن القيم القديمة يمكن فك ترميزها
                $decodedData = [];
                if ($existingData && is_string($existingData)) {
                    $decodedData = json_decode($existingData, true);
    
                    // إذا لم يكن JSON صالحًا، يتم تعيينه كمصفوفة فارغة
                    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decodedData)) {
                        $decodedData = [];
                    }
                }
    
                // إذا كانت القيمة ليست JSON صالح، يتم تحويلها إلى مصفوفة فارغة
                if (!is_array($decodedData)) {
                    $decodedData = [];
                }
    
                // إضافة القيمة الجديدة مع التاريخ والوقت
                $decodedData[] = [
                    'value' => $request->$field,
                    'timestamp' => now()->toDateTimeString(),
                ];
    
                // تحديث البيانات
                $updatedData[$field] = json_encode($decodedData);
            }
        }
    
        // تحديث القيم في جدول القياسات والجرعات
        if (!empty($updatedData)) {
            $measurementAndDose->update($updatedData);
        }
    
        // إرجاع الاستجابة بنجاح
        return response()->json([
            'message' => 'Measurements and doses updated successfully',
            'data' => $measurementAndDose,
        ], 200);
    }
    
    public function updateDischargeDate(Request $request, $id)

    {
   
        // البحث عن المريض باستخدام id
        $intensiveCarePatient = IntensiveCarePatient::find($id);

        if (!$intensiveCarePatient) {
            return response()->json([
                'message' => 'Patient not found'
            ], 404);
        }

        // تحديث بيانات المريض
        $intensiveCarePatient->update(["discharge_date" => now()->toDateTimeString() ]);

        // إرجاع الاستجابة بنجاح
        return response()->json([
            'message' => 'Intensive Care Patient updated discharge date successfully',
            'patient' => $intensiveCarePatient
        ], 200);
    } 
    public function updateDoctorReport(Request $request, $id)
    {
   
        // البحث عن المريض باستخدام id
        $intensiveCarePatient = IntensiveCarePatient::find($id);

        if (!$intensiveCarePatient) {
            return response()->json([
                'message' => 'Intensive Care Patient not found'
            ], 404);
        }

            // استرجاع القيم القديمة
    $existingDoctorReport = $intensiveCarePatient->doctor_report ?? ''; // الحقل الخاص بالقياسات

    // إضافة القيمة الجديدة مع التاريخ والوقت
    $newDoctorReport = [
        'value' => $request->doctor_report,
        'timestamp' => now()->toDateTimeString(),
    ];

    // تحويل القيم إلى نص JSON وتحديث الحقل
    $updatedDoctorReport = $existingDoctorReport
        ? json_decode($existingDoctorReport, true) 
        : [];

    $updatedDoctorReport[] = $newDoctorReport;

    $intensiveCarePatient->update([
        'doctor_report' => json_encode($updatedDoctorReport),
    ]);

    return response()->json([
        'message' => 'Measurement added successfully',
        'intensiveCarePatient' => $intensiveCarePatient,
    ], 200);

    } 

}
    






