<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PatientsController;
use App\Http\Controllers\Api\IntensiveCarePatients;
use App\Http\Controllers\Api\DashboardAccountsController;
use App\Http\Controllers\Api\SpecialtyController;
use App\Http\Controllers\Api\ExistenceController;
use App\Http\Controllers\Api\AnalysesController;
use App\Http\Controllers\Api\RadiologiesController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\MeasurementAndDoseController;
use App\Http\Controllers\Api\DoctorReportController;
use App\Http\Controllers\Api\ObstetricsGynecologyController;
use App\Http\Controllers\Api\GynecologicalExaminationController;
use App\Http\Controllers\Api\InternalDepartmentController;
use App\Http\Controllers\Api\InternalDepartmentMeasurementController;
use App\Http\Controllers\Api\NephrologyDepartmentController;
use App\Http\Controllers\Api\NephrologyMeasurementController;
use App\Http\Controllers\Api\SurgeryDepartmentController;
use App\Http\Controllers\Api\SurgeryMeasurementController;
use App\Http\Controllers\Api\PediatricController;
use App\Http\Controllers\Api\PediatricMeasurementController;
use App\Http\Controllers\Api\PatientDischargeController;
use App\Http\Controllers\Api\PrescriptionController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\BedController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\LocationController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockTransactionController;
use App\Http\Controllers\Api\DepartmentRequestController;
use App\Http\Controllers\Api\WarehouseController;
use App\Http\Controllers\Api\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/test', function () {
  return response()->json(['message' => 'API is working']);
});

//جدول المرضى 
Route::get('/patients', [PatientsController::class, 'index']);
Route::get('/patient/{id}', [PatientsController::class, 'show']);
Route::get('/patientByIdCard/{id_card}', [PatientsController::class, 'showByIdCard']);
Route::post('/patients', [PatientsController::class, 'store']);
Route::post('/anonymous-patient', [PatientsController::class, 'createAnonymousPatient']);
Route::put('/patient/update/{id}', [PatientsController::class, 'update']);
Route::put('/patient/updateById_card', [PatientsController::class, 'updateById_card']);
Route::patch('/patient/update/{id}', [PatientsController::class, 'update']);
Route::patch('/patient/updateById_card', [PatientsController::class, 'updateById_card']);
Route::delete('/patient/{id}', [PatientsController::class, 'destroy']);

//جدول مرضى العنايةالمشددة مع القياسات والجرعات 
Route::post('/IntensiveCarePatients', [IntensiveCarePatients::class, 'store']);

Route::get('/IntensiveCarePatients', [IntensiveCarePatients::class, 'get']);
Route::get('/IntensiveCarePatients/{id}', [IntensiveCarePatients::class, 'getById']);
Route::get('/IntensiveCarePatientsActive', [IntensiveCarePatients::class, 'getActive']);

Route::put('/IntensiveCarePatients/{id}', [IntensiveCarePatients::class, 'update']);
Route::put('/updateMeasurementAndDose/{id}', [IntensiveCarePatients::class, 'updateMeasurementAndDose']);
Route::put('/updateDischargeDate/{id}', [IntensiveCarePatients::class, 'updateDischargeDate']);
Route::put('/updateDoctorReport/{id}', [IntensiveCarePatients::class, 'updateDoctorReport']);

Route::patch('/IntensiveCarePatients/{id}', [IntensiveCarePatients::class, 'update']);
Route::patch('/updateMeasurementAndDose/{id}', [IntensiveCarePatients::class, 'updateMeasurementAndDose']);
Route::patch('/updateDischargeDate/{id}', [IntensiveCarePatients::class, 'updateDischargeDate']);
Route::patch('/updateDoctorReport/{id}', [IntensiveCarePatients::class, 'updateDoctorReport']);

