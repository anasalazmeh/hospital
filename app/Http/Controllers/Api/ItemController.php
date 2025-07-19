<?php

namespace App\Http\Controllers\Api;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;

class ItemController extends Controller
{
    /**
     * عرض جميع الأصناف
     */
    public function index()
    {
        $items = Item::with('category')->get();
        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }

    /**
     * عرض صنف معين
     */
    public function show($id)
    {
        $item = Item::with('category')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'الصنف غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item
        ]);
    }

    /**
     * إنشاء صنف جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'unit' => 'required|string|max:50',
            'min_stock' => 'required|integer|min:0',
            'max_stock' => 'required|integer|min:0|gt:min_stock',
            'barcode' => 'nullable|string|unique:items,barcode'
        ], [
            'name.required' => 'اسم الصنف مطلوب',
            'category_id.required' => 'التصنيف مطلوب',
            'unit.required' => 'وحدة القياس مطلوبة',
            'max_stock.gt' => 'يجب أن يكون الحد الأقصى أكبر من الحد الأدنى'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $item = Item::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'تم إنشاء الصنف بنجاح'
        ], 201);
    }

    /**
     * تحديث صنف موجود
     */
    public function update(Request $request, $id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'الصنف غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'description' => 'nullable|string',
            'unit' => 'sometimes|string|max:50',
            'min_stock' => 'sometimes|integer|min:0',
            'max_stock' => 'sometimes|integer|min:0|gt:min_stock',
            'barcode' => 'nullable|string|unique:items,barcode,'.$id.',id'
        ], [
            'max_stock.gt' => 'يجب أن يكون الحد الأقصى أكبر من الحد الأدنى'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $item->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $item,
            'message' => 'تم تحديث الصنف بنجاح'
        ]);
    }

    /**
     * حذف صنف
     */
    public function destroy($id)
    {
        $item = Item::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'الصنف غير موجود'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الصنف بنجاح'
        ]);
    }

    /**
     * البحث عن أصناف حسب الباركود أو الاسم
     */
    public function search(Request $request)
    {
        $query = Item::query();

        if ($request->has('barcode')) {
            $query->where('barcode', $request->barcode);
        }

        if ($request->has('name')) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'data' => $items
        ]);
    }
}