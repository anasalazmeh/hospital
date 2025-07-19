<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bed;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class BedController extends Controller
{
    /**
     * عرض جميع الأسرة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // إذا كان مديرًا
            if ($currentUser->role === 'admin') {
                $beds = Bed::with(['room', 'department'])->get();

                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب جميع الأسرة بنجاح',
                    'data' => $beds
                ]);
            }

            // إذا كان رئيس قسم
            if ($currentUser->role === 'department_head') {
                $beds = Bed::where('department_id', $currentUser->department_id)
                    ->with(['room', 'department'])
                    ->get();

                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب أسرة قسمك بنجاح',
                    'data' => $beds
                ]);
            }

            // إذا لم يكن لديه صلاحيات
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحيات لعرض الأسرة'
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض سرير محدد
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // البحث عن السرير
            $bed = Bed::with(['room', 'department'])->find($id);

            if (!$bed) {
                return response()->json([
                    'success' => false,
                    'message' => 'السرير غير موجود'
                ], 404);
            }

            // إذا كان مديرًا
            if ($currentUser->role === 'admin') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب بيانات السرير بنجاح',
                    'data' => $bed
                ]);
            }

            // إذا كان رئيس قسم
            if ($currentUser->role === 'department_head') {
                if ($bed->department_id === $currentUser->department_id) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم جلب بيانات السرير بنجاح',
                        'data' => $bed
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك صلاحية عرض هذا السرير'
                ], 403);
            }

            // إذا لم يكن لديه صلاحيات
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحيات لعرض الأسرة'
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * إنشاء سرير جديد
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // التحقق من أن المستخدم رئيس قسم
            if ($currentUser->role !== 'department_head') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بإنشاء أسرة'
                ], 403);
            }

            // إضافة department_id تلقائياً من قسم المستخدم
            $request->merge(['department_id' => $currentUser->department_id]);

            $validator = Validator::make($request->all(), [
                'department_id' => 'required|exists:departments,id',
                'room_id' => [
                    'required',
                    'exists:rooms,id',
                    function ($attribute, $value, $fail) use ($currentUser) {
                        $room = Room::find($value);
                        if ($room && $room->department_id !== $currentUser->department_id) {
                            $fail('الغرفة المحددة ليست في قسمك');
                        }
                        if ($room->is_active === false) {
                            $fail('الغرفة المحددة معطلة');
                        }
                    }
                ],
                'bed_number' => 'required|string|unique:beds',
                'status' => 'sometimes|in:available,occupied',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }
            $room = Room::with('beds')
                ->where('id', $request->room_id)
                ->where('department_id', $request->department_id)
                ->first();

            if (!$room) {
                return response()->json(['message' => 'الغرفة غير موجودة أو لا تنتمي لهذا القسم'], 404);
            }

            if ($room->beds->count() === $room->capacity) {
                return response()->json([
                    'message' => 'لا يمكن إضافة سرير جديد، الغرفة ممتلئة بالكامل (السعة: ' . $room->capacity . ')'
                ], 422);
            }
            $bed = Bed::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء السرير بنجاح',
                'data' => $bed
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء السرير',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث بيانات السرير
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            $bed = Bed::find($id);

            if (!$bed) {
                return response()->json([
                    'success' => false,
                    'message' => 'السرير غير موجود'
                ], 404);
            }

            // التحقق من أن المستخدم رئيس قسم وأن السرير تابع لقسمه
            if ($currentUser->role !== 'department_head' || $bed->department_id !== $currentUser->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا السرير'
                ], 403);
            }

            // منع تغيير القسم للسرير
            if ($request->has('department_id') && $request->department_id != $currentUser->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن نقل السرير إلى قسم آخر'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'room_id' => [
                    'sometimes',
                    'exists:rooms,id',
                    function ($attribute, $value, $fail) use ($currentUser) {
                        $room = Room::find($value);
                        if ($room && $room->department_id !== $currentUser->department_id) {
                            $fail('الغرفة المحددة ليست في قسمك');
                        }
                    }
                ],
                'bed_number' => 'sometimes|string|unique:beds,bed_number,' . $id,
                'status' => 'sometimes|in:available,occupied',
                'is_active' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $bed->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات السرير بنجاح',
                'data' => $bed
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء التحديث',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض الأسرة المتاحة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableBeds()
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // بناء الاستعلام الأساسي للأسرة المتاحة
            $query = Bed::where('status', 'available')
                ->where('is_active', true)
                ->with(['room', 'department']);

            // تطبيق الفلاتر حسب الصلاحية
            if ($currentUser->role === 'admin') {
                // المدير يرى جميع الأسرة المتاحة
                $beds = $query->get();
            } elseif ($currentUser->role === 'department_head') {
                // رئيس القسم يرى فقط أسرة قسمه المتاحة
                $beds = $query->where('department_id', $currentUser->department_id)->get();
            } else {
                // مستخدم عادي بدون صلاحيات
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحيات لعرض الأسرة المتاحة'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الأسرة المتاحة بنجاح',
                'data' => $beds
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأسرة المتاحة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض الأسرة المشغولة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function occupiedBeds()
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // بناء الاستعلام الأساسي
            $query = Bed::where('status', 'occupied')
                ->with(['room', 'department']);

            // تطبيق الفلاتر حسب الصلاحية
            if ($currentUser->role === 'admin') {
                // المدير يرى جميع الأسرة المشغولة
                $beds = $query->get();
            } elseif ($currentUser->role === 'department_head') {
                // رئيس القسم يرى فقط أسرة قسمه المشغولة
                $beds = $query->where('department_id', $currentUser->department_id)->get();
            } else {
                // مستخدم عادي بدون صلاحيات
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحيات لعرض الأسرة المشغولة'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الأسرة المشغولة بنجاح',
                'data' => $beds
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الأسرة المشغولة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}