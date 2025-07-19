<?php
// app/Http/Controllers/Api/DepartmentRequestController.php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepartmentRequest;
use App\Models\DepartmentRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\DashboardAccounts;
use App\Models\Notification;
class DepartmentRequestController extends Controller
{
    // الحصول على جميع طلبات الأقسام
    public function index()
    {
        $requests = DepartmentRequest::with(["departmentRequestItems.item", "department", 'processor','issuer'])->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    // إنشاء طلب جديد
    public function store(Request $request)
    {
        // التحقق من المصادقة باستخدام JWT
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();
            if ($currentUser->role !== 'department_head') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون رئيس قسم',
                    'data' => null
                ], 403);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول. يرجى تسجيل الدخول أولاً'
            ], 401);
        }

        // بدء المعاملة
        \DB::beginTransaction();

        try {
            // التحقق من صحة البيانات
            $validator = Validator::make($request->all(), [
                'notes' => 'nullable|string|max:500',
                'items' => 'required|array|min:1|max:100',
                'items.*.item_id' => 'required|integer|exists:items,id',
                'items.*.quantity' => 'required|integer|min:1|max:1000'
            ], [
                'items.*.item_id.exists' => 'الصنف المحدد غير موجود أو غير متاح',
                'items.*.quantity.min' => 'الكمية يجب أن تكون على الأقل 1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // إنشاء طلب القسم
            $departmentRequest = DepartmentRequest::create([
                'department_id' => $currentUser->department_id ?? 1, // استخدام قسم المستخدم إن وجد
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
                'requested_by' => $currentUser->id,

            ]);

            // إضافة العناصر باستخدام insert بدلاً من create لكل عنصر لتحسين الأداء
            $requestItems = array_map(function ($item) use ($departmentRequest) {
                return [
                    'department_request_id' => $departmentRequest->id,
                    'item_id' => $item['item_id'],
                    'quantity' => $item['quantity'],
                ];
            }, $validated['items']);

            DepartmentRequestItem::insert($requestItems);
            $warehouseManagers = DashboardAccounts::where("role", "warehouse_manager")->get();

            $notifications = [];

            foreach ($warehouseManagers as $manager) {
                $notifications[] = [
                    'sender_account_id' => $currentUser->id,
                    'receiver_account_id' => $manager->id,
                    'title' => 'طلب جديد يحتاج مراجعة',
                    'message' => 'يوجد طلب جديد برقم #' . $departmentRequest->id . ' يحتاج إلى مراجعتك',
                    'priority' => 'normal', // أو 'urgent' أو 'critical'
                    'link' => "/department-requests-warehouse", // الرابط المرتبط بالإشعار
                ];
            }

            Notification::insert($notifications);
            \DB::commit();

            // تحميل العلاقات مع التخزين المؤقت

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الطلب بنجاح',
                'data' => $departmentRequest,
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في قاعدة البيانات',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع',
                'error' => env('APP_DEBUG') ? $e->getMessage() : null
            ], 500);
        }
    }

    // الحصول على طلب محدد
    public function show($id)
    {
        $departmentRequest = DepartmentRequest::with(['department', 'requester', 'items.item'])->find($id);

        if (!$departmentRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatRequest($departmentRequest)
        ]);
    }


    // دالة مساعدة لتنسيق بيانات الطلب
    private function formatRequest($request)
    {
        return [
            'id' => $request->id,
            'department' => $request->department->name,
            'status' => $request->status,
            'notes' => $request->notes,
            'requested_by' => $request->requester->name,
            'items' => $request->items->map(function ($item) {
                return [
                    'item_id' => $item->item_id,
                    'item_name' => $item->item->name,
                    'quantity' => $item->quantity,
                    'approved_quantity' => $item->approved_quantity
                ];
            }),
            'created_at' => $request->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $request->updated_at->format('Y-m-d H:i:s')
        ];
    }
    /**
     * تسجيل خروج المواد من المستودع
     */
    public function issueRequest(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب عليك تسجيل دخول أولاً'
                ], 403);
            }

            // التحقق من الصلاحيات إن لزم الأمر
            if (!in_array($currentUser->role, ['warehouse_manager', 'warehouse_employee'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'ليس لديك صلاحية لتخريج الطلبات'
                ], 403);
            }
            // البحث عن الطلب
            $departmentRequest = DepartmentRequest::with('requester')->findOrFail($id);

            // التحقق من صلاحية الطلب للإصدار
            if (!$departmentRequest || !$departmentRequest->isReadyForIssue()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إصدار هذا الطلب لأنه غير معتمد أو مرفوض'
                ], 400);
            }
            if ($departmentRequest->is_issued === true) {
                return response()->json([
                    'success' => false,
                    'message' => "تم تخريج الطلبية من قبل"
                ], 400);
            }
            Notification::create([
                'sender_account_id' => $currentUser->id,
                'receiver_account_id' => $departmentRequest->requester->id,
                'title' => 'من المستودع',
                'message' => 'الطلبية في طريقها اليك ',
                'priority' => 'normal', // أو 'urgent' أو 'critical'
                'link' => "/department-requests", // الرابط المرتبط بالإشعار
            ]);
            // تحديث حالة الطلب الرئيسي
            $departmentRequest->markAsIssued($currentUser->id);
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $departmentRequest->fresh(['items', 'issuer'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل في إصدار المواد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * الحصول على طلبات جاهزة للإصدار
     */
    public function getReadyForIssue()
    {
        $requests = DepartmentRequest::with(['department', 'requester', 'departmentRequestItems.item', 'departmentRequestItems.location'])
            ->whereIn('status', ['approved', 'partially_fulfilled'])
            ->where('is_issued', false)
            ->orderBy('status_updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }
    public function getReadyForIssueById($id)
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

            // جلب بيانات الطلب مع العلاقات المرتبطة
            $requests = DepartmentRequest::find($id);

            return response()->json([
                'success' => true,
                'data' => $requests
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'لم يتم العثور على الطلب المطلوب'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات الطلب: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * الحصول على تفاصيل طلب معين
     */
    public function getRequestDetails($id)
    {
        $request = DepartmentRequest::with([
            'department',
            'requester',
            'processor',
            'items.item',
            'items.issuer'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $request
        ]);
    }
}
