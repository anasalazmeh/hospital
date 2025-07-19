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
        Schema::create('surgery_measurements', function (Blueprint $table) {
            $table->id(); // المعرف الفريد للقياس

            // العلاقات مع جداول أخرى
            $table->foreignId('surgery_department_id')->constrained()->onDelete('cascade'); // ربط بقسم الجراحة
            $table->foreignId('doctor_id')->constrained("user_accounts", "id")->onDelete('cascade');
            // قياسات العناية بالجرح
            $table->dateTime('dressing_changed')->nullable(); // تاريخ آخر تغيير للضمادة
            $table->string('wound_condition', 255)->nullable(); // حالة الجرح (مثل: جيد، ملتهب، إلخ)
            $table->string('surgical_drains', 255)->nullable(); // وصف المصارف الجراحية إن وجدت

            // قياسات الألم والأدوية
            $table->integer('pain_level')->nullable()->comment('0-10 scale'); // مستوى الألم من 0 إلى 10
            $table->text('medication_doses')->nullable(); // جرعات الأدوية وتوقيتها

            // العلامات الحيوية
            $table->decimal('temperature', 5, 2)->nullable()->comment('in Celsius'); // درجة الحرارة بالسيليزيوس
            $table->string('blood_pressure', 20)->nullable()->comment('e.g. 120/80'); // ضغط الدم (مثال: 120/80)
            $table->integer('oxygen_level')->nullable()->comment('SpO2 percentage'); // مستوى الأكسجين في الدم (نسبة مئوية)
            $table->integer('heart_rate')->nullable()->comment('bpm'); // معدل ضربات القلب (ضربة/دقيقة)
            $table->integer('respiration_rate')->nullable()->comment('breaths per minute'); // معدل التنفس (نفس/دقيقة)
            $table->decimal('blood_sugar', 5, 2)->nullable()->comment('mg/dL'); // مستوى السكر في الدم (ملغم/ديسيلتر)

            // التواريخ التلقائية
            $table->timestamps(); // created_at و updated_at تلقائياً
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_measurements');
    }
};
