<?php

namespace App\Http\Controllers\Api;

use App\Models\Radiologies;
use App\Models\Patients;
use App\Models\IntensiveCarePatient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RadiologiesController extends Controller
{

    public function index()
    {
        try {
            // التحقق من المصادقة والصلاحيات
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser || !in_array($currentUser->role, ['admin', 'lab_technician'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }

            // جلب جميع سجلات الأشعة
            $radiologies = Radiologies::all();
            $data = [];

            foreach ($radiologies as $item) {
                // معالجة ملفات الوسائط
                $mediaFiles = $this->processMediaFiles($item->media_files);

                // إضافة بيانات الأشعة إلى المصفوفة النهائية
                $data[] = [
                    'id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'patient' => $item->patient,
                    'icu_patient' => $item->icupPatient,
                    'radiologies_date' => $item->radiologies_date,
                    'media_files' => $mediaFiles
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('Error in RadiologiesController@index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    /**
     * معالجة ملفات الوسائط
     *
     * @param mixed $mediaFiles
     * @return array
     */
    private function processMediaFiles($mediaFiles): array
    {
        $processedFiles = [];

        // تحويل الملفات إلى مصفوفة إذا كانت نصية
        if (is_string($mediaFiles)) {
            $decodedFiles = json_decode($mediaFiles, true);
            $mediaFiles = is_array($decodedFiles) ? $decodedFiles : [$mediaFiles];
        } elseif (is_null($mediaFiles)) {
            $mediaFiles = [];
        }

        // معالجة كل ملف
        foreach ($mediaFiles as $file) {
            try {
                if (is_array($file)) {
                    $processedFiles[] = [
                        'url' => asset('storage/' . $file['path']),
                        'name' => $file['name'] ?? basename($file['path']),
                        'type' => $file['type'] ?? $this->getFileMimeType($file['path']),
                        'size' => $file['size'] ?? $this->getFileSize($file['path'])
                    ];
                } else {
                    $processedFiles[] = [
                        'url' => asset('storage/' . $file),
                        'name' => basename($file),
                        'type' => $this->getFileMimeType($file),
                        'size' => $this->getFileSize($file)
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error processing media file', [
                    'file' => $file,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        return $processedFiles;
    }

    /**
     * الحصول على نوع الملف
     *
     * @param string $path
     * @return string
     */
    private function getFileMimeType(string $path): string
    {
        try {
            return mime_content_type(storage_path('app/public/' . $path)) ?: 'application/octet-stream';
        } catch (\Exception $e) {
            Log::warning('Could not determine mime type for file: ' . $path);
            return 'application/octet-stream';
        }
    }

    /**
     * الحصول على حجم الملف
     *
     * @param string $path
     * @return int
     */
    private function getFileSize(string $path): int
    {
        try {
            return Storage::disk('public')->size($path) ?: 0;
        } catch (\Exception $e) {
            Log::warning('Could not determine size for file: ' . $path);
            return 0;
        }
    }
public function show($id)
{
    try {
        // التحقق من المصادقة والصلاحيات
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        if (!$currentUser || !in_array($currentUser->role, ['admin', 'lab_technician'])) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول',
                'data' => null
            ], 403);
        }

        // البحث عن سجل الأشعة
        $radiology = Radiologies::find($id);

        if (!$radiology) {
            return response()->json([
                'success' => false,
                'message' => 'السجل الإشعاعي غير موجود',
                'data' => null
            ], 404);
        }

        // معالجة ملفات الوسائط بنفس الطريقة المستخدمة في index
        $mediaFiles = $this->processMediaFiles($radiology->media_files);

        return response()->json([
            'success' => true,
            'message' => 'تم جلب السجل بنجاح',
            'data' => [
                'id' => $radiology->id,
                'title' => $radiology->title,
                'description' => $radiology->description,
                'patient' => $radiology->patient,
                'icu_patient' => $radiology->icupPatient,
                'radiologies_date' => $radiology->radiologies_date,
                'media_files' => $mediaFiles
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
        Log::error('Error in RadiologiesController@show', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ: ' . $e->getMessage(),
            'data' => null
        ], 500);
    }
}

    public function store(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

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
            'media_files' => 'required|array',
            'media_files.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi', // 10MB لكل ملف
            'radiologies_date' => 'required|date',
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

        $icup = IntensiveCarePatient::where('id_card', $request->id_card)
            ->whereNull('discharge_date')
            ->orderByDesc('created_at')
            ->first();

        $radiologiesDate = Carbon::parse($request->radiologies_date)->format('Y-m-d H:i:s');

        $mediaData = [];
        if ($request->hasFile('media_files')) {
            $files = $request->file('media_files');
            // تأكد أننا نتعامل مع مصفوفة حتى لو كان ملف واحد
            if (!is_array($files)) {
                $files = [$files];
            }
            foreach ($files as $file) {
                if ($file->isValid()) {
                    $path = $file->store('radiologies', 'public');
                    $mediaData[] = [
                        'path' => $path,
                        'name' => $file->getClientOriginalName(),
                        'type' => $file->getMimeType(),
                        'size' => $file->getSize()
                    ];
                }
            }
        }
        $radiology = Radiologies::create([
            'patient_id' => $patient->id,
            'icup_id' => $icup ? $icup->id : null,
            'title' => $request->title,
            'description' => $request->description,
            'media_files' => json_encode($mediaData),
            'radiologies_date' => $radiologiesDate,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إضافة فحص إشعاعي للمريض بنجاح',
            'data' => $radiology
        ], 201);
    }

public function update(Request $request, $id)
{
    $token = JWTAuth::getToken();
    $currentUser = JWTAuth::authenticate($token);

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
        'media_files' => 'sometimes|array',
        'media_files.*' => 'file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:10240', // 10MB لكل ملف
        'radiologies_date' => 'sometimes|date',
    ]);

    if ($validator->fails()) {
        return response()->json($validator->errors(), 400);
    }

    $radiology = Radiologies::find($id);

    if (!$radiology) {
        return response()->json([
            'success' => false,
            'message' => 'الفحص الإشعاعي غير موجود'
        ], 404);
    }

    if ($request->has('title')) {
        $radiology->title = $request->title;
    }

    if ($request->has('description')) {
        $radiology->description = $request->description;
    }

    if ($request->has('radiologies_date')) {
        $radiology->radiologies_date = Carbon::parse($request->radiologies_date)->format('Y-m-d H:i:s');
    }

    if ($request->hasFile('media_files')) {
        // حذف الملفات القديمة
        $oldMedia = json_decode($radiology->media_files ?? '[]', true);
        foreach ($oldMedia as $oldFile) {
            if (Storage::disk('public')->exists($oldFile['path'])) {
                Storage::disk('public')->delete($oldFile['path']);
            }
        }

        // رفع الملفات الجديدة
        $mediaData = [];
        $files = $request->file('media_files');
        if (!is_array($files)) {
            $files = [$files];
        }
        foreach ($files as $file) {
            if ($file->isValid()) {
                $path = $file->store('radiologies', 'public');
                $mediaData[] = [
                    'path' => $path,
                    'name' => $file->getClientOriginalName(),
                    'type' => $file->getMimeType(),
                    'size' => $file->getSize()
                ];
            }
        }
        $radiology->media_files = json_encode($mediaData);
    }

    $radiology->save();

    return response()->json([
        'success' => true,
        'message' => 'تم تحديث الفحص الإشعاعي بنجاح',
        'data' => $radiology
    ], 200);
}
}