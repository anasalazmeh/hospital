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
        Schema::create('intensive_care_patients', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_patients'); // مفتاح خارجي للإشارة إلى جدول المرضى
            $table->string('id_card');
            $table->string('health_condition');
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->foreignId('bed_id')->constrained('beds', 'id')->onDelete('cascade');
            $table->date('discharge_date')->nullable();
            $table->text('admission_reason')->nullable();// سبب دخول للعناية المشددة
            $table->unsignedBigInteger('attending_doctor_id')->nullable();
            $table->enum('severity_level', ['critical', 'serious', 'stable'])->default('critical');//مستوى الخطورة (حالة حرجة/شبه حرجة/مستقرة)
            $table->text('medical_notes')->nullable();// ملاحظات 
            $table->boolean('ventilator_dependency')->default(false); // هل يحتاج لتنفس صناعي؟
            $table->boolean('isolation_required')->default(false); // هل يحتاج عزل؟
            $table->timestamps();
            // تعريف العلاقات
            $table->foreign('id_patients')->references('id')->on('patients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intensive_care_patients');
    }
};
