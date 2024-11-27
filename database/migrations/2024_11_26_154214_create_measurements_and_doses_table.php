<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('measurements_and_doses', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary(); // المستخدم المرتبط
            $table->float('blood_pressure')->nullable(); // ضغط الدم
            $table->float('blood_sugar')->nullable(); // نسبة السكر
            $table->float('temperature')->nullable(); // الحرارة
            $table->string('blood_analysis')->nullable(); // تحليل الدم
            $table->float('urine_output')->nullable(); // نسبة التبول
            $table->string('doses')->nullable(); // الجرعات
            $table->float('oxygen_level')->nullable(); // معدل الأوكسجين
            $table->timestamp('measurement_time'); // وقت القياس (تاريخ ووقت كامل بالدقة)
            $table->timestamps(); // وقت الإنشاء والتحديث
            $table->foreign('id')->references('id')->on('intensive_care_patients')->onDelete('cascade');
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
