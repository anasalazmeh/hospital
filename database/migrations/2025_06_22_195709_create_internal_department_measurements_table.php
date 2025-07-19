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
        Schema::create('internal_department_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_id')->constrained("internal_departments", "id")->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained("user_accounts", "id")->onDelete('cascade');
            $table->decimal('temperature', 5, 2)->nullable()->comment('درجة الحرارة');
            $table->string('blood_pressure')->nullable()->comment('ضغط الدم');
            $table->decimal('oxygen_level', 5, 2)->nullable()->comment('مستوى الأكسجين');
            $table->integer('heart_rate')->nullable()->comment('نبض القلب');
            $table->integer('respiration_rate')->nullable()->comment('معدل التنفس');
            $table->decimal('blood_sugar', 5, 2)->nullable()->comment('سكر الدم');
            $table->decimal('weight', 6, 2)->nullable()->comment('الوزن');
            $table->text('blood_tests')->nullable()->comment('تحاليل الدم');
            $table->text('medication_doses')->nullable()->comment('الجرعات الدوائية');
            $table->text('medical_procedures')->nullable()->comment('الإجراءات الطبية');
            $table->text('ecg')->nullable()->comment('تخطيط القلب');
            $table->text('kidney_functions')->nullable()->comment('وظائف الكلى');
            $table->text('liver_functions')->nullable()->comment('وظائف الكبد');
            $table->text('blood_count')->nullable()->comment('تعداد الدم');
            $table->text('new_measurement')->nullable()->comment('قياس جديد');
            $table->text('notes')->nullable()->comment('ملاحظات إضافية');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_department_measurements');
    }
};
