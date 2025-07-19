<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DashboardAccounts;
use App\Models\Patients;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Validation\Rule;
class ExistenceController extends Controller
{


    public function checkEmail(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);
            if (!$currentUser || $currentUser->is_active === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }
            $validated = $request->validate([
                'email' => ['required', 'email:rfc,dns', 'max:255']
            ], [
                'email.required' => 'حقل البريد الإلكتروني مطلوب',
                'email.email' => 'يجب إدخال بريد إلكتروني صالح',
                'email.max' => 'يجب ألا يتجاوز البريد الإلكتروني 255 حرفاً'
            ]);

            $exists = DashboardAccounts::where('email', $validated['email'])->exists();

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'البريد الإلكتروني موجود' : 'البريد الإلكتروني غير موجود'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
                'message' => 'تحقق من صحة البيانات المدخلة'
            ], 422); // HTTP 422 Unprocessable Entity
        }
    }

    public function checkIdCard(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);
            if (!$currentUser || $currentUser->is_active === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }
            $validated = $request->validate([
                'id_card' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\-]+$/']
            ], [
                'id_card.required' => 'حقل رقم الهوية مطلوب',
                'id_card.regex' => 'يجب أن يحتوي رقم الهوية على أرقام وحروف فقط',
                'id_card.max' => 'يجب ألا يتجاوز رقم الهوية 50 حرفاً'
            ]);

            $exists = DashboardAccounts::where('id_card', $validated['id_card'])->exists();
            if (!$exists) {
                $exists = Patients::where('id_card', $validated['id_card'])->exists();
            }

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'رقم الهوية موجود' : 'رقم الهوية غير موجود'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
                'message' => 'تحقق من صحة رقم الهوية'
            ], 422);
        }
    }

    public function checkPhone(Request $request)
    {
        try {
                        $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);
            if (!$currentUser || $currentUser->is_active === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }
            $validated = $request->validate([
                'phone' => ['required', 'string', 'regex:/^\+?[0-9]{8,15}$/']
            ], [
                'phone.required' => 'حقل الهاتف مطلوب',
                'phone.regex' => 'يجب إدخال رقم هاتف صحيح (8-15 رقم)'
            ]);

            $exists = DashboardAccounts::where('phone', $validated['phone'])->exists();
            if (!$exists) {
                $exists = Patients::where('phone', $validated['phone'])->exists();
            }

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'رقم الهاتف موجود' : 'رقم الهاتف غير موجود'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
                'message' => 'تحقق من صحة رقم الهاتف'
            ], 422);
        }
    }

    public function checkIdNumber(Request $request)
    {
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);
            if (!$currentUser || $currentUser->is_active === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول',
                    'data' => null
                ], 403);
            }
            $validated = $request->validate([
                'id_number' => ['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\-]+$/']
            ], [
                'id_number.required' => 'حقل الرقم التعريفي مطلوب',
                'id_number.regex' => 'يجب أن يحتوي الرقم التعريفي على أرقام وحروف فقط',
                'id_number.max' => 'يجب ألا يتجاوز الرقم التعريفي 50 حرفاً'
            ]);

            $exists = Patients::where('id_number', $validated['id_number'])->exists();

            return response()->json([
                'exists' => $exists,
                'message' => $exists ? 'الرقم التعريفي موجود' : 'الرقم التعريفي غير موجود'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => $e->errors(),
                'message' => 'تحقق من صحة الرقم التعريفي'
            ], 422);
        }
    }
}
