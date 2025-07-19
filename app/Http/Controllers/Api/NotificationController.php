<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;
use App\Models\UserAccount;
use App\Models\Patient;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;

class NotificationController extends Controller
{
    // عرض جميع إشعارات المستخدم
    public function index(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب عليك تسجيل دخول أولاً'
            ], 403);
        }
        try {
            $notifications = Notification::with(['sender', 'receiver', 'patient'])
                ->where('receiver_account_id', $currentUser->id)
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            return response()->json([
                'success' => true,
                'data' => $notifications,
                'unread_count' => Notification::where('receiver_account_id', auth()->id())
                    ->where('is_read', false)
                    ->count()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في جلب الإشعارات: ' . $e->getMessage()
            ], 500);
        }
    }

    // إنشاء إشعار جديد
    public function store(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب عليك تسجيل دخول أولاً'
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'receiver_account_id' => 'required|exists:user_accounts,id',
            'patient_id' => 'nullable|exists:patients,id',
            'title' => 'required|string|max:100',
            'message' => 'required|string|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $notification = Notification::create([
                'sender_account_id' => $currentUser->id,
                'receiver_account_id' => $request->receiver_account_id,
                'patient_id' => $request->patient_id,
                'title' => $request->title,
                'message' => $request->message,
                'is_read' => false
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال الإشعار بنجاح',
                'data' => $notification->load(['sender', 'receiver'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل في إرسال الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }

    // تحديث حالة القراءة
    public function markAsRead($id)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب عليك تسجيل دخول أولاً'
                ], 403);
            }
            $notification = Notification::findOrFail($id);

            if ($notification->receiver_account_id !== $currentUser->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتحديث هذا الإشعار'
                ], 403);
            }

            if (!$notification->is_read) {
                $notification->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة الإشعار إلى مقروء',
                'data' => $notification
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث الإشعار: ' . $e->getMessage()
            ], 500);
        }
    }
    // تحديث جميع  اشعارات حالة القراءة
    public function markAllAsRead()
    {
        try {
                        $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب عليك تسجيل دخول أولاً'
                ], 403);
            }
            ;

            // تحديث جميع الإشعارات غير المقروءة للمستخدم الحالي
            $updatedCount = Notification::where('receiver_account_id', $currentUser->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now()
                ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث ' . $updatedCount . ' إشعار كمقروء',
                'data' => [
                    'marked_as_read_count' => $updatedCount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل في تحديث الإشعارات: ' . $e->getMessage()
            ], 500);
        }
    }
    // حذف إشعار
    // public function destroy($id)
    // {
    //     try {
    //         $notification = Notification::findOrFail($id);

    //         if ($notification->sender_account_id !== auth()->id()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'غير مصرح لك بحذف هذا الإشعار'
    //             ], 403);
    //         }

    //         $notification->delete();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'تم حذف الإشعار بنجاح'
    //         ]);

    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'فشل في حذف الإشعار: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
}