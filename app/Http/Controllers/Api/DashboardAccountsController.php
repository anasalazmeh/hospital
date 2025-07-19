<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\DashboardAccounts;
use App\Models\Patients;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Mail\PasswordResetCodeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
class DashboardAccountsController extends Controller
{

    public function getById($id)
    {
        DB::beginTransaction();
        try {
            $token = JWTAuth::getToken();
            $currentUser = JWTAuth::authenticate($token);
            $user = DashboardAccounts::findOrFail($id);

            // إذا كان المستخدم الحالي هو نفسه صاحب الحساب وكان نشطًا
            if ($currentUser->id === $id && $currentUser->is_active === true) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم جلب بيانات المستخدم بنجاح',
                    'data' => $user->fresh()->load(['specialties', 'department']),
                ]);
            }

            // إذا كان المستخدم الحالي ليس مسؤولًا ولا رئيس قسم ولا نشطًا
            if ($currentUser->role !== 'admin' && $currentUser->role !== 'department_head' && $currentUser->is_active !== true) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بتعديل هذا الحساب',
                ], 403);
            }

            // إذا كان المستخدم الحالي رئيس قسم، نتحقق من أن الحساب المطلوب ينتمي لنفس القسم
            if ($currentUser->role === 'department_head' && $user->department_id !== $currentUser->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح لك بمشاهدة بيانات مستخدم من قسم آخر',
                ], 403);
            }

            // إذا كان المستخدم الحالي مسؤولًا أو رئيس قسم (وليس لديه مشكلة في الصلاحيات)
            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات المستخدم بنجاح',
                'data' => $user->fresh()->load(['specialties', 'department']),
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
                'message' => 'المستخدم غير موجود'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function index(Request $request)
    {
        try {
            $currentUser = JWTAuth::parseToken()->authenticate();

            if (!$currentUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب تسجيل الدخول أولاً',
                    'data' => null
                ], 401);
            }

            $query = DashboardAccounts::with(['specialties', 'department'])
                ->orderBy('created_at', 'desc');

            // إذا كان المستخدم رئيس قسم
            if ($currentUser->role === 'department_head') {
                $query->where('department_id', $currentUser->department_id);
            }
            // إذا كان المستخدم مدير (admin) لا نضيف أي شرط، يرى كل شيء
            elseif ($currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون مديراً أو رئيس قسم للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }

            // تطبيق فلترة إضافية حسب القسم إذا طلبها المدير
            // if ($currentUser->role === 'admin' && $request->has('department_id')) {
            //     $query->where('department_id', $request->department_id);
            // }

            // تطبيق فلترة الدور إذا طلبت
            // if ($request->has('role')) {
            //     $query->where('role', $request->role);
            // }

            $users = $query->get();

            if ($users->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد بيانات متاحة حالياً',
                    'data' => []
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $users,
                'meta' => [
                    'total_users' => $users->count()
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
                'message' => 'حدث خطأ غير متوقع أثناء جلب البيانات',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

public function register(Request $request)
{
    DB::beginTransaction();
    try {
        $currentUser = JWTAuth::parseToken()->authenticate();

        // التحقق من صلاحية المستخدم (المدير أو رئيس القسم فقط)
        if ($currentUser->role !== 'admin' && $currentUser->role !== 'department_head') {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بالوصول'
            ], 403);
        }

        $validatedData = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|unique:user_accounts,email',
            'phone' => 'required|string|unique:user_accounts,phone|min:10|max:15|regex:/^[0-9]+$/',
            'pin' => [
                'required',
                'string',
                'min:6',
                'max:6',
                'regex:/^[0-9]+$/',
                'confirmed'
            ],
            'pin_confirmation' => 'required|string|min:6|max:6',
            'id_card' => 'required|string|unique:user_accounts,id_card|max:20',
            'role' => 'required|string|in:admin,doctor,lab_technician,nurse,department_head,radiology_technician,icu_specialist,admission_head,accountant,warehouse_manager,warehouse_employee',
            'birth_date' => 'required|date',
            'gender' => 'required|in:male,female',
            'address' => 'nullable|string',
            'blood_type' => 'nullable|in:A+,A-,AB+,AB-,O+,O-',
            'specialties' => 'nullable|array',
            'specialties.*' => 'exists:specialties,id',
            'department_id' => 'nullable|exists:departments,id'
        ], [
            // رسائل الأخطاء المخصصة لكل حقل
            'full_name.required' => 'حقل الاسم الكامل مطلوب',
            'full_name.max' => 'الاسم الكامل يجب ألا يتجاوز 255 حرفًا',
            'email.required' => 'حقل البريد الإلكتروني مطلوب',
            'email.email' => 'يجب إدخال بريد إلكتروني صالح',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقًا',
            'phone.required' => 'حقل الهاتف مطلوب',
            'phone.unique' => 'رقم الهاتف مسجل مسبقًا',
            'phone.min' => 'رقم الهاتف يجب أن يكون على الأقل 10 أرقام',
            'phone.max' => 'رقم الهاتف يجب ألا يتجاوز 15 رقماً',
            'phone.regex' => 'رقم الهاتف يجب أن يحتوي على أرقام فقط',
            'pin.required' => 'حقل الرقم السري مطلوب',
            'pin.min' => 'الرقم السري يجب أن يكون 6 أرقام',
            'pin.max' => 'الرقم السري يجب أن يكون 6 أرقام',
            'pin.regex' => 'الرقم السري يجب أن يحتوي على أرقام فقط',
            'pin.confirmed' => 'تأكيد الرقم السري غير متطابق',
            'pin_confirmation.required' => 'حقل تأكيد الرقم السري مطلوب',
            'id_card.required' => 'حقل بطاقة الهوية مطلوب',
            'id_card.unique' => 'رقم بطاقة الهوية مسجل مسبقًا',
            'id_card.max' => 'رقم بطاقة الهوية يجب ألا يتجاوز 20 حرفًا',
            'role.required' => 'حقل الدور مطلوب',
            'role.in' => 'الدور المحدد غير صالح',
            'birth_date.required' => 'حقل تاريخ الميلاد مطلوب',
            'birth_date.date' => 'يجب إدخال تاريخ ميلاد صالح',
            'gender.required' => 'حقل الجنس مطلوب',
            'gender.in' => 'الجنس المحدد غير صالح',
            'address.string' => 'يجب أن يكون العنوان نصًا',
            'blood_type.in' => 'فصيلة الدم المحددة غير صالحة',
            'specialties.array' => 'يجب أن تكون التخصصات مصفوفة',
            'specialties.*.exists' => 'أحد التخصصات المحددة غير موجود',
            'department_id.exists' => 'القسم المحدد غير موجود'
        ]);

        // إذا كان المستخدم رئيس قسم
        if ($currentUser->role === 'department_head') {
            // نمنع إنشاء أي أدوار غير doctor أو nurse
            if (!in_array($validatedData['role'], ['doctor', 'nurse'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'يمكنك إنشاء حسابات لأطباء وممرضين في قسمك فقط'
                ], 403);
            }
            // تحقق إضافي للتأكد من أن القسم موجود
            if (!$validatedData['department_id']) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تعيين قسم للمستخدم الجديد'
                ], 422);
            }
            // نجبر استخدام قسم رئيس القسم
            if ($validatedData['department_id'] !== $currentUser->department_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'يمكنك إنشاء حسابات في قسمك فقط'
                ], 403);
            }
            $validatedData['department_id'] = $currentUser->department_id;
        }

        $birthDate = Carbon::parse($request->birth_date)->format('Y-m-d H:i:s');

        $newUser = DashboardAccounts::create([
            'full_name' => $validatedData['full_name'],
            'email' => $validatedData['email'],
            'phone' => $validatedData['phone'],
            'pin' => Hash::make($validatedData['pin']),
            'role' => $validatedData['role'],
            'id_card' => $validatedData['id_card'],
            'birth_date' => $birthDate,
            'gender' => $validatedData['gender'],
            'address' => $validatedData['address'],
            'blood_type' => $validatedData['blood_type'],
            'is_active' => true,
            'department_id' => $validatedData['department_id']
        ]);

        if (!empty($validatedData['specialties'])) {
            $newUser->specialties()->sync($validatedData['specialties']);
        }
        
        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم إنشاء المستخدم بنجاح'
        ], 201);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        // الحصول على أول رسالة خطأ فقط
        $errorMessage = $e->validator->errors()->first();
        return response()->json([
            'success' => false,
            'message' => $errorMessage
        ], 422);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ غير متوقع: ' . $e->getMessage()
        ], 500);
    }
}

