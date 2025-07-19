<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    /**
     * عرض جميع عناصر المخزون
     */
public function index(Request $request)
{
    // إنشاء الاستعلام الأساسي مع العلاقات
    $query = Stock::with(['item', 'location', 'item.category','supplier']);

    // تطبيق الفلاتر إذا وجدت
    if ($request->has('item_id')) {
        $query->where('item_id', $request->item_id);
    }

    if ($request->has('location_id')) {
        $query->where('location_id', $request->location_id);
    }

    if ($request->has('expiry_date')) {
        $query->where('expiry_date', $request->expiry_date);
    }

    // جلب جميع النتائج (بدون pagination)
    $stocks = $query->get();
    // تجميع النتائج حسب item_id
    $groupedStocks = $stocks->groupBy('item_id')->map(function ($items, $itemId) {
        $firstItem = $items->first();
        return [
            'item_id' => $itemId,
            'item_name' => $firstItem->item->name ?? '',
            'item_unit' => $firstItem->item->unit ?? '',
            'item_description' => $firstItem->item->description ?? '',
            'category' => $firstItem->item->category ? [
                'id' => $firstItem->item->category->id,
                'name' => $firstItem->item->category->name,
                // يمكن إضافة المزيد من خصائص الصنف إذا لزم الأمر
            ] : null,
            'total_quantity' => $items->sum('quantity'),
            'data' => $items->map(function ($stock) {
                return [
                    'id' => $stock->id,
                    'quantity' => $stock->quantity,
                    'location_id' => $stock->location_id,
                    'location_name' => $stock->location->name ?? '',
                    'supplier_id' => $stock->supplier_id,
                    'supplier_name' => $stock->supplier->name ?? '',
                    'manufacturing_date' => $stock->manufacturing_date,
                    'expiry_date' => $stock->expiry_date,
                    'batch_number' => $stock->batch_number,
                    'created_at' => $stock->created_at,
                    'updated_at' => $stock->updated_at
                ];
            })
        ];
    })->values(); // values() لإعادة تعيين المفاتيح لتبدأ من 0

    return response()->json($groupedStocks);
}

    /**
     * تخزين سجل مخزون جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:0',
            'location_id' => 'required|exists:locations,id',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stock = Stock::create($request->all());

        return response()->json([
            'message' => 'تم إنشاء سجل المخزون بنجاح',
            'data' => $stock->load(['item', 'location'])
        ], 201);
    }

    /**
     * عرض سجل مخزون محدد
     */
    public function show($id)
    {
        $stock = Stock::with(['item', 'location'])->findOrFail($id);
        return response()->json($stock);
    }

    /**
     * تحديث سجل المخزون
     */
    public function update(Request $request, $id)
    {
        $stock = Stock::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|exists:items,id',
            'quantity' => 'sometimes|integer|min:0',
            'location_id' => 'sometimes|exists:locations,id',
            'expiry_date' => 'nullable|date',
            'batch_number' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stock->update($request->all());

        return response()->json([
            'message' => 'تم تحديث سجل المخزون بنجاح',
            'data' => $stock->load(['item', 'location'])
        ]);
    }

    /**
     * حذف سجل المخزون
     */
    public function destroy($id)
    {
        $stock = Stock::findOrFail($id);
        $stock->delete();

        return response()->json([
            'message' => 'تم حذف سجل المخزون بنجاح'
        ]);
    }

    /**
     * البحث في المخزون
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $stocks = Stock::with(['item', 'location'])
            ->whereHas('item', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->query . '%')
                    ->orWhere('code', 'like', '%' . $request->query . '%');
            })
            ->orWhere('batch_number', 'like', '%' . $request->query . '%')
            ->paginate(15);

        return response()->json($stocks);
    }
}