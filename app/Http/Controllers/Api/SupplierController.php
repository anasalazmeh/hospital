<?php

namespace App\Http\Controllers\Api;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
class SupplierController extends Controller
{
    /**
     * عرض جميع الموردين
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // البحث حسب الاسم إذا تم تقديمه
        if ($request->has('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        // التصفية حسب الحالة إذا تم تقديمها
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $suppliers = $query->get();

        return response()->json([
            'success' => true,
            'data' => $suppliers
        ]);
    }

    /**
     * عرض مورد معين
     */
    public function show($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'المورد غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $supplier
        ]);
    }

    /**
     * إنشاء مورد جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:suppliers,email',
            'contact_person' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean'
        ], [
            'name.required' => 'اسم المورد مطلوب',
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier = Supplier::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $supplier,
            'message' => 'تم إنشاء المورد بنجاح'
        ], 201);
    }

    /**
     * تحديث مورد موجود
     */
    public function update(Request $request, $id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'المورد غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:suppliers,email,'.$id,
            'contact_person' => 'nullable|string|max:255',
            'is_active' => 'sometimes|boolean'
        ], [
            'email.email' => 'البريد الإلكتروني غير صالح',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $supplier->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $supplier,
            'message' => 'تم تحديث المورد بنجاح'
        ]);
    }

    /**
     * حذف مورد
     */
    public function destroy($id)
    {
        $supplier = Supplier::find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'المورد غير موجود'
            ], 404);
        }

        // التحقق من عدم وجود مشتريات مرتبطة بهذا المورد
        // if ($supplier->purchases()->count() > 0) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'لا يمكن حذف المورد لأنه مرتبط بمشتريات'
        //     ], 400);
        // }

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المورد بنجاح'
        ]);
    }

    /**
     * الحصول على المشتريات من المورد
     */
    public function purchases($id)
    {
        $supplier = Supplier::with('purchases')->find($id);

        if (!$supplier) {
            return response()->json([
                'success' => false,
                'message' => 'المورد غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $supplier->purchases
        ]);
    }
}