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
            'id_card' => 'required',  // تحقق أن "id_card" مطلوب وأنه فريد في جدول "patients".
            'specialties' => 'required|string',  // تحقق أن "specialties" مطلوب وأنه نص (string).
            'health_condition' => 'required|string',  // تحقق أن "health_condition" مطلوب وأنه نص (string).
            'room_number' => 'required|string',  // تحقق أن "room_number" مطلوب وأنه نص (string).
            'bed_number' => 'required|string',  // تحقق أن "bed_number" مطلوب وأنه نص (string).
            'blood_pressure' => 'nullable|string',  // تحقق أن "blood_pressure" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'blood_sugar' => 'nullable|string',  // تحقق أن "blood_sugar" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'temperature' => 'nullable|string',  // تحقق أن "temperature" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'blood_analysis' => 'nullable|string',  // تحقق أن "blood_analysis" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'urine_output' => 'nullable|string',  // تحقق أن "urine_output" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'doses' => 'nullable|string',  // تحقق أن "doses" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'oxygen_level' => 'nullable|string',  // تحقق أن "oxygen_level" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'doctor_report' => 'nullable|string',  // تحقق أن "doctor_report" غير مطلوب ولكنه يجب أن يكون نص (string) إذا تم تقديمه.
            'discharge_date' => 'nullable|date',  // تحقق أن "discharge_date" غير مطلوب ولكن إذا تم تقديمه، يجب أن يكون تاريخًا صحيحًا.
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
    
    
}





