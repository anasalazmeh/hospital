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
            $table->bigIncrements('id'); // المستخدم المرتبط
            $table->longText('blood_pressure')->nullable(); // ضغط الدم
            $table->longText('blood_sugar')->nullable(); // نسبة السكر
            $table->longText('temperature')->nullable(); // الحرارة
            $table->longText('blood_analysis')->nullable(); // تحليل الدم
            $table->longText('urine_output')->nullable(); // نسبة التبول
            $table->longText('doses')->nullable(); // الجرعات
            $table->longText('oxygen_level')->nullable(); // معدل الأوكسجين
            $table->timestamps(); // وقت الإنشاء والتحديث
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
