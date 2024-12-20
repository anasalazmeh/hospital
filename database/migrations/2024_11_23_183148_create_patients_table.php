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
        Schema::create('patients', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string( 'id_card')->unique(); // رقم البطاقة (فريد)
            $table->json('full_name'); // الاسم الكامل
            $table->string('id_number'); //رقم الوطني
            $table->string('phone_number'); // الرقم
            $table->date('date_of_birth'); // المواليد
            $table->string('medical_info')->nullable(); // معلومات طبية
            $table->string('blood_type', 3)->nullable(); // زمرة الدم (مثل A+, B-)
            $table->boolean("card_status")->default(true); // حالة البطاقة
            $table->timestamps(); // created_at و updated_at
        });

    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};
