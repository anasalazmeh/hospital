<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('nephrology_departments', function (Blueprint $table) {
            $table->id(); // المعرف الفريد للسجل (تلقائي)
            // ربط السجل بالمريض المعني
            $table->foreignId('patient_id')->constrained("patients","id")->onDelete('cascade');
            // التشخيص الأساسي للمريض (نص حر)
            $table->text('primary_diagnosis');
            // التشخيص الثانوي أو الإضافي إن وجد (نص حر، غير إلزامي)
            $table->text('secondary_diagnosis')->nullable();
            // حالة الكلى الحالية للمريض (قائمة محددة بالقيم)
            $table->enum('kidney_status', [
                'طبيعي',       // الكلى تعمل بشكل طبيعي
                'قصور بسيط',    // مرحلة مبكرة من القصور الكلوي
                'قصور متوسط',  // مرحلة متوسطة من القصور الكلوي
                'قصور شديد',   // مرحلة متقدمة من القصور الكلوي
                'فشل كلوي'     // الفشل الكلوي الكامل
            ]);
            // نوع الغسيل الكلوي إن كان مطلوبًا (نص حر)
            $table->string('dialysis_type')->nullable();
                            $table->text('dialysis_days')
          ->nullable(); // كم مرة يغسل كلاوي بالاسبوع
            // حقول التوقيت التلقائية (تاريخ الإنشاء والتحديث)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nephrology_departments');
    }
};