// عرض جميع الحسابات
Route::get('/auth/users', [DashboardAccountsController::class, 'index']);
Route::get('/auth/user/{id}', [DashboardAccountsController::class, 'getById']);
Route::get('/auth/meUsers', [DashboardAccountsController::class, 'me']);
Route::post('/auth/register', [DashboardAccountsController::class, 'register']);
Route::post('/auth/login', [DashboardAccountsController::class, 'login']);  // تسجيل الدخول
Route::post('/auth/refresh-token', [DashboardAccountsController::class, 'refreshToken']);
Route::post('/auth/logout', [DashboardAccountsController::class, 'logout']);
Route::post('/auth/change-password', [DashboardAccountsController::class, 'changePassword']);
Route::post('/auth/forgotPassword', [DashboardAccountsController::class, 'forgotPassword']);
Route::post('/auth/resetPassword', [DashboardAccountsController::class, 'resetPassword']);
Route::put('/auth/update/{id}', [DashboardAccountsController::class, 'update']);
Route::patch('/auth/update/{id}', [DashboardAccountsController::class, 'update']);
Route::delete('/auth/delete/{id}', [DashboardAccountsController::class, 'destroy']);
Route::get('/doctors', [DashboardAccountsController::class, 'getDoctors']);

// Route::prefix('auth')->group(function () {
//   Route::post('register', [DashboardAccountsController::class, 'register'])->name('auth.register');
//   Route::post('login', [DashboardAccountsController::class, 'login'])->name('auth.login');
//   Route::post('logout', [DashboardAccountsController::class, 'logout'])->middleware('auth:api')->name('auth.logout');
//   Route::post('refresh', [DashboardAccountsController::class, 'refreshToken'])->name('auth.refresh');
//   Route::post('me', [DashboardAccountsController::class, 'me'])->middleware('auth:api')->name('auth.me');
// });
// Route::prefix('api/v1')->middleware('api')->group(function () {
//   // Authentication Routes


//   // Password Management
//   Route::post('password/change', [DashboardAccountsController::class, 'changePassword'])->middleware('auth:api')->name('password.change');

//   // User Management
//   Route::apiResource('users', DashboardAccountsController::class)->middleware(['auth:api', 'role:admin'])->except(['create', 'edit']);

//   // Additional custom user routes
//   Route::prefix('users')->middleware(['auth:api', 'role:admin'])->group(function () {
//       Route::delete('{id}/force-delete', [DashboardAccountsController::class, 'forceDelete'])->name('users.force-delete');
//       Route::post('{id}/restore', [DashboardAccountsController::class, 'restore'])->name('users.restore');
//   });
// });
//جدول التخصصات 
// عرض جميع التخصصات
Route::get('/specialties', [SpecialtyController::class, 'index']);

// إضافة تخصص جديد
Route::post('/specialties', [SpecialtyController::class, 'store']);

// عرض تخصص معين
Route::get('/specialty/{id}', [SpecialtyController::class, 'show']);

// تعديل تخصص معين
Route::put('/specialties/{id}', [SpecialtyController::class, 'update']);
Route::patch('/specialties/{id}', [SpecialtyController::class, 'update']);

// حذف تخصص معين
Route::delete('/specialties/{id}', [SpecialtyController::class, 'destroy']);
// حفظ صور وحماية الملقات
Route::get('/storage/patients/{filename}', function ($filename) {
  $path = storage_path('app/public/patients/' . $filename);

  if (!File::exists($path)) {
    abort(404);
  }

  return response()->file($path);
})->middleware('auth:api');

//منشان تشيك
Route::get('/check-email', [ExistenceController::class, 'checkEmail']);
Route::get('/check-id-card', [ExistenceController::class, 'checkIdCard']);
Route::get('/check-phone', [ExistenceController::class, 'checkPhone']);
Route::get('/check-id-number', [ExistenceController::class, 'checkIdNumber']);

//تحليلات مخبرية
Route::get('/analyses', [AnalysesController::class, 'index']);
Route::post('/analys', [AnalysesController::class, 'store']);
Route::get('/analys/{id}', [AnalysesController::class, 'show']);
Route::put('/analys/{id}', [AnalysesController::class, 'update']);
Route::patch('/analys/{id}', [AnalysesController::class, 'update']);
// Route::delete('/analys/{id}', [AnalysesController::class, 'destroy']);

