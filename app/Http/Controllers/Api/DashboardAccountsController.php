<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\DashboardAccounts;

class DashboardAccountsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

            try {
                $accounts = DashboardAccounts::all();
                return response()->json([
                    'message' => 'Accounts retrieved successfully',
                    'data' => $accounts
                ], 200);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'An error occurred while fetching accounts',
                    'error' => $e->getMessage()
                ], 500);
            }
 
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    { $validator = Validator::make($request->all(), [
        'full_name' => 'required|string|max:255',
        'email' => 'required|string|email|unique:dashboard_accounts',
        'phone_number' => 'required|string|unique:dashboard_accounts',
        'password' => 'required|string|min:8',
        'role' => 'nullable|in:admin,card_creator,intensive_care',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $account = DashboardAccounts::create([
            'full_name' => $request->full_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => bcrypt($request->password), // تشفير كلمة المرور
            'role' => $request->role ?? 'card_creator',
        ]);

        return response()->json([
            'message' => 'Account created successfully',
            'data' => $account
        ], 201);
    } catch (\Exception $e) {
        return response()->json([
            'message' => 'An error occurred while creating the account',
            'error' => $e->getMessage()
        ], 500);
    }
}

public function login(Request $request)
{
    // التحقق من البيانات المدخلة
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'message' => 'Validation error',
            'errors' => $validator->errors()
        ], 422);
    }

    // محاولة توثيق المستخدم
    if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
        $user = Auth::user();  // الحصول على المستخدم الحالي
        $token = $user->createToken('API Token')->plainTextToken;  // إنشاء توكن API
        
        return response()->json([
            'message' => 'Login successful',
            'token' => $token  // إرجاع التوكن
        ], 200);
    }

    // في حال فشل التوثيق
    return response()->json([
        'message' => 'Invalid credentials'
    ], 401);
}
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete($id)
    {
        try {
            $account = DashboardAccounts::find($id);  // البحث عن الحساب

            if (!$account) {
                return response()->json([
                    'message' => 'Account not found'
                ], 404);
            }

            $account->delete();  // حذف الحساب

            return response()->json([
                'message' => 'Account deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
