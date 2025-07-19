<?php

namespace App\Http\Controllers\Api;

use App\Models\IntensiveCarePatient;
use App\Models\MeasurementAndDose;
use App\Http\Controllers\Controller;
use App\Models\Patients;
use App\Models\Room;
use App\Models\Bed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule; // <-- أضف هذا السطر

class IntensiveCarePatients extends Controller
{
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'id_card' => 'required|string|max:255|exists:patients,id_card',
                'health_condition' => 'required|string|max:255',
                'room_id' => 'required|exists:rooms,id',
                'bed_id' => 'required|exists:beds,id',
                'discharge_date' => 'nullable|date|after_or_equal:today',
                'admission_reason' => 'required|string',
                'attending_doctor_id' => 'nullable|exists:doctors,id',
                'severity_level' => 'required|in:critical,serious,stable',
                'medical_notes' => 'nullable|string',
                'ventilator_dependency' => 'sometimes|boolean',
                'isolation_required' => 'sometimes|boolean',
                'specialties' => 'nullable|array',
                'specialties.*' => 'exists:specialties,id',
                'doctors' => 'nullable|array',
                'doctors.*' => 'exists:user_accounts,id'
            ], [
                'id_card.exists' => 'رقم الهوية غير مسجل في قاعدة البيانات',
                'room_id.exists' => 'الغرفة غير موجودة',
                'bed_id.exists' => 'السرير غير موجود',
                'attending_doctor_id.exists' => 'الطبيب المحدد غير موجود في النظام'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 422);
            }

            // التحقق من أن السرير موجود في الغرفة المحددة
            $bed = Bed::where('id', $request->bed_id)->with('room')
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

            // البحث عن المريض
            $patient = Patients::where('id_card', $request->id_card)->first();
            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود'
                ], 404);
            }

            // التحقق من عدم وجود المريض في العناية المشددة بالفعل
            $existing = IntensiveCarePatient::where('id_patients', $patient->id)
                ->whereNull('discharge_date')
                ->exists();

            if ($existing) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض موجود في العناية المشددة بالفعل ولم يتم خروجه بعد'
                ], 400);
            }

            // إنشاء السجل
            $icuPatient = IntensiveCarePatient::create([
                'id_patients' => $patient->id,
                'id_card' => $request->id_card,
                'health_condition' => $request->health_condition,
                'room_id' => $request->room_id,
                'bed_id' => $request->bed_id,
                'discharge_date' => $request->discharge_date,
                'admission_reason' => $request->admission_reason,
                'attending_doctor_id' => $request->attending_doctor_id,
                'severity_level' => $request->severity_level,
                'medical_notes' => $request->medical_notes,
                'ventilator_dependency' => $request->boolean('ventilator_dependency'),
                'isolation_required' => $request->boolean('isolation_required'),
            ]);

            // تحديث حالة السرير
            $bed->update(['status' => 'occupied']);
            // تحديث حالة الغرفة إذا لزم الأمر
            // $occupiedBeds = IntensiveCarePatient::where('room_id', $request->room_id)
            //     ->whereNull('discharge_date')
            //     ->count();

            // $totalBedsInRoom = Bed::where('room_id', $request->room_id)->count();

            // if ($occupiedBeds >= $totalBedsInRoom) {
            //     $room->update(['status' => 'مشغولة']);
            // }

            // ربط التخصصات والأطباء إذا وجدت
            if (!empty($request->specialties)) {
                $icuPatient->specialties()->sync($request->specialties);
            }

            if (!empty($request->doctors)) {
                $icuPatient->user_accounts()->sync($request->doctors);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء سجل العناية المشددة بنجاح',
                'data' => $icuPatient->load(['patient', 'user_accounts', 'specialties', 'room', 'bed'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            // البحث عن سجل العناية المشددة
            $icuPatient = IntensiveCarePatient::find($id);

            if (!$icuPatient) {
                return response()->json([
                    'success' => false,
                    'message' => 'سجل العناية المشددة غير موجود'
                ], 404);
            }

            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'health_condition' => 'sometimes|required|string|max:255',
                'room_id' => 'sometimes|required|exists:rooms,id',
                'bed_id' => 'sometimes|required|exists:beds,id',
                'discharge_date' => 'nullable|date|after_or_equal:today',
                'admission_reason' => 'sometimes|required|string',
                'attending_doctor_id' => 'nullable|exists:doctors,id',
                'severity_level' => 'sometimes|required|in:critical,serious,stable',
                'medical_notes' => 'nullable|string',
                'ventilator_dependency' => 'sometimes|boolean',
                'isolation_required' => 'sometimes|boolean',
                'specialties' => 'nullable|array',
                'specialties.*' => 'exists:specialties,id',
                'doctors' => 'nullable|array',
                'doctors.*' => 'exists:user_accounts,id'
            ], [
                'attending_doctor_id.exists' => 'الطبيب المحدد غير موجود في النظام',
                'room_id.exists' => 'الغرفة غير موجودة',
                'bed_id.exists' => 'السرير غير موجود'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
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
            if ($request->has('bed_id') && $request->bed_id != $icuPatient->bed_id) {
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
            if ($request->has('bed_id') && $request->bed_id != $icuPatient->bed_id) {
                $oldBed = Bed::find($icuPatient->bed_id);
            }

            // تحديث البيانات الأساسية
            $updateData = [
                'health_condition' => $request->health_condition ?? $icuPatient->health_condition,
                'room_id' => $request->room_id ?? $icuPatient->room_id,
                'bed_id' => $request->bed_id ?? $icuPatient->bed_id,
                'discharge_date' => $request->discharge_date ?? $icuPatient->discharge_date,
                'admission_reason' => $request->admission_reason ?? $icuPatient->admission_reason,
                'attending_doctor_id' => $request->attending_doctor_id ?? $icuPatient->attending_doctor_id,
                'severity_level' => $request->severity_level ?? $icuPatient->severity_level,
                'medical_notes' => $request->medical_notes ?? $icuPatient->medical_notes,
                'ventilator_dependency' => $request->has('ventilator_dependency')
                    ? $request->boolean('ventilator_dependency')
                    : $icuPatient->ventilator_dependency,
                'isolation_required' => $request->has('isolation_required')
                    ? $request->boolean('isolation_required')
                    : $icuPatient->isolation_required,
            ];

            $icuPatient->update($updateData);

            // تحديث حالة الأسرة
            if ($oldBed) {
                $oldBed->update(['status' => 'available']);
                Bed::find($request->bed_id)->update(['status' => 'occupied']);
            }

            // تحديث العلاقات
            if ($request->has('specialties')) {
                $icuPatient->specialties()->sync($request->specialties);
            }

            if ($request->has('doctors')) {
                $icuPatient->user_accounts()->sync($request->doctors);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث سجل العناية المشددة بنجاح',
                'data' => $icuPatient->load(['patient', 'user_accounts', 'specialties', 'room', 'bed'])
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }
    public function get(Request $request)
    {
        try {
            $intensiveCarePatients = IntensiveCarePatient::with(['patient', 'user_accounts', 'specialties', 'analyses', 'radiologies','room','bed'])->get();
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
    public function getById(Request $request, $id)
    {
        try {
            $intensiveCarePatients = IntensiveCarePatient::with(['patient', 'specialties', 'user_accounts', 'analyses', 'radiologies', 'doctorReport.doctor', 'measurementAndDose','room','bed'])->find($id);
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
    // public function update(Request $request, $id)
    // {
    //     // التحقق من البيانات المدخلة
    //     $validator = Validator::make($request->all(), [

    //         'specialties' => 'required|string',
    //         'health_condition' => 'required|string',
    //         'room_number' => 'required|string',
    //         'bed_number' => 'required|string',
    //         'blood_pressure' => 'nullable|string',
    //         'blood_sugar' => 'nullable|string',
    //         'temperature' => 'nullable|string',
    //         'blood_analysis' => 'nullable|string',
    //         'urine_output' => 'nullable|string',
    //         'doses' => 'nullable|string',
    //         'oxygen_level' => 'nullable|string',
    //         'doctor_report' => 'nullable|string',
    //         'discharge_date' => 'nullable|date',
    //     ]);

    //     // إذا كانت البيانات غير صالحة، أعد الاستجابة بخطأ
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'errors' => $validator->errors()
    //         ], 422);  // 422 يعني أن البيانات غير صالحة
    //     }

    //     // البحث عن المريض باستخدام id
    //     $intensiveCarePatient = IntensiveCarePatient::find($id);

    //     if (!$intensiveCarePatient) {
    //         return response()->json([
    //             'message' => 'Patient not found'
    //         ], 404);
    //     }

    //     // تحديث بيانات المريض
    //     $intensiveCarePatient->update($request->only([
    //         'id_card',
    //         'specialties',
    //         'health_condition',
    //         'room_number',
    //         'bed_number',
    //         'blood_pressure',
    //         'blood_sugar',
    //         'temperature',
    //         'blood_analysis',
    //         'urine_output',
    //         'doses',
    //         'oxygen_level',
    //         'doctor_report',
    //         'discharge_date'
    //     ]));

    //     // إرجاع الاستجابة بنجاح
    //     return response()->json([
    //         'message' => 'Patient updated successfully',
    //         'patient' => $intensiveCarePatient
    //     ], 200);
    // }
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
        $intensiveCarePatient->update(["discharge_date" => now()->toDateTimeString()]);

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







