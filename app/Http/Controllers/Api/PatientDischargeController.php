<?php

namespace App\Http\Controllers\Api;

use App\Models\IntensiveCarePatient;
use App\Models\DoctorReport;
use App\Models\SurgeryDepartment;
use App\Models\InternalDepartment;
use App\Models\ObstetricsGynecology;
use App\Models\Pediatric;
use App\Models\Bed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PatientDischargeController extends Controller
{
    public function discharge(Request $request)
    {
        // التحقق من المصادقة
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 401);
            }

            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'icup_id' => 'sometimes|exists:intensive_care_patients,id',
                'surgery_department_id' => 'sometimes|exists:surgery_departments,id',
                'Internal_department_id' => 'sometimes|exists:internal_departments,id',
                'OBGYN_id' => 'sometimes|exists:obstetrics_gynecology,id',
                'pediatric_department_id' => 'sometimes|exists:pediatrics,id',
                'report' => 'required|string',
                'department' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 400);
            }

            // تحديد القسم المناسب
            if ($request->icup_id) {
                return $this->handleICUDischarge($request, $currentUser);
            }

            if ($request->surgery_department_id) {
                return $this->handleSurgeryDischarge($request, $currentUser);
            }

            if ($request->Internal_department_id) {
                return $this->handleInternalDischarge($request, $currentUser);
            }

            if ($request->OBGYN_id) {
                return $this->handleOBGYNDischarge($request, $currentUser);
            }

            if ($request->pediatric_department_id) {
                return $this->handlePediatricDischarge($request, $currentUser);
            }

            return response()->json([
                'success' => false,
                'message' => 'لم يتم تحديد قسم صالح للإخراج'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Discharge error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function handleICUDischarge($request, $currentUser)
    {
        DB::beginTransaction();
        try {
            $ICU = IntensiveCarePatient::with(['patient', 'doctors'])
                ->where('id', $request->icup_id)
                ->whereNull('discharge_date')
                ->first();

            if (!$ICU) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود في العناية المشددة أو تم إخراجه مسبقاً'
                ], 404);
            }

            // التحقق من الصلاحيات
            $isDoctor = $currentUser->role === 'doctor' && $ICU->doctors->contains($currentUser->id);
            $isDeptHead = $currentUser->role === 'department_head' && optional($currentUser->department)->name === "قسم العناية المشددة";
            
            if (!$isDoctor && !$isDeptHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإخراج المريض من هذا القسم'
                ], 403);
            }

            // إنشاء التقارير
            $this->createReports($currentUser, $ICU->patient->id, $request->report, $request->department, $ICU->id);
            
            // تحديث تاريخ الإخراج
            $ICU->update(['discharge_date' => now()]);
            
            // تحديث حالة السرير
            $bed = Bed::find($ICU->bed_id);
            if (!$bed) {
                throw new \Exception('السرير غير موجود');
            }
            $bed->update(['status' => 'available']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'تم إخراج المريض من العناية المشددة بنجاح'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('ICU Discharge error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في إخراج المريض: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleSurgeryDischarge($request, $currentUser)
    {
        DB::beginTransaction();
        try {
            $surgery = SurgeryDepartment::with(['patient', 'doctors'])
                ->where('id', $request->surgery_department_id)
                ->whereNull('discharge_date')
                ->first();

            if (!$surgery) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود في قسم الجراحة أو تم إخراجه مسبقاً'
                ], 404);
            }

            $isDoctor = $currentUser->role === 'doctor' && $surgery->doctors->contains($currentUser->id);
            $isDeptHead = $currentUser->role === 'department_head' && optional($currentUser->department)->name === "قسم الجراحة";
            
            if (!$isDoctor && !$isDeptHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإخراج المريض من هذا القسم'
                ], 403);
            }

            $this->createReports($currentUser, $surgery->patient->id, $request->report, $request->department);

            $surgery->update(['discharge_date' => now()]);
            
            $bed = Bed::find($surgery->bed_id);
            if (!$bed) {
                throw new \Exception('السرير غير موجود');
            }
            $bed->update(['status' => 'available']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'تم إخراج المريض من قسم الجراحة بنجاح'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Surgery Discharge error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في إخراج المريض: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleInternalDischarge($request, $currentUser)
    {
        DB::beginTransaction();
        try {
            $internal = InternalDepartment::with(['patient', 'doctors'])
                ->where('id', $request->Internal_department_id)
                ->whereNull('discharge_date')
                ->first();

            if (!$internal) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود في قسم الباطنية أو تم إخراجه مسبقاً'
                ], 404);
            }

            $isDoctor = $currentUser->role === 'doctor' && $internal->doctors->contains($currentUser->id);
            $isDeptHead = $currentUser->role === 'department_head' && optional($currentUser->department)->name === "قسم الداخلية";
            
            if (!$isDoctor && !$isDeptHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإخراج المريض من هذا القسم'
                ], 403);
            }

            $this->createReports($currentUser, $internal->patient->id, $request->report, $request->department);

            $internal->update(['discharge_date' => now()]);
            
            $bed = Bed::find($internal->bed_id);
            if (!$bed) {
                throw new \Exception('السرير غير موجود');
            }
            $bed->update(['status' => 'available']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'تم إخراج المريض من قسم الباطنية بنجاح'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Internal Discharge error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في إخراج المريض: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handleOBGYNDischarge($request, $currentUser)
    {
        DB::beginTransaction();
        try {
            $obgyn = ObstetricsGynecology::with(['patient', 'doctors'])
                ->where('id', $request->OBGYN_id)
                ->whereNull('discharge_date')
                ->first();

            if (!$obgyn) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود في قسم النساء والتوليد أو تم إخراجه مسبقاً'
                ], 404);
            }

            $isDoctor = $currentUser->role === 'doctor' && $obgyn->doctors->contains($currentUser->id);
            $isDeptHead = $currentUser->role === 'department_head' && optional($currentUser->department)->name === "قسم النسائية";
            
            if (!$isDoctor && !$isDeptHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإخراج المريض من هذا القسم'
                ], 403);
            }

            $this->createReports($currentUser, $obgyn->patient->id, $request->report, $request->department);

            $obgyn->update(['discharge_date' => now()]);
            
            $bed = Bed::find($obgyn->bed_id);
            if (!$bed) {
                throw new \Exception('السرير غير موجود');
            }
            $bed->update(['status' => 'available']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'تم إخراج المريض من قسم النساء والتوليد بنجاح'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('OBGYN Discharge error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في إخراج المريض: ' . $e->getMessage()
            ], 500);
        }
    }

    private function handlePediatricDischarge($request, $currentUser)
    {
        DB::beginTransaction();
        try {
            $pediatric = Pediatric::with(['patient', 'doctors'])
                ->where('id', $request->pediatric_department_id)
                ->whereNull('discharge_date')
                ->first();

            if (!$pediatric) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود في قسم الأطفال أو تم إخراجه مسبقاً'
                ], 404);
            }

            $isDoctor = $currentUser->role === 'doctor' && $pediatric->doctors->contains($currentUser->id);
            $isDeptHead = $currentUser->role === 'department_head' && optional($currentUser->department)->name === "قسم الأطفال";
            
            if (!$isDoctor && !$isDeptHead) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإخراج المريض من هذا القسم'
                ], 403);
            }

            $this->createReports($currentUser, $pediatric->patient->id, $request->report, $request->department);

            $pediatric->update(['discharge_date' => now()]);
            
            $bed = Bed::find($pediatric->bed_id);
            if (!$bed) {
                throw new \Exception('السرير غير موجود');
            }
            $bed->update(['status' => 'available']);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'تم إخراج المريض من قسم الأطفال بنجاح'
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Pediatric Discharge error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'فشل في إخراج المريض: ' . $e->getMessage()
            ], 500);
        }
    }

    private function createReports($user, $patientId, $report, $department, $icup_id = null)
    {
        try {
            // إنشاء تقرير القسم (إذا كان icup_id موجودًا)
            if (!is_null($icup_id)) {
                DoctorReport::create([
                    "user_id" => $user->id,
                    'icup_id' => $icup_id,
                    'patient_id' => $patientId,
                    'department' => $department,
                    'report' => $report,
                    'type' => "department",
                ]);
            }

            // إنشاء التقرير العام
            DoctorReport::create([
                "user_id" => $user->id,
                'patient_id' => $patientId,
                'department' => $department,
                'report' => $report,
                'type' => 'general',
            ]);

        } catch (\Exception $e) {
            Log::error('Create report error: ' . $e->getMessage());
            throw $e;
        }
    }
}