<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
class RoomController extends Controller
{
    /**
     * عرض جميع الغرف
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
                $rooms = Room::with(['department','beds'])->get();

                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب جميع الغرف بنجاح',
                    'data' => $rooms
                ]);
            }

            // إذا كان رئيس قسم
            if ($currentUser->role === 'department_head') {
                $rooms = Room::where('department_id', $currentUser->department_id)
                    ->with(['department','beds'])
                    ->get();

                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب غرف قسمك بنجاح',
                    'data' => $rooms
                ]);
            }

            // إذا لم يكن لديه صلاحيات
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحيات لعرض الغرف'
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
     * عرض غرفة محددة
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

            // البحث عن الغرفة
            $room = Room::with('department')->find($id);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'الغرفة غير موجودة'
                ], 404);
            }

            // إذا كان مديرًا
            if ($currentUser->role === 'admin') {
                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب بيانات الغرفة بنجاح',
                    'data' => $room
                ]);
            }

            // إذا كان رئيس قسم
            if ($currentUser->role === 'department_head') {
                if ($room->department_id === $currentUser->department_id) {
                    return response()->json([
                        'success' => true,
                        'message' => 'تم جلب بيانات الغرفة بنجاح',
                        'data' => $room
                    ]);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'لا تملك صلاحية عرض هذه الغرفة'
                ], 403);
            }

            // إذا لم يكن لديه صلاحيات
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحيات لعرض الغرف'
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
     * إنشاء غرفة جديدة
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
                    'message' => 'غير مصرح لك بإنشاء غرف'
                ], 403);
            }

            // إضافة department_id تلقائياً من قسم المستخدم
            $request->merge(['department_id' => $currentUser->department_id]);

            $validator = Validator::make($request->all(), [
                'department_id' => 'required|exists:departments,id',
                'room_number' => 'required|string|unique:rooms',
                'capacity' => 'required|integer|min:1',
                'status' => 'sometimes|in:available,occupied',
                'is_active' => 'sometimes|boolean' // إضافة التحقق من الحقل الجديد
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $room = Room::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الغرفة بنجاح',
                'data' => $room
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء الغرفة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * تحديث بيانات الغرفة
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

            $room = Room::find($id);

            if (!$room) {
                return response()->json([
                    'success' => false,
                    'message' => 'الغرفة غير موجودة'
                ], 404);
            }

            // التحقق من أن المستخدم رئيس قسم وأن الغرفة تابعة لقسمه
            if ($currentUser->role !== 'department_head' || $room->department_id !== $currentUser->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذه الغرفة'
                ], 403);
            }

            // منع تغيير القسم للغرفة
            if ($request->has('department_id') && $request->department_id != $currentUser->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن نقل الغرفة إلى قسم آخر'
                ], 422);
            }

            $validator = Validator::make($request->all(), [
                'room_number' => 'sometimes|string|unique:rooms,room_number,' . $id,
                'capacity' => 'sometimes|integer|min:1',
                'status' => 'sometimes|in:available,occupied',
                'is_active' => 'sometimes|boolean' // إضافة التحقق من الحقل الجديد
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في البيانات المدخلة',
                    'errors' => $validator->errors()
                ], 422);
            }

            $room->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات الغرفة بنجاح',
                'data' => $room
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
     * حذف غرفة
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * عرض الغرف المتاحة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function availableRooms()
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // بناء الاستعلام الأساسي للغرف المتاحة
            $query = Room::available()->with('department');

            // تطبيق الفلاتر حسب الصلاحية
            if ($currentUser->role === 'admin') {
                // المدير يرى جميع الغرف المتاحة
                $rooms = $query->get();
            } elseif ($currentUser->role === 'department_head') {
                // رئيس القسم يرى فقط غرف قسمه المتاحة
                $rooms = $query->where('department_id', $currentUser->department_id)->get();
            } else {
                // مستخدم عادي بدون صلاحيات
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحيات لعرض الغرف المتاحة'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الغرف المتاحة بنجاح',
                'data' => $rooms
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الغرف المتاحة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * عرض الغرف المشغولة
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function occupiedRooms()
    {
        try {
            // التحقق من المستخدم الحالي
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // بناء الاستعلام الأساسي
            $query = Room::occupied()->with('department');

            // تطبيق الفلاتر حسب الصلاحية
            if ($currentUser->role === 'admin') {
                // المدير يرى جميع الغرف المشغولة
                $rooms = $query->get();
            } elseif ($currentUser->role === 'department_head') {
                // رئيس القسم يرى فقط غرف قسمه المشغولة
                $rooms = $query->where('department_id', $currentUser->department_id)->get();
            } else {
                // مستخدم عادي بدون صلاحيات
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحيات لعرض الغرف المشغولة'
                ], 403);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الغرف المشغولة بنجاح',
                'data' => $rooms
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الغرف المشغولة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}