// صور أشعة
Route::get('/radiologies', [RadiologiesController::class, 'index']);
Route::post('/radiology', [RadiologiesController::class, 'store']);
Route::get('/radiology/{id}', [RadiologiesController::class, 'show']);
Route::put('/radiology/{id}', [RadiologiesController::class, 'update']);
Route::patch('/radiology/{id}', [RadiologiesController::class, 'update']);
// Route::delete('/analys/{id}', [AnalysesController::class, 'destroy']);

// صور أشعة
Route::get('/radiologies', [RadiologiesController::class, 'index']);
Route::post('/radiology', [RadiologiesController::class, 'store']);
Route::get('/radiology/{id}', [RadiologiesController::class, 'show']);
Route::put('/radiology/{id}', [RadiologiesController::class, 'update']);
Route::patch('/radiology/{id}', [RadiologiesController::class, 'update']);
// Route::delete('/analys/{id}', [AnalysesController::class, 'destroy']);

// قياسات وجرعات 
Route::get('/measurementAndDose', [MeasurementAndDoseController::class, 'index']);
Route::post('/measurementAndDose', [MeasurementAndDoseController::class, 'store']);
Route::get('/measurementAndDose/{id}', [MeasurementAndDoseController::class, 'show']);
Route::put('/measurementAndDose/{id}', [MeasurementAndDoseController::class, 'update']);
Route::patch('/measurementAndDose/{id}', [MeasurementAndDoseController::class, 'update']);
// Route::delete('/analys/{id}', [AnalysesController::class, 'destroy']);


// مرضى النسائية وتوليد
Route::get('/obstetrics-gynecology', [ObstetricsGynecologyController::class, 'index']);
Route::get('/obstetrics-gynecology/{id}', [ObstetricsGynecologyController::class, 'show']);
Route::get('/obstetrics-gynecology/getId/{id_card}', [ObstetricsGynecologyController::class, 'getId']);
Route::post('/obstetrics-gynecology', [ObstetricsGynecologyController::class, 'store']);
Route::put('/obstetrics-gynecology/{id}', [ObstetricsGynecologyController::class, 'update']);

// قياسات مرضلا النسائية وتوليد
Route::get('/gynecological-examinations', [GynecologicalExaminationController::class, 'index']);
Route::get('/gynecological-examinations/{id}', [GynecologicalExaminationController::class, 'show']);
Route::post('/gynecological-examinations', [GynecologicalExaminationController::class, 'store']);
Route::put('/gynecological-examinations/{id}', [GynecologicalExaminationController::class, 'update']);

// قسم الداخلية 
Route::get('/internal-departments', [InternalDepartmentController::class, 'index']);
Route::get('/internal-departments/{id}', [InternalDepartmentController::class, 'show']);
Route::get('/internal-departments/getId/{id_card}', [InternalDepartmentController::class, 'getId']);
Route::post('/internal-departments', [InternalDepartmentController::class, 'store']);
Route::put('/internal-departments/{id}', [InternalDepartmentController::class, 'update']);
Route::patch('/internal-departments/{id}', [InternalDepartmentController::class, 'update']);
Route::delete('/internal-departments/{id}', [InternalDepartmentController::class, 'update']);

// قياسات تبع قسم الداخلية 
Route::get('/internal-measurement', [InternalDepartmentMeasurementController::class, 'index']);
Route::get('/internal-measurement/{id}', [InternalDepartmentMeasurementController::class, 'show']);
Route::post('/internal-measurement', [InternalDepartmentMeasurementController::class, 'store']);
Route::put('/internal-measurement/{id}', [InternalDepartmentMeasurementController::class, 'update']);
Route::patch('/internal-measurement/{id}', [InternalDepartmentMeasurementController::class, 'update']);
Route::delete('/internal-measurement/{id}', [InternalDepartmentMeasurementController::class, 'update']);

