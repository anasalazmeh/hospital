<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Department;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Authentication and authorization check
            $currentUser = JWTAuth::parseToken()->authenticate();

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }

            // Base query
            $query = Department::orderBy('created_at', 'desc');

            // Apply status filter if provided
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Get all data (without pagination)
            $departments = $query->get();

            // Check if data exists
            if ($departments->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد بيانات متاحة حالياً',
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الأقسام بنجاح',
                'data' => $departments,
                'meta' => [
                    'total_departments' => $departments->count()
                ]
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى',
                'error' => $e->getMessage(),
                'data' => null
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في المصادقة',
                'error' => $e->getMessage(),
                'data' => null
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع أثناء جلب الأقسام',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    public function show($id)
    {
        try {
            // Authentication and authorization check
            $currentUser = JWTAuth::parseToken()->authenticate();

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }

            // Base query
            $department = Department::find($id);


            // Check if data exists
            if (!$department) {
                return response()->json([
                    'success' => false,
                    'message' => 'القسم غيرب موجود',
                ],404);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الأقسام بنجاح',
                'data' => $department,
            ]);

        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية الجلسة، يرجى تسجيل الدخول مرة أخرى',
                'error' => $e->getMessage(),
                'data' => null
            ], 401);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'خطأ في المصادقة',
                'error' => $e->getMessage(),
                'data' => null
            ], 401);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع أثناء جلب الأقسام',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function getById($id)
    {
        DB::beginTransaction();
        try {
            // Check current user
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                ], 403);
            }

            $department = Department::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات القسم بنجاح',
                'data' => $department
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطأ في جلب البيانات',
                'errors' => $e->errors()
            ], 422);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'القسم غير موجود'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات القسم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Check current user
            $currentUser = JWTAuth::parseToken()->authenticate();

            // Only admin can create departments
            if ($currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول'
                ], 403);
            }

            // Validate input
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:departments,name',
                'description' => 'nullable|string',
                'status' => 'sometimes|boolean'
            ]);

            // Create new department
            $department = Department::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'] ?? null,
                'status' => $validatedData['status'] ?? true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء القسم بنجاح',
                'data' => $department
            ], 201);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'errors' => $e->errors(),
                'message' => 'خطأ في البيانات المدخلة'
            ], 422);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ غير متوقع أثناء إنشاء القسم',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Check current user
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);

            // Only admin can update departments
            if ($currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول'
                ], 403);
            }

            // Find department to update
            $department = Department::findOrFail($id);

            // Validation rules
            $rules = [
                'name' => 'sometimes|required|string|max:255|unique:departments,name,' . $department->id,
                'description' => 'sometimes|nullable|string',
                'status' => 'sometimes|boolean'
            ];

            // Validate input
            $validatedData = $request->validate($rules);

            // Update department
            $department->update($validatedData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث بيانات القسم بنجاح',
                'data' => $department
            ]);

        } catch (ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'خطأ في البيانات المدخلة',
                'errors' => $e->errors()
            ], 422);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'القسم غير موجود'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تحديث القسم',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول للوصول إلى هذه الخدمة',
                    'data' => null
                ], 401);
            }

            $user = JWTAuth::authenticate($token);

            // Only admin can delete departments
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بهذه العملية'
                ], 403);
            }

            $department = Department::findOrFail($id);

            // Check if department has related data before deleting
            // You can add checks here if departments have related models

            $department->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف القسم بنجاح'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'القسم غير موجود'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف القسم: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleStatus($id)
    {
        DB::beginTransaction();
        try {
            $token = JWTAuth::getToken();
            $user = JWTAuth::authenticate($token);

            if (!$user || $user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بهذه العملية'
                ], 403);
            }

            $department = Department::findOrFail($id);
            $department->status = !$department->status;
            $department->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تغيير حالة القسم بنجاح',
                'data' => [
                    'id' => $department->id,
                    'name' => $department->name,
                    'new_status' => $department->status
                ]
            ]);

        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'القسم غير موجود'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير حالة القسم',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}