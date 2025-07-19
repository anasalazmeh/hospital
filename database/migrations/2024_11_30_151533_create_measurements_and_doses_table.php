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
        Schema::create('measurements_and_doses', function (Blueprint $table) {
            // =========== الحقول الأساسية ===========
            $table->id(); // المعرف الفريد للقياس (تلقائي)

            // =========== القياسات الحيوية (Vital Signs) ===========
            $table->decimal('temperature', 4, 2)->nullable(); // درجة حرارة الجسم (وحدة: °C أو °F)
            $table->string('blood_pressure')->nullable(); // ضغط الدم (مثال: "120/80 mmHg")
            $table->integer('oxygen_level')->nullable(); // تشبع الأكسجين (وحدة: % SpO₂)
            $table->decimal('blood_sugar', 5, 2)->nullable(); // مستوى السكر في الدم (وحدة: mg/dL أو mmol/L)
            $table->integer('heart_rate')->nullable(); // معدل ضربات القلب (وحدة: نبضة/دقيقة)
            $table->integer('respiratory_rate')->nullable(); // معدل التنفس (وحدة: نفس/دقيقة)
            $table->integer('serone')->nullable(); // سيرون

            // =========== قياسات السوائل والبول ===========
            $table->decimal('urine_output', 8, 2)->nullable(); // إخراج البول (وحدة: mL/hour)
            $table->decimal('cvp', 5, 2)->nullable(); // الضغط الوريدي المركزي (وحدة: cmH₂O)

            // =========== الأدوية والعلاجات ===========
            $table->longText('doses')->nullable(); // الجرعات الدوائية (يمكن تخزينها كنص أو JSON مثال: {"الدواء": "Insulin", "الجرعة": "5 units"})

            // =========== الفحوصات الخاصة ===========
            $table->text('echocardiography_results')->nullable(); // نتائج فحص الإيكو للقلب 
            $table->text('echo_findings_results')->nullable(); // نتائج فحص الإيكو المري 
            $table->boolean('requires_dialysis')->default(false); // غسيل الكلى
            $table->longText('additional_procedures')->nullable(); // إجراءات إضافية

            // =========== الحقول التلقائية ===========
            // في جزء منفصل بعد إنشاء الجدول
            $table->unsignedBigInteger('icup_id');
            $table->unsignedBigInteger('user_account_id');
            $table->timestamps(); // created_at و updated_at (تسجيل وقت الإنشاء/التحديث)

            // تعريف العلاقات
            $table->foreign('icup_id')
                ->references('id')
                ->on('intensive_care_patients')
                ->onDelete('cascade');
            $table->foreign('user_account_id')
                ->references('id')
                ->on('user_accounts')
                ->onDelete('cascade');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('measurements_and_doses');
    }
};