// قسم الكلي 
Route::get('/nephrology-departments', [NephrologyDepartmentController::class, 'index']);
Route::get('/nephrology-departments/{id}', [NephrologyDepartmentController::class, 'show']);
Route::get('/nephrology-departments/getId/{id_card}', [NephrologyDepartmentController::class, 'getId']);
Route::post('/nephrology-departments', [NephrologyDepartmentController::class, 'store']);
Route::put('/nephrology-departments/{id}', [NephrologyDepartmentController::class, 'update']);
Route::patch('/nephrology-departments/{id}', [NephrologyDepartmentController::class, 'update']);
Route::delete('/nephrology-departments/{id}', [NephrologyDepartmentController::class, 'update']);

// قياسات تبع قسم الكلي 
Route::get('/nephrology-measurement', [NephrologyMeasurementController::class, 'index']);
Route::get('/nephrology-measurement/{id}', [NephrologyMeasurementController::class, 'show']);
Route::post('/nephrology-measurement', [NephrologyMeasurementController::class, 'store']);
Route::put('/nephrology-measurement/{id}', [NephrologyMeasurementController::class, 'update']);
Route::patch('/nephrology-measurement/{id}', [NephrologyMeasurementController::class, 'update']);
Route::delete('/nephrology-measurement/{id}', [NephrologyMeasurementController::class, 'update']);

// قسم الجراحة 
Route::get('/surgery-departments', [SurgeryDepartmentController::class, 'index']);
Route::get('/surgery-departments/{id}', [SurgeryDepartmentController::class, 'show']);
Route::get('/surgery-departments/getId/{id_card}', [SurgeryDepartmentController::class, 'getId']);
Route::post('/surgery-departments', [SurgeryDepartmentController::class, 'store']);
Route::put('/surgery-departments/{id}', [SurgeryDepartmentController::class, 'update']);
Route::patch('/surgery-departments/{id}', [SurgeryDepartmentController::class, 'update']);
Route::delete('/surgery-departments/{id}', [SurgeryDepartmentController::class, 'update']);

// قياسات تبع قسم الجراحة 
Route::get('/surgery-measurement', [SurgeryMeasurementController::class, 'index']);
Route::get('/surgery-measurement/{id}', [SurgeryMeasurementController::class, 'show']);
Route::post('/surgery-measurement', [SurgeryMeasurementController::class, 'store']);
Route::put('/surgery-measurement/{id}', [SurgeryMeasurementController::class, 'update']);
Route::patch('/surgery-measurement/{id}', [SurgeryMeasurementController::class, 'update']);
Route::delete('/surgery-measurement/{id}', [SurgeryMeasurementController::class, 'update']);

// قسم الاطفال 
Route::get('/pediatric-departments', [PediatricController::class, 'index']);
Route::get('/pediatric-departments/{id}', [PediatricController::class, 'show']);
Route::get('/pediatric-departments/getId/{id_card}', [PediatricController::class, 'getId']);
Route::post('/pediatric-departments', [PediatricController::class, 'store']);
Route::put('/pediatric-departments/{id}', [PediatricController::class, 'update']);
Route::patch('/pediatric-departments/{id}', [PediatricController::class, 'update']);
Route::delete('/pediatric-departments/{id}', [PediatricController::class, 'update']);

// قياسات تبع قسم الاطفال 
Route::get('/pediatric-measurement', [PediatricMeasurementController::class, 'index']);
Route::get('/pediatric-measurement/{id}', [PediatricMeasurementController::class, 'show']);
Route::post('/pediatric-measurement', [PediatricMeasurementController::class, 'store']);
Route::put('/pediatric-measurement/{id}', [PediatricMeasurementController::class, 'update']);
Route::patch('/pediatric-measurement/{id}', [PediatricMeasurementController::class, 'update']);
Route::delete('/pediatric-measurement/{id}', [PediatricMeasurementController::class, 'update']);

