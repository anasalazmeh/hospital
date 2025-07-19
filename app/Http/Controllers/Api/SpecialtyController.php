<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Specialties;

class SpecialtyController extends Controller
{
    public function index()
    {
        $specialties = Specialties::all();
        return response()->json($specialties);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string|max:255',
        ]);

        $specialty = Specialties::create($request->all());
        return response()->json($specialty, 201);
    }

    /**
     * عرض تخصص معين.
     */
    public function show($id)
    {
        $specialty = Specialties::findOrFail($id);
        return response()->json($specialty);
    }

    /**
     * تعديل تخصص معين.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $specialty = Specialties::findOrFail($id);
        $specialty->update($request->all());
        return response()->json($specialty);
    }

    /**
     * حذف تخصص معين.
     */
    public function destroy($id)
    {
        $specialty = Specialties::findOrFail($id);
        if ($specialty->dectors()->count() > 0) { // إذا كان هناك علاقة مع جدول الأطباء
            return response()->json([
                'message' => 'لا يمكن الحذف بسبب وجود أطباء مرتبطين بهذا التخصص'
            ], 422);
        }
        $specialty->delete();
        return response()->json(null, 204);
    }
    //
}
