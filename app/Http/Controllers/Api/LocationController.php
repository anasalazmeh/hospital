<?php

namespace App\Http\Controllers\Api;

use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
class LocationController extends Controller
{
    /**
     * عرض جميع المواقع
     */
    public function index(Request $request)
    {
        $query = Location::query();

        // البحث حسب الاسم إذا تم تقديمه
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $locations = $query->get();

        return response()->json([
            'success' => true,
            'data' => $locations
        ]);
    }

    /**
     * عرض موقع معين
     */
    public function show($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $location
        ]);
    }

    /**
     * إنشاء موقع جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:locations,name',
            'description' => 'nullable|string'
        ], [
            'name.required' => 'اسم الموقع مطلوب',
            'name.unique' => 'اسم الموقع مسجل مسبقاً'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $location = Location::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $location,
            'message' => 'تم إنشاء الموقع بنجاح'
        ], 201);
    }

    /**
     * تحديث موقع موجود
     */
    public function update(Request $request, $id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:locations,name,' . $id . ',id',
            'description' => 'nullable|string'
        ], [
            'name.required' => 'اسم الموقع مطلوب',
            'name.unique' => 'اسم الموقع مسجل مسبقاً'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $location->update($request->all());

        return response()->json([
            'success' => true,
            'data' => $location,
            'message' => 'تم تحديث الموقع بنجاح'
        ]);
    }

    /**
     * حذف موقع
     */
    public function destroy($id)
    {
        $location = Location::find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود'
            ], 404);
        }

        // التحقق من عدم وجود أصناف مرتبطة بهذا الموقع
        // if ($location->items()->count() > 0) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'لا يمكن حذف الموقع لأنه مرتبط بأصناف'
        //     ], 400);
        // }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الموقع بنجاح'
        ]);
    }

    /**
     * الحصول على الأصناف في موقع معين
     */
    public function items($id)
    {
        $location = Location::with('items')->find($id);

        if (!$location) {
            return response()->json([
                'success' => false,
                'message' => 'الموقع غير موجود'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $location->items
        ]);
    }
}