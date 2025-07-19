<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoctorReport;
use App\Models\IntensiveCarePatient;
use App\Models\Patients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
class DoctorReportController extends Controller
{
    public function store(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من صلاحية المستخدم كدكتور
        if (!$currentUser || !in_array($currentUser->role, ['doctor', 'department_head'])) {
            return response()->json([
                'success' => false,
                'message' => 'إنشاء التقارير متاح للأطباء ورؤساء الأقسام فقط. إذا كنت مؤهلاً، الرجاء تسجيل الدخول بحسابك الصحيح أو التواصل مع مدير النظام.'
            ], 403);
        }

        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'icup_id' => 'nullable|exists:intensive_care_patients,id',
            'patient_id' => 'nullable|exists:patients,id',
            'report' => 'required|string',
            'department' => 'required|string',
            'type' => 'required|in:department,general',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // التحقق من وجود أحد المعرفين على الأقل
        if (!$request->icup_id && !$request->patient_id) {
            return response()->json([
                'success' => false,
                'message' => 'يجب توفير معرف المريض سواء في العناية المركزة أو المرضى العاديين'
            ], 422);
        }

        // إذا كان التقرير لمريض العناية المركزة
        if ($request->icup_id && $request->type === 'department') {
            $icup = IntensiveCarePatient::with(['user_accounts'])
                ->where('id', $request->icup_id)
                ->whereNull('discharge_date')
                ->first();

            if (!$icup) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود في العناية المركزة أو تم إخراجه بالفعل'
                ], 404);
            }

            // التحقق مما إذا كان الدكتور الحالي مسؤولاً عن هذا المريض
            $isAssignedDoctor = $icup->user_accounts->contains('id', $currentUser->id);

            if (!$isAssignedDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: أنت لست الطبيب المسؤول عن هذا المريض',
                ], 403);
            }
            if (($currentUser->role === 'doctor' && !$icup->user_accounts->contains('id', $currentUser->id)) || ($currentUser->role === 'department_head' && $currentUser->department->name !== 'قسم العناية المشددة')) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: أنت لست الطبيب المسؤول عن هذا المريض'
                ], 403);
            }

            // إنشاء التقرير لمريض العناية المركزة
            $report = DoctorReport::create([
                'user_id' => $currentUser->id,
                'icup_id' => $request->icup_id,
                'report' => $request->report,
                'department' => $request->department,
                'type' => $request->type,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء التقرير لمريض العناية المركزة بنجاح',
                'data' => $report
            ], 201);
        }
        // إذا كان التقرير لمريض عادي
        elseif ($request->patient_id && $request->type === 'general') {
            $patient = Patients::where('id', $request->patient_id)
                ->where('status', true)
                ->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود أو بطاقة غير مفعله'
                ], 404);
            }

            // إنشاء التقرير للمريض العادي
            $report = DoctorReport::create([
                'user_id' => $currentUser->id,
                'patient_id' => $request->patient_id,
                'report' => $request->report,
                'department' => $request->department,
                'type' => $request->type,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'تم إنشاء التقرير للمريض العادي بنجاح',
                'data' => $report
            ], 201);
        }
        // في حالة عدم تطابق أي من الشروط السابقة
        else {
            return response()->json([
                'success' => false,
                'message' => 'تركيبة البيانات غير صالحة. يرجى التأكد من تطابق نوع التقرير مع نوع المريض المحدد'
            ], 400);
        }
    }

    /**
     * تحديث تقرير موجود
     */
    public function update(Request $request, $id)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من صلاحية المستخدم كدكتور
        if (!$currentUser || $currentUser->role !== 'doctor') {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: يجب أن تكون دكتور للتعديل على التقارير',
            ], 403);
        }
        // التحقق من صحة البيانات المدخلة
        $validator = Validator::make($request->all(), [
            'report' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }
        // البحث عن التقرير المطلوب
        $report = DoctorReport::find($id);

        if (!$report) {
            return response()->json([
                'success' => false,
                'message' => 'التقرير غير موجود'
            ], 404);
        }

        // التحقق من أن الدكتور هو من أنشأ التقرير
        if ($report->user_id != $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالتعديل: يمكنك فقط تعديل التقارير التي أنشأتها',
            ], 403);
        }

        // إذا كان التقرير لمريض العناية المركزة
        if ($report->icup_id) {
            $icup = IntensiveCarePatient::with(['user_accounts'])
                ->where('id', $report->icup_id)
                ->whereNull('discharge_date')
                ->first();

            if (!$icup) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود في العناية المركزة أو تم إخراجه'
                ], 400);
            }

            $isAssignedDoctor = $icup->user_accounts->contains('id', $currentUser->id);
            if (!$isAssignedDoctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالتعديل: لم تعد مسؤولاً عن هذا المريض',
                ], 403);
            }
        }
        if ($report->patient_id) {
            $patient = Patients::where('id', $report->patient_id)
                ->where('is_active', true)
                ->first();

            if (!$patient) {
                return response()->json([
                    'success' => false,
                    'message' => 'المريض غير موجود أو بطاقة غير مفعلة'
                ], 400);
            }
        }
        // تحديث التقرير
        $report->update([
            'report' => $request->report,
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $report,
            'message' => 'تم تعديل التقرير بنجاح',
        ]);
    }
}