public function update(Request $request, $id)
{
    DB::beginTransaction();
    try {
        $token = JWTAuth::getToken();
        $currentUser = JWTAuth::authenticate($token);
        $userToUpdate = DashboardAccounts::findOrFail($id);

        // التحقق من الصلاحيات
        if ($currentUser->role !== 'admin' && $currentUser->is_active !== true) {
            return response()->json([
                'success' => false,
                'message' => 'غير مصرح بتعديل هذا الحساب',
            ], 403);
        }

        // منع المستخدم من تعديل حسابه الخاص
        if ($currentUser->id === $userToUpdate->id) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تعديل بيانات حساب خاص بك',
            ], 403);
        }

        // تعريف قواعد التحقق
        $rules = [
            'full_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:user_accounts,email,' . $userToUpdate->id,
            'phone' => 'sometimes|required|string|unique:user_accounts,phone,' . $userToUpdate->id,
            'id_card' => 'sometimes|required|string|unique:user_accounts,id_card,' . $userToUpdate->id,
            'birth_date' => 'sometimes|required|date',
            'gender' => 'sometimes|required|in:male,female',
            'role' => 'sometimes|required|in:admin,doctor,lab_technician,nurse,department_head,radiology_technician,icu_specialist,admission_head,accountant,warehouse_manager,warehouse_employee',
            'blood_type' => 'sometimes|required|in:A+,A-,AB+,AB-,O+,O-',
            'is_active' => 'sometimes|boolean',
            'address' => 'sometimes|string',
            'specialties' => 'sometimes|nullable|array',
            'specialties.*' => 'exists:specialties,id',
            'department_id' => 'sometimes|nullable|exists:departments,id'
        ];

        // إزالة حقول الصلاحيات إذا لم يكن المستخدم مديراً
        if ($currentUser->role !== 'admin') {
            unset($rules['role'], $rules['is_active']);
        }

        // رسائل الأخطاء المخصصة
        $messages = [
            'full_name.required' => 'حقل الاسم الكامل مطلوب عند التعديل',
            'full_name.max' => 'الاسم الكامل يجب ألا يتجاوز 255 حرفاً',
            'email.required' => 'حقل البريد الإلكتروني مطلوب عند التعديل',
            'email.email' => 'يجب إدخال بريد إلكتروني صالح',
            'email.unique' => 'البريد الإلكتروني مسجل مسبقاً لمستخدم آخر',
            'phone.required' => 'حقل الهاتف مطلوب عند التعديل',
            'phone.unique' => 'رقم الهاتف مسجل مسبقاً لمستخدم آخر',
            'id_card.required' => 'حقل بطاقة الهوية مطلوب عند التعديل',
            'id_card.unique' => 'رقم بطاقة الهوية مسجل مسبقاً لمستخدم آخر',
            'birth_date.required' => 'حقل تاريخ الميلاد مطلوب عند التعديل',
            'birth_date.date' => 'يجب إدخال تاريخ ميلاد صالح',
            'gender.required' => 'حقل الجنس مطلوب عند التعديل',
            'gender.in' => 'قيمة الجنس غير صالحة',
            'role.required' => 'حقل الدور مطلوب عند التعديل',
            'role.in' => 'الدور المحدد غير صالح',
            'blood_type.required' => 'حقل فصيلة الدم مطلوب عند التعديل',
            'blood_type.in' => 'فصيلة الدم المحددة غير صالحة',
            'is_active.boolean' => 'حالة التنشيط يجب أن تكون true أو false',
            'address.string' => 'يجب أن يكون العنوان نصاً',
            'specialties.array' => 'يجب أن تكون التخصصات مصفوفة',
            'specialties.*.exists' => 'أحد التخصصات المحددة غير موجود',
            'department_id.exists' => 'القسم المحدد غير موجود'
        ];

        // تنفيذ التحقق من البيانات
        $validatedData = $request->validate($rules, $messages);

        // معالجة تاريخ الميلاد إذا كان موجوداً
        if (isset($validatedData['birth_date'])) {
            $validatedData['birth_date'] = Carbon::parse($validatedData['birth_date'])->format('Y-m-d');
        }

        // تحديث بيانات المستخدم
        $userToUpdate->update($validatedData);

        // تحديث التخصصات إذا كانت موجودة
        if (array_key_exists('specialties', $validatedData)) {
            $userToUpdate->specialties()->sync($validatedData['specialties']);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث بيانات المستخدم بنجاح',
            'data' => $userToUpdate->fresh()->load(['specialties', 'department'])
        ]);

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        // الحصول على أول رسالة خطأ فقط
        $errorMessage = $e->validator->errors()->first();
        return response()->json([
            'success' => false,
            'message' => $errorMessage
        ], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء التحديث: ' . $e->getMessage()
        ], 500);
    }
}

    public function login(Request $request)
    {
        DB::beginTransaction();
        try {
            // التحقق من صحة البيانات المدخلة
            $validator = Validator::make($request->all(), [
                'id_card' => 'required|string',
                'pin' => [
                    'required',      // مطلوب ولا يمكن أن يكون فارغًا
                    'string',        // يجب أن يكون نصًا (مع أن الأرقام ستكون كـ string)
                    'min:6',         // الحد الأدنى 6 أحرف
                    'max:6',         // الحد الأقصى 6 أحرف (أي بالضبط 6)
                    'regex:/^[0-9]+$/', // يجب أن يحتوي على أرقام فقط (0-9)
                ],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'خطأ في التحقق من البيانات',
                    'errors' => $validator->errors(),
                    'data' => null
                ], 422);
            }

            // البحث عن المستخدم
            // $user = DashboardAccounts::with(['specialties', 'IntensiveCarePatient'])
            //             ->where('id_card', $request->id_card)
            //             ->first();
            $user = DashboardAccounts::where('id_card', $request->id_card)->with('department')->first();

            // التحقق من وجود المستخدم وصحة كلمة المرور
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'البطاقة غير صحيحة',
                    'data' => null
                ], 401);
            }
            if (!Hash::check($request->pin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور غير صحيحة',
                    'data' => null
                ], 401);
            }

            // التحقق من حالة الحساب
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحساب غير مفعل، يرجى التواصل مع المسؤول',
                    'data' => null
                ], 403);
            }

            // إنشاء معرف الجهاز
            $deviceId = (string) md5($request->userAgent() . $request->ip());

            // إنشاء التوكن
            $token = JWTAuth::claims([
                'role' => $user->role,
                'id_card' => $user->id_card,
                'device_id' => $deviceId
            ])->fromUser($user);

            // تحديث معرف الجهاز الأخير
            $user->update(['last_device_id' => $deviceId]);
            DB::commit();
            // إرجاع الاستجابة الناجحة
            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الدخول بنجاح',
                'data' => [
                    'token' => $token,
                    'token_type' => 'bearer',
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'phone' => $user->phone,
                        'is_active' => $user->is_active,
                        'id_card' => $user->id_card,
                        'gender' => $user->gender,
                        'force_password_reset' => $user->force_password_reset,
                        'first_login' => $user->first_login,
                        'department_id' => $user->department_id,
                        'department' => $user->department
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء محاولة تسجيل الدخول',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function refreshToken(Request $request)
    {
        try {
            // 1. الحصول على التوكن الحالي
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تقديم رمز الدخول'
                ], 401);
            }

            // 2. محاولة تجديد التوكن
            // try {
            //     $newToken = JWTAuth::refresh($token);
            // } catch (TokenExpiredException $e) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'انتهت صلاحية رمز الدخول، يرجى تسجيل الدخول مرة أخرى'
            //     ], 401);
            // } catch (TokenInvalidException $e) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'رمز الدخول غير صالح'
            //     ], 401);
            // } catch (JWTException $e) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'خطأ في معالجة رمز الدخول'
            //     ], 500);
            // }

            // 3. الحصول على بيانات المستخدم من التوكن الجديد
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            // 4. التحقق من حالة الحساب
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحساب غير مفعل، يرجى التواصل مع المسؤول'
                ], 403);
            }
            if (!$user->id_card) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحساب id_card يرجى التواصل مع المسؤول'
                ], 403);
            }
            if (!$user->role) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحساب role يرجى التواصل مع المسؤول'
                ], 403);
            }
            if (!$user->last_device_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'الحساب role يرجى التواصل مع المسؤول'
                ], 403);
            }
            // if (!$user->department) {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'لا يوجد قسم تابع الو'
            //     ], 403);
            // }

            // 5. إضافة البيانات المطلوبة للتوكن الجديد
            $newTokenWithClaims = JWTAuth::claims([
                'role' => $user->role,
                'id_card' => $user->id_card,
                'device_id' => $user->last_device_id // استخدام القيمة من قاعدة البيانات بدلاً من التوكن القديم
            ])->fromUser($user);
            return response()->json([
                'success' => true,
                'message' => 'تم تجديد رمز الدخول بنجاح',
                'data' => [
                    'token' => $newTokenWithClaims,
                    'token_type' => 'bearer',
                    'user' => [
                        'id' => $user->id,
                        'full_name' => $user->full_name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'phone' => $user->phone,
                        'is_active' => $user->is_active,
                        'id_card' => $user->id_card,
                        'gender' => $user->gender,
                        'force_password_reset' => $user->force_password_reset,
                        'first_login' => $user->first_login,
                        'department_id' => $user->department_id,
                        'department' => $user->department,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث رمز المصادقة',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function changePassword(Request $request)
    {
        DB::beginTransaction();
        try {
            // التحقق من البيانات المدخلة
            $validator = Validator::make($request->all(), [
                'oldPin' => [
                    'required',
                    'string',
                    'min:6',
                    'max:6',
                    'regex:/^[0-9]+$/'
                ],
                'newPin' => [
                    'required',
                    'string',
                    'min:6',
                    'max:6',
                    'regex:/^[0-9]+$/',
                    'different:oldPin',
                    'confirmed'
                ],
                'newPin_confirmation' => 'required|string|min:6|max:6' // إضافة تأكيد كلمة المرور
            ], [
                'oldPin.required' => 'حقل كلمة المرور القديمة مطلوب',
                'oldPin.min' => 'يجب أن تتكون كلمة المرور القديمة من 6 أرقام',
                'oldPin.max' => 'يجب أن تتكون كلمة المرور القديمة من 6 أرقام',
                'oldPin.regex' => 'يجب أن تحتوي كلمة المرور القديمة على أرقام فقط',

                'newPin.required' => 'حقل كلمة المرور الجديدة مطلوب',
                'newPin.min' => 'يجب أن تتكون كلمة المرور الجديدة من 6 أرقام',
                'newPin.max' => 'يجب أن تتكون كلمة المرور الجديدة من 6 أرقام',
                'newPin.regex' => 'يجب أن تحتوي كلمة المرور الجديدة على أرقام فقط',
                'newPin.different' => 'يجب أن تكون كلمة المرور الجديدة مختلفة عن القديمة',
                'newPin.confirmed' => 'تأكيد كلمة المرور الجديدة غير متطابق',

                'newPin_confirmation.required' => 'حقل تأكيد كلمة المرور مطلوب',
                'newPin_confirmation.min' => 'يجب أن يتكون تأكيد كلمة المرور من 6 أرقام',
                'newPin_confirmation.max' => 'يجب أن يتكون تأكيد كلمة المرور من 6 أرقام'
            ]);

            if ($validator->fails()) {
                $firstError = $validator->errors()->first();

                return response()->json([
                    'success' => false,
                    'message' => $firstError, // إظهار أول رسالة خطأ في حقل message
                    'errors' => $validator->errors(),
                    'data' => null
                ], 422);
            }

            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'لم يتم تقديم رمز الدخول'
                ], 401);
            }
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'المستخدم غير موجود'
                ], 404);
            }

            if (!Hash::check($request->oldPin, $user->pin)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كلمة المرور الحالية غير صحيحة',
                    'data' => null
                ], 401);
            }

            if ($request->newPin === $request->oldPin) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب أن تكون كلمة المرور الجديدة مختلفة عن القديمة',
                    'errors' => ['newPin' => ['يجب أن تكون كلمة المرور الجديدة مختلفة عن القديمة']],
                    'data' => null
                ], 422);
            }
            $user->update([
                'pin' => Hash::make($request->newPin),
                'force_password_reset' => true,
                'first_login' => true
            ]);
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'تم تغيير كلمة المرور بنجاح',
                'data' => null
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تغيير كلمة المرور',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:user_accounts,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'البريد الإلكتروني غير صحيح أو غير مسجل',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = DashboardAccounts::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'البريد الإلكتروني غير مسجل في النظام'
                ], 404);
            }
            // إنشاء كود تحقق عشوائي (6 أرقام)
            $verificationCode = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            // حفظ كود التحقق في قاعدة البيانات
            $user->update([
                'verification_code' => $verificationCode,
                'verification_code_expires_at' => now()->addMinutes(10) // صلاحية 30 دقيقة
            ]);

            // إرسال الكود عبر البريد الإلكتروني
            Mail::to('anoosalazmeh@gmail.com')->send(new PasswordResetCodeMail($verificationCode));

            return response()->json([
                'success' => true,
                'message' => 'تم إرسال كود التحقق إلى بريدك الإلكتروني'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إرسال كود التحقق',
                'error' => $e->getMessage() // إظهار تفاصيل الخطأ
            ], 500);
        }
    }
    public function resetPassword(Request $request)
    {
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:user_accounts,email',
                'verification_code' => 'required|string|size:6',
                'newPin' => [
                    'required',
                    'string',
                    'min:6',
                    'max:6',
                    'regex:/^[0-9]+$/',
                    'confirmed'
                ]
            ]);
            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'فشل التحقق من البيانات',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = DashboardAccounts::where('email', $request->email)->first();

            // التحقق من صحة كود التحقق
            if ($user->verification_code !== $request->verification_code) {
                return response()->json([
                    'success' => false,
                    'message' => 'كود التحقق غير صحيح'
                ], 401);
            }
            // التحقق من صلاحية كود التحقق
            if (now()->gt($user->verification_code_expires_at)) {
                return response()->json([
                    'success' => false,
                    'message' => 'كود التحقق منتهي الصلاحية'
                ], 401);
            }
            // تحديث كلمة المرور
            $user->update([
                'pin' => Hash::make($request->newPin),
                'verification_code' => null,
                'verification_code_expires_at' => null,
                'force_password_reset' => true
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إعادة تعيين كلمة المرور بنجاح'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة تعيين كلمة المرور',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function logout(Request $request)
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token مطلوب',
                    'data' => null
                ], 400);
            }

            JWTAuth::invalidate($token);

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل الخروج بنجاح',
                'data' => null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تسجيل الخروج',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
    public function me()
    {
        try {
            $token = JWTAuth::getToken();

            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'يجب تسجيل الدخول للوصول إلى هذه الخدمة',
                    'data' => null
                ], 401);
            }

            $user = JWTAuth::user()->load(['specialties', 'intensiveCarePatient']);

            return response()->json([
                'success' => true,
                'message' => 'تم جلب بيانات المستخدم بنجاح',
                'data' => $user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب بيانات المستخدم',
                'error' => $e->getMessage(),
                'data' => null
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
            // لا يمكن للمستخدم حذف نفسه
            if ($user->id == $id) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكنك حذف حسابك الخاص'
                ], 400);
            }

            $userToDelete = DashboardAccounts::findOrFail($id);

            // التحقق من الصلاحيات (مثال: فقط المدير يمكنه الحذف)
            if ($user->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بهذه العملية'
                ], 403);
            }
            // if ($userToDelete->specialties()->count() > 0) { // إذا كان هناك علاقة مع جدول الأطباء
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'لا يمكن الحذف بسبب وجود اختصاصات مرتبطين بهذه حساب'
            //     ], 422);
            // }
            if ($userToDelete->intensiveCarePatient()->count() > 0) { // إذا كان هناك علاقة مع جدول الأطباء
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن الحذف بسبب وجود مرضه مرتبطين بهذه حساب'
                ], 422);
            }

            // حذف المستخدم
            $userToDelete->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الحساب بنجاح'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'الحساب غير موجود'
            ], 404);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الحساب: ' . $e->getMessage()
            ], 500);
        }
    }
    public function getDoctors(Request $request)
    {
        try {
            // التحقق من التوكن والمستخدم
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided',
                    'data' => null
                ], 401);
            }

            $currentUser = JWTAuth::authenticate($token);
            if (!$currentUser || !in_array($currentUser->role, ['admin', 'department_head'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون مسؤولاً أو رئيس قسم للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }
            // جلب الأطباء مع فلترة النشاط
            $query = DashboardAccounts::with(['specialties', 'department'])
                ->orderBy('created_at', 'desc');

            // إذا كان المستخدم رئيس قسم
            if ($currentUser->role === 'department_head') {
                $query->where('department_id', $currentUser->department_id);
            }
            // إذا كان المستخدم مدير (admin) لا نضيف أي شرط، يرى كل شيء
            elseif ($currentUser->role !== 'admin') {
                return response()->json([
                    'success' => false,
                    'message' => 'غير مصرح بالوصول: يجب أن تكون مديراً أو رئيس قسم للوصول إلى هذه البيانات',
                    'data' => null
                ], 403);
            }
            $query = $query->get();
            // تحقق من وجود بيانات
            if ($query->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'لا توجد بيانات متاحة حالياً',
                    'data' => []
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم جلب البيانات بنجاح',
                'data' => $query,
            ], 200);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate token',
                'error' => $e->getMessage(),
                'data' => null
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب البيانات',
                'error' => $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}