// تقرير دكتور لمرضى العناية
Route::post('/doctor-reports', [DoctorReportController::class, 'store']);
Route::put('/doctor-reports/{id}', [DoctorReportController::class, 'update']);
Route::patch('/doctor-reports/{id}', [DoctorReportController::class, 'update']);

// اخراج المريض من قسم
Route::put('/patient_discharge', [PatientDischargeController::class, 'discharge']);
Route::patch('/patient_discharge', [PatientDischargeController::class, 'discharge']);

// اخراج المريض من قسم
Route::post('/prescription', [PrescriptionController::class, 'store']);
Route::put('/prescription', [PrescriptionController::class, 'update']);
Route::patch('/prescription', [PrescriptionController::class, 'update']);

// الأقسام  
Route::get('/department', [DepartmentController::class, 'index']);
Route::get('/department/{id}', [DepartmentController::class, 'show']);
Route::post('/department', [DepartmentController::class, 'store']);
Route::put('/department/{id}', [DepartmentController::class, 'update']);
Route::patch('/department/{id}', [DepartmentController::class, 'update']);
// Route::delete('/department/{id}', [DepartmentController::class, 'update']);

// غراف
Route::prefix('rooms')->group(function () {
  Route::get('/', [RoomController::class, 'index']); // عرض جميع الغرف
  Route::get('/available', [RoomController::class, 'availableRooms']); // الغرف المتاحة
  Route::get('/occupied', [RoomController::class, 'occupiedRooms']); // الغرف المشغولة
  Route::post('/', [RoomController::class, 'store']); // إنشاء غرفة جديدة
  Route::get('/{id}', [RoomController::class, 'show']); // عرض غرفة محددة
  Route::put('/{id}', [RoomController::class, 'update']); // تحديث غرفة
  Route::patch('/{id}', [RoomController::class, 'update']); // تحديث غرفة
  // Route::delete('/{id}', [RoomController::class, 'destroy']); // حذف غرفة
});

// الأسرة
Route::prefix('beds')->group(function () {
  Route::get('/', [BedController::class, 'index']); // عرض جميع الغرف
  Route::get('/available', [BedController::class, 'availableRooms']); // الغرف المتاحة
  Route::get('/occupied', [BedController::class, 'occupiedRooms']); // الغرف المشغولة
  Route::get('/bedsByRoom', [BedController::class, 'bedsByRoom']); // تحديث غرفة
  Route::post('/', [BedController::class, 'store']); // إنشاء غرفة جديدة
  Route::get('/{id}', [BedController::class, 'show']); // عرض غرفة محددة
  Route::put('/{id}', [BedController::class, 'update']); // تحديث غرفة
  Route::patch('/{id}', [BedController::class, 'update']); // تحديث غرفة
  // Route::delete('/{id}', [RoomController::class, 'destroy']); // حذف غرفة
});


// مستودع

// الصنف
Route::get('/categories', [CategoryController::class, 'index']);
Route::get('/categories/{id}', [CategoryController::class, 'show']);
Route::post('/categories', [CategoryController::class, 'store']);
Route::put('/categories/{id}', [CategoryController::class, 'update']);
Route::patch('/categories/{id}', [CategoryController::class, 'update']);
Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

// منتج
Route::get('/item', [ItemController::class, 'index']);
Route::get('/item/{id}', [ItemController::class, 'show']);
Route::post('/item', [ItemController::class, 'store']);
Route::put('/item/{id}', [ItemController::class, 'update']);
Route::patch('/item/{id}', [ItemController::class, 'update']);
Route::delete('/item/{id}', [ItemController::class, 'destroy']);

// موردين
Route::get('/suppliers', [SupplierController::class, 'index']);
Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
Route::post('/suppliers', [SupplierController::class, 'store']);
Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
Route::patch('/suppliers/{id}', [SupplierController::class, 'update']);
Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

