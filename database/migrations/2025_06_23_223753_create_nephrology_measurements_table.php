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
        Schema::create('nephrology_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nephrology_department_id')->constrained("nephrology_departments", "id")->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained("user_accounts", "id")->onDelete('cascade');
            // القياسات الأساسية
            $table->decimal('weight', 5, 2)->comment('الوزن (كجم)');
            $table->decimal('height', 5, 2)->nullable()->comment('الطول (سم)');
            $table->string('blood_pressure')->nullable()->comment('ضغط الدم (مثال: 120/80)');
            $table->integer('pulse')->comment('معدل النبض')->nullable();
            $table->decimal('temperature', 4, 1)->nullable()->comment('درجة الحرارة');

            // وظائف الكلى
            $table->decimal('creatinine', 5, 2)->nullable()->comment('الكرياتينين (mg/dL)');
            $table->decimal('urea', 5, 2)->nullable()->comment('اليوريا (mg/dL)');
            $table->decimal('gfr', 5, 2)->nullable()->comment('معدل الترشيح الكبيبي (mL/min)');
            $table->decimal('sodium', 5, 2)->nullable()->comment('الصوديوم (mEq/L)');
            $table->decimal('potassium', 5, 2)->nullable()->comment('البوتاسيوم (mEq/L)');
            // معلومات إضافية
            $table->text('notes')->nullable()->comment('ملاحظات الطبيب');

            // نوع قياسات
            $table->enum('type_mmeasurements', [
                'قبل الغسيل',
                'بعد الغسيل',
            ]);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nephrology_measurements');
    }
};
