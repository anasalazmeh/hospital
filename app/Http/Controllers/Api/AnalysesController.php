<?php

namespace App\Http\Controllers\Api;

use App\Models\Analysis;
use App\Models\Patients;
use App\Models\IntensiveCarePatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class AnalysesController extends Controller
{
    // الحصول على جميع التحاليل مع إمكانية التصفية
    public function index()
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser || !in_array($currentUser->role, ['admin', 'lab_technician'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }

            $analyses = Analysis::all();
            $data = [];

            foreach ($analyses as $item) {
                $data[] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'patient' => $item->patient,
                    'analysis_date' => $item->analysis_date,
                    'pdf_url' => $item->pdf_path ? asset("storage/{$item->pdf_path}") : null
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in AnalysisController', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // إضافة trace للخطأ
            ]);
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    // الحصول على تحليل معين
    public function show($id)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if (!$currentUser || !in_array($currentUser->role, ['admin', 'lab_technician'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }

            // $analysis = Analysis::with([
            //     'patient:id,id_card,name',
            //     'icupPatient:id,id_card,bed_number'
            // ])->find($id);
            $analysis = Analysis::find($id);

            if (!$analysis) {
                return response()->json([
                    'success' => false,
                    'message' => 'السجل المخبري غير موجود',
                    'data' => null
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب السجل بنجاح',
                'data' => [
                    'id' => $analysis->id,
                    'title' => $analysis->title,
                    'description' => $analysis->description,
                    'analysis_date' => $analysis->analysis_date,
                    'patient' => $analysis->patient,
                    'icu_patient' => $analysis->icupPatient,
                    'pdf_file' => $analysis->pdf_path,
                    'pdf_url' => $analysis->pdf_path ? asset("storage/{$analysis->pdf_path}") : null
                ]
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الجلسة',
                'data' => null
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'توكن غير صالح',
                'data' => null
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في الخادم',
                'data' => null
            ], 500);
        }
    }
    // إنشاء تحليل جديد
    public function store(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من صلاحية المستخدم
        if (!$currentUser || !in_array($currentUser->role, ['admin', 'lab_technician'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً او فني مخبر للوصول إلى هذه البيانات',
                'data' => null
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'id_card' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'pdf_file' => 'required|file|mimes:pdf|max:2048',
            'analysis_date' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $patient = Patients::where('id_card', $request->id_card)->first();

        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'المريض غير موجود'
            ], 404);
        }

        // البحث عن مريض العناية المركزة إذا وجد
        $icup = IntensiveCarePatient::where('id_card', $request->id_card)
            ->whereNull('discharge_date')
            ->orderByDesc('created_at')
            ->first();

        // تحويل تنسيق التاريخ قبل التخزين
        $analysisDate = Carbon::parse($request->analysis_date)->format('Y-m-d H:i:s');

        // تخزين الملف
        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('analyses', $filename, 'public');

            $analysis = Analysis::create([
                'patient_id' => $patient->id,
                'icup_id' => $icup ? $icup->id : null,
                'title' => $request->title,
                'description' => $request->description,
                'pdf_path' => $path,
                'analysis_date' => $analysisDate, // استخدام التاريخ المعدل
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إضافة تحليل لمريض بنجاح',
                'data' => $analysis
            ], 201);
        }

        return response()->json([
            'success' => false,
            'message' => 'لم يتم تحميل الملف'
        ], 400);
    }
    public function update(Request $request, $id)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من صلاحية المستخدم
        if (!$currentUser || !in_array($currentUser->role, ['admin', 'lab_technician'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً او فني مخبر للوصول إلى هذه البيانات',
                'data' => null
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'pdf_file' => 'sometimes|file|mimes:pdf|max:2048',
            'analysis_date' => 'sometimes|date',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $analysis = Analysis::find($id);

        if (!$analysis) {
            return response()->json([
                'success' => false,
                'message' => 'التحليل غير موجود'
            ], 404);
        }

        // تحديث الحقول الأساسية
        if ($request->has('title')) {
            $analysis->title = $request->title;
        }

        if ($request->has('description')) {
            $analysis->description = $request->description;
        }

        if ($request->has('analysis_date')) {
            $analysis->analysis_date = Carbon::parse($request->analysis_date)->format('Y-m-d H:i:s');
        }

        // تحديث ملف PDF إذا تم توفيره
        if ($request->hasFile('pdf_file')) {
            $file = $request->file('pdf_file');

            if ($file->isValid()) {
                // حذف الملف القديم
                if ($analysis->pdf_path && Storage::disk('public')->exists($analysis->pdf_path)) {
                    Storage::disk('public')->delete($analysis->pdf_path);
                }
                $filename = time() . '_' . $file->getClientOriginalName();
                $path = $file->storeAs('analyses', $filename, 'public');
                $analysis->pdf_path = $path;
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في ملف الرفع: ' . $file->getErrorMessage()
                ], 400);
            }
        }

        $analysis->save();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث التحليل بنجاح',
            'data' => $analysis
        ], 200);
    }
    // حذف تحليل
    // public function destroy($id)
    // {
    //     $token = JWTAuth::getToken();
    //     $currentUser = JWTAuth::authenticate($token);
    //     // التحقق من صلاحية المستخدم
    //     if (!$currentUser || !in_array($currentUser->role, ['admin', 'lab_technician'])) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً او فني مخبر للوصول إلى هذه البيانات',
    //             'data' => null
    //         ], 403);
    //     }
    //     try {
    //         $analysis = Analysis::find($id);

    //         if (!$analysis) {
    //             return response()->json([
    //                 'status' => false,
    //                 'message' => 'التحليل المطلوب غير موجود',
    //                 'error_code' => 'ANALYSIS_NOT_FOUND'
    //             ], 404);
    //         }

    //         $analysis->delete();

    //         return response()->json([
    //             'status' => true,
    //             'message' => 'تم حذف التحليل بنجاح',
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'حدث خطأ أثناء محاولة حذف التحليل',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