// المواقع تخزين مواد
Route::get('/locations', [LocationController::class, 'index']);
Route::get('/locations/{id}', [LocationController::class, 'show']);
Route::post('/locations', [LocationController::class, 'store']);
Route::put('/locations/{id}', [LocationController::class, 'update']);
Route::patch('/locations/{id}', [LocationController::class, 'update']);
Route::delete('/locations/{id}', [LocationController::class, 'destroy']);

//   مخزون
Route::get('/stock', [StockController::class, 'index']);
Route::get('/stock/{id}', [StockController::class, 'show']);
Route::post('/stock', [StockController::class, 'store']);
Route::put('/stock/{id}', [StockController::class, 'update']);
Route::patch('/stock/{id}', [StockController::class, 'update']);
Route::delete('/stock/{id}', [StockController::class, 'destroy']);

//   حركات
Route::get('/stock_transactions', [StockTransactionController::class, 'index']);
Route::get('/stock_transactions/{id}', [StockTransactionController::class, 'show']);
Route::post('/stock_transactions', [StockTransactionController::class, 'store']);
Route::post('/stock_transactions/return', [StockTransactionController::class, 'returnStock']);
Route::post('/stock_transactions/damage', [StockTransactionController::class, 'damageStock']);
Route::post('/stock_transactions/storefulfillment', [StockTransactionController::class, 'storefulfillment']);
Route::post('/stock_transactions/rejectRequest', [StockTransactionController::class, 'rejectRequest']);
// Route::put('/stock_transactions/{id}', [StockTransactionController::class, 'update']);
// Route::patch('/stock_transactions/{id}', [StockTransactionController::class, 'update']);

// طلبات الأقسام
Route::get('/department-requests', [DepartmentRequestController::class, 'index']);
Route::post('/department-requests', [DepartmentRequestController::class, 'store']);
Route::get('/department-requests/{id}', [DepartmentRequestController::class, 'show']);
Route::patch('/department-requests/{id}/status', [DepartmentRequestController::class, 'updateStatus']);

// طلبات مستودع
Route::get('/warehouse-requests', [DepartmentRequestController::class, 'getReadyForIssue']);
Route::get('/warehouse-requests/{id}', [DepartmentRequestController::class, 'getReadyForIssueById']);
Route::post('/warehouse-requests/issueRequest/{id}', [DepartmentRequestController::class, 'issueRequest']);

// لصفحة تحكم لمدير المستودع
Route::get('/warehouse/summary', [WarehouseController::class, 'getSummary']);
// بيانات الرسم الدائري (GET)
// مثال: /api/warehouse/pie-data
Route::get('/warehouse/pie-data', [WarehouseController::class, 'getPieData']);
// بيانات الرسم الخطي (GET)
// مثال: /api/warehouse/line-data
Route::get('/warehouse/line-data', [WarehouseController::class, 'getLineData']);
// تفاصيل فئة محددة (GET مع باراميتر)
// مثال: /api/warehouse/details/شراء
Route::get('/warehouse/details/{category}', [WarehouseController::class, 'getDetails'])->where('category', '.*'); // تقبل أي حرفيات;

// الحصول على جميع إشعارات المستخدم
Route::get('/notifications', [NotificationController::class, 'index']);
// إنشاء إشعار جديد
Route::post('/notifications', [NotificationController::class, 'store']);
// تحديث حالة القراءة للإشعار
Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
// تحديث جميع اشعارات حالة القراءة
Route::patch('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
// حذف إشعار
// Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

// نسحة احتياطية 
Route::prefix('backup')->group(function () {
  Route::post('/create', [BackupController::class, 'createBackup']);
  Route::post('/schedule', [BackupController::class, 'scheduleBackup']);
});
// Route::get('/storage/analyses/{file}', function ($file) {
//     $path = storage_path('app/public/analyses/' . $file);
//     return response()->file($path, [
//         'Content-Type' => 'application/pdf',
//         'Access-Control-Allow-Origin' => '*'
//     ]);
// });