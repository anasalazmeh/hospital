<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryMovement;
use App\Models\Stock;
use App\Models\Department;
use App\Models\StockTransaction;
use App\Models\DepartmentRequest;
use App\Models\DashboardAccounts;
use App\Models\Notification;
use App\Models\DepartmentRequestItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
class StockTransactionController extends Controller
{
    // أنواع الحركات المسموحة

    /**
     * تسجيل حركة جديدة في المخزون
     */
    public function store(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من أن المستخدم رئيس قسم
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب  عليك تسجيل دخول اولاً'
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'transaction_type' => 'required|in:purchase,return,damage,fulfillment',
            'quantity' => 'required|integer|min:1',
            'location_id' => 'required|exists:locations,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'manufacturing_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:manufacturing_date',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // إنشاء سجل الحركة
            $movement = StockTransaction::create([
                'item_id' => $request->item_id,
                'transaction_type' => $request->transaction_type,
                'quantity' => $request->quantity,
                'location_id' => $request->location_id,
                'supplier_id' => $request->supplier_id,
                'notes' => $request->notes,
                'user_id' => $currentUser->id,
            ]);

            $stock = Stock::create([
                'item_id' => $request->item_id,
                'transaction_type' => $request->transaction_type,
                'quantity' => $request->quantity,
                'location_id' => $request->location_id,
                'supplier_id' => $request->supplier_id,
                'manufacturing_date' => $request->manufacturing_date,
                'expiry_date' => $request->expiry_date,
                'batch_number' => $movement->id,
            ]);


            DB::commit();

            return response()->json([
                'message' => 'تم تسجيل الحركة بنجاح',
                'movement' => $movement,
                'current_stock' => $stock
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'فشل في تنفيذ العملية: ' . $e->getMessage()], 500);
        }
    }
    public function storefulfillment(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب عليك تسجيل دخول أولاً'
            ], 403);
        }
        // التحقق من الصلاحيات إن لزم الأمر
        if ($currentUser->role !== 'warehouse_manager') {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لرفض الطلبات'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'department_request_id' => 'required|exists:department_requests,id',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.department_request_item_id' => 'required|exists:department_request_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $successfulMovements = [];
            $failedItems = [];
            $alreadyFulfilledItems = [];
            $departmentRequestId = $request->department_request_id;
            $allItemsFullyFulfilled = true;
            $anyNewFulfillment = false;

            // تحميل الطلب مع عناصره
            $departmentRequest = DepartmentRequest::with('items')->findOrFail($departmentRequestId);

            // التحقق من حالة الطلب العامة
            if ($departmentRequest->status === 'approved') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الطلب تم تلبية كامل كميته مسبقاً',
                    'department_request_status' => $departmentRequest->status
                ], 400);
            }

            // التحقق من حالة الطلب المرفوض
            if ($departmentRequest->status === 'rejected') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تلبية طلب مرفوض',
                    'department_request_status' => $departmentRequest->status
                ], 400);
            }

            foreach ($request->items as $item) {
                $departmentRequestItem = $departmentRequest->items->find($item['department_request_item_id']);

                // حالة 1: الصنف تم تلبية كامل كميته مسبقاً
                if ($departmentRequestItem->approved_quantity >= $departmentRequestItem->quantity) {
                    $alreadyFulfilledItems[] = [
                        'item_id' => $item['item_id'],
                        'requested_quantity' => $item['quantity'],
                        'approved_quantity' => $departmentRequestItem->approved_quantity,
                        'message' => 'هذا الصنف تم تلبية كامل كميته مسبقاً'
                    ];
                    continue;
                }

                // حساب الكمية القابلة للتلبية
                $remainingQuantity = $departmentRequestItem->quantity - $departmentRequestItem->approved_quantity;
                $quantityToFulfill = min($item['quantity'], $remainingQuantity);

                // حالة 2: لا يوجد كمية كافية في المخزون
                $oldStock = Stock::where('item_id', $item['item_id'])
                    ->where('quantity', '>=', $quantityToFulfill)
                    ->first();

                if (!$oldStock) {
                    $availableStock = Stock::where('item_id', $item['item_id'])->sum('quantity');
                    $failedItems[] = [
                        'item_id' => $item['item_id'],
                        'requested_quantity' => $quantityToFulfill,
                        'available_quantity' => $availableStock,
                        'message' => 'لا يوجد كمية كافية في المخزون'
                    ];
                    $allItemsFullyFulfilled = false;
                    continue;
                }

                try {
                    // حالة 3: تلبية كاملة أو جزئية
                    $oldStock->update([
                        'quantity' => $oldStock->quantity - $quantityToFulfill
                    ]);

                    // تسجيل الحركة
                    $movement = StockTransaction::create([
                        'item_id' => $item['item_id'],
                        'transaction_type' => 'fulfillment',
                        'quantity' => $quantityToFulfill,
                        'department_id' => $departmentRequest->department_id,
                        'notes' => $request->notes ?? 'تنفيذ طلب قسم رقم: ' . $departmentRequestId,
                        'user_id' => $currentUser->id,
                        'supplier_id' => $oldStock->supplier_id,
                        'location_id' => $oldStock->location_id,
                        'department_request_id' => $departmentRequestId
                    ]);

                    // تحديث الكمية المعتمدة
                    $newApprovedQuantity = $departmentRequestItem->approved_quantity + $quantityToFulfill;
                    if ($departmentRequest->is_issued === false && $departmentRequest->status === "partially_fulfilled") {
                        $quantity= $departmentRequestItem->delivered_quantity +$quantityToFulfill;
                        $departmentRequestItem->update([
                            'approved_quantity' => $newApprovedQuantity,
                            'delivered_quantity' => $quantity,
                            'batch_number' => $oldStock->batch_number,
                            'location_id' => $oldStock->location_id,
                        ]);
                    } else {
                        $departmentRequest->update(['is_issued' =>false]);
                        $departmentRequestItem->update([
                            'approved_quantity' => $newApprovedQuantity,
                            'delivered_quantity' => $quantityToFulfill,
                            'batch_number' => $oldStock->batch_number,
                            'location_id' => $oldStock->location_id,
                        ]);
                    }

                    $successfulMovements[] = [
                        'movement' => $movement,
                        'item_id' => $item['item_id'],
                        'fulfilled_quantity' => $quantityToFulfill,
                        'remaining_quantity' => $departmentRequestItem->quantity - $newApprovedQuantity,
                        'status' => $newApprovedQuantity >= $departmentRequestItem->quantity
                            ? 'fully_fulfilled'
                            : 'partially_fulfilled'
                    ];

                    $anyNewFulfillment = true;

                    // إذا لم يتم تلبية الكمية بالكامل
                    if ($newApprovedQuantity < $departmentRequestItem->quantity) {
                        $allItemsFullyFulfilled = false;
                    }

                } catch (\Exception $e) {
                    $failedItems[] = [
                        'item_id' => $item['item_id'],
                        'requested_quantity' => $quantityToFulfill,
                        'message' => 'فشل في معالجة الصنف: ' . $e->getMessage()
                    ];
                    $allItemsFullyFulfilled = false;
                    continue;
                }
            }

            // تحديث حالة الطلب
            if ($anyNewFulfillment) {
                $allItems = $departmentRequest->items()->get(); // تأكيد جلب العناصر

                $fullyFulfilledCount = $allItems->filter(function ($item) {
                    return $item->approved_quantity >= $item->quantity;
                })->count();

                $partiallyFulfilledCount = $allItems->filter(function ($item) {
                    return $item->approved_quantity > 0 && $item->approved_quantity < $item->quantity;
                })->count();
                $warehouseManagers = DashboardAccounts::where("role", "warehouse_employee")->get();
                $notifications = [];
                foreach ($warehouseManagers as $manager) {
                    $notifications[] = [
                        'sender_account_id' => 1,
                        'receiver_account_id' => $manager->id,
                        'title' => 'طلب جديد يحتاج توصيل',
                        'message' => 'يوجد طلب جديد برقم #' . $departmentRequest->id . ' يحتاج إلى توصيل إلى القسم' . $departmentRequest->department_id,
                        'priority' => 'normal', // أو 'urgent' أو 'critical'
                        'link' => '/wearehouse-requests/' . $departmentRequest->id, // الرابط المرتبط بالإشعار
                    ];
                }
                Notification::insert($notifications);
                if ($fullyFulfilledCount === $allItems->count()) {
                    $departmentRequest->updateStatus('approved', $currentUser->id);
                    Notification::create([
                        'sender_account_id' => $currentUser->id,
                        'receiver_account_id' => $departmentRequest->requested_by,
                        'title' => 'من المستودع',
                        'message' => 'تمت الموافقة على طلبك بشكل كامل بنجاح ',
                        'priority' => 'normal', // أو 'urgent' أو 'critical'
                        'link' => "/department-requests", // الرابط المرتبط بالإشعار
                    ]);
                } elseif ($fullyFulfilledCount > 0 || $partiallyFulfilledCount > 0) {
                    $departmentRequest->updateStatus('partially_fulfilled', $currentUser->id);
                    Notification::create([
                        'sender_account_id' => $currentUser->id,
                        'receiver_account_id' => $departmentRequest->requested_by,
                        'title' => 'من المستودع',
                        'message' => 'تمت الموافقة على طلبك بشكل كامل بنجاح ',
                        'priority' => 'normal', // أو 'urgent' أو 'critical'
                        'link' => "/department-requests", // الرابط المرتبط بالإشعار
                    ]);
                }

                $departmentRequest->save(); // حفظ التغييرات بشكل صريح
            }

            DB::commit();

            // إعداد الرد المناسب
            $response = [
                'success' => $anyNewFulfillment,
                'message' => $this->generateResponseMessage(
                        $anyNewFulfillment,
                        $allItemsFullyFulfilled,
                        count($successfulMovements),
                        count($failedItems),
                        count($alreadyFulfilledItems)
                    ),
                'data' => [
                    'successful_movements' => $successfulMovements,
                    'failed_items' => $failedItems,
                    'already_fulfilled_items' => $alreadyFulfilledItems,
                    'department_request_status' => $departmentRequest->fresh()->status,
                    'fulfillment_summary' => $this->generateFulfillmentSummary($departmentRequest)
                ]
            ];

            return response()->json($response, $this->determineStatusCode(
                $anyNewFulfillment,
                $allItemsFullyFulfilled,
                count($failedItems)
            ));

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل في تنفيذ العملية: ' . $e->getMessage(),
                'error_details' => config('app.debug') ? $e->getTrace() : null
            ], 500);
        }
    }

    // دالة مساعدة لتوليد رسالة الرد المناسبة
    private function generateResponseMessage($anyNewFulfillment, $allFullyFulfilled, $successCount, $failCount, $alreadyFulfilledCount)
    {
        if (!$anyNewFulfillment) {
            if ($alreadyFulfilledCount > 0) {
                return 'جميع الأصناف المطلوبة تم تلبية كامل كميتها مسبقاً';
            }
            return 'لم يتم تلبية أي صنف بسبب عدم توفر الكمية';
        }

        $messages = [];

        if ($allFullyFulfilled) {
            $messages[] = 'تم تلبية جميع الأصناف بالكامل';
        } else {
            if ($successCount > 0) {
                $messages[] = "تم تلبية $successCount صنف بنجاح";
            }
            if ($failCount > 0) {
                $messages[] = "فشل في تلبية $failCount صنف";
            }
            if ($alreadyFulfilledCount > 0) {
                $messages[] = "$alreadyFulfilledCount صنف تم تلبية كميته مسبقاً";
            }
        }

        return implode(' - ', $messages);
    }

    // دالة مساعدة لتحديد كود الحالة HTTP المناسب
    private function determineStatusCode($anyNewFulfillment, $allFullyFulfilled, $failCount)
    {
        if (!$anyNewFulfillment) {
            return $failCount > 0 ? 400 : 200;
        }

        if ($allFullyFulfilled) {
            return 201;
        }

        return 207; // Multi-Status
    }

    // دالة مساعدة لتوليد ملخص التلبية
    private function generateFulfillmentSummary($departmentRequest)
    {
        $items = $departmentRequest->items;

        return [
            'total_items' => $items->count(),
            'fully_fulfilled_items' => $items->where('approved_quantity', '>=', DB::raw('quantity'))->count(),
            'partially_fulfilled_items' => $items->where('approved_quantity', '>', 0)
                ->where('approved_quantity', '<', DB::raw('quantity'))
                ->count(),
            'unfulfilled_items' => $items->where('approved_quantity', '<=', 0)->count(),
            'completion_percentage' => round(
                    $items->sum('approved_quantity') / $items->sum('quantity') * 100,
                    2
                )
        ];
    }
    public function returnStock(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من أن المستخدم مسجل دخوله
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب عليك تسجيل دخول أولاً'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id', // معرف سجل المخزون المراد ترجيع جزء منه
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'batch_number' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // الحصول على سجل المخزون الأصلي
            $originalStock = Stock::where("batch_number", $request->batch_number)->where("location_id", $request->location_id)->first();

            // التحقق من أن الكمية المراد ترجيعها متاحة
            if ($request->quantity > $originalStock->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المراد ترجيعها أكبر من الكمية المتاحة'
                ], 400);
            }

            // إنشاء سجل الحركة للترجيع
            $movement = StockTransaction::create([
                'item_id' => $originalStock->item_id,
                'transaction_type' => 'return',
                'quantity' => $request->quantity,
                'department_id' => $originalStock->department_id,
                'location_id' => $originalStock->location_id,
                'supplier_id' => $originalStock->supplier_id,
                'notes' => $request->notes ?? 'ترجيع كمية من المخزون',
                'user_id' => $currentUser->id,
            ]);

            // تحديث الكمية في السجل الأصلي
            $originalStock->quantity -= $request->quantity;
            $originalStock->save();

            // يمكنك هنا إضافة سجل جديد للمخزون المرتجع إذا كان لديك مكان مختلف للتخزين
            // أو يمكنك التعامل معه حسب متطلبات نظامك

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تمت عملية الترجيع بنجاح',
                'movement' => $movement,
                'updated_stock' => $originalStock
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل في عملية الترجيع: ' . $e->getMessage()
            ], 500);
        }
    }
    public function damageStock(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        // التحقق من أن المستخدم مسجل دخوله
        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب عليك تسجيل دخول أولاً'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'notes' => 'nullable|string',
            'batch_number' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // الحصول على سجل المخزون الأصلي
            $originalStock = Stock::where("batch_number", $request->batch_number)
                ->where("location_id", $request->location_id)
                ->first();

            // التحقق من أن الكمية المراد تسجيلها كتالف متاحة
            if ($request->quantity > $originalStock->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكمية المراد تسجيلها كتالف أكبر من الكمية المتاحة'
                ], 400);
            }

            // إنشاء سجل الحركة للتلف
            $movement = StockTransaction::create([
                'item_id' => $originalStock->item_id,
                'transaction_type' => 'damage',
                'quantity' => $request->quantity,
                'supplier_id' => $originalStock->supplier_id,
                'location_id' => $originalStock->location_id,
                'notes' => $request->notes ?? 'تسجيل كمية تالفة من المخزون - السبب: ' . $request->damage_reason,
                'user_id' => $currentUser->id,
                'damage_reason' => $request->damage_reason, // حفظ سبب التلف
            ]);

            // تحديث الكمية في السجل الأصلي
            $originalStock->quantity -= $request->quantity;
            $originalStock->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تمت عملية تسجيل التلف بنجاح',
                'movement' => $movement,
                'updated_stock' => $originalStock
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل في عملية تسجيل التلف: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حساب الكمية الجديدة بناءً على نوع الحركة
     */

    /**
     * الحصول على سجل الحركات
     */
    public function index(Request $request)
    {
        $data = StockTransaction::with('item', 'user', 'location', 'supplier', 'department')->get();

        return response()->json([
            'success' => true,
            'message' => 'تم جلب البيانات بنجاح',
            'data' => $data
        ]);
    }
    /**
     * رفض طلب قسم
     * 
     * @param Request $request
     * @param int $id معرف الطلب
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectRequest(Request $request)
    {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);

        if (!$currentUser) {
            return response()->json([
                'success' => false,
                'message' => 'يجب عليك تسجيل دخول أولاً'
            ], 403);
        }

        // التحقق من الصلاحيات إن لزم الأمر
        if ($currentUser->role !== 'warehouse_manager') {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لرفض الطلبات'
            ], 403);
        }
        $validator = Validator::make($request->all(), [
            'department_request_id' => 'required|exists:department_requests,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        try {
            DB::beginTransaction();

            $departmentRequest = DepartmentRequest::findOrFail($request->department_request_id);

            // لا يمكن رفض طلب مرفوض مسبقاً
            if ($departmentRequest->status === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا الطلب مرفوض مسبقاً'
                ], 400);
            }

            // لا يمكن رفض طلب تم تلبية كامل كميته
            if ($departmentRequest->status === 'approved' || $departmentRequest->status === 'partially_fulfilled') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن رفض طلب تم تلبية كامل كميته'
                ], 400);
            }

            // تحديث حالة الطلب إلى مرفوض
            $departmentRequest->updateStatus('rejected', $currentUser->id);

            // يمكنك إضافة أي عمليات إضافية هنا مثل:
            // - إرسال إشعار للمستخدم
            // - تسجيل الحركة في السجل

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم رفض الطلب بنجاح',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'فشل في رفض الطلب: ' . $e->getMessage()
            ], 500);
        }
    }
    /**
     * الحصول على تفاصيل حركة محددة
    //  */
    // public function show($id)
    // {
    //     $movement = InventoryMovement::with(['stock.item', 'department', 'user'])->findOrFail($id);
    //     return response()->json($movement);
    // }
}