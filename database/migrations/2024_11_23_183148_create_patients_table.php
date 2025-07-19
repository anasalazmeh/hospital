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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('id_card')->unique();
            $table->string('full_name');
            $table->string('id_number')->unique()->nullable();
            $table->string('phone')->unique()->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('blood_type', ['A+', 'A-', 'AB+', "AB-", 'O+', "O-"])->nullable();
            $table->boolean('status')->default(true);
            $table->enum('gender', ['male', 'female']);
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->json('chronic_diseases')->nullable();// الأمراض المزمنة (يمكن تخزينها كمصفوفة JSON)
            $table->string('allergies')->nullable(); // الحساسيات
            $table->longText('current_medication')->nullable(); // الأدوية الحالية 
            $table->string('emergency_contact_phone')->nullable();// رقم احد من قريبينك
            $table->string('emergency_contact_name')->nullable();// اسم صاحب الرقم
            $table->enum('emergency_contact_relation', [
                'father',
                'mother',
                'brother',
                'sister',
                'son',
                'daughter',
                'husband',
                'wife',
                'uncle',
                'aunt',
                'cousin',
                'grandfather',
                'grandmother',
                'other'
            ])->nullable();
            $table->string('nationality')->nullable(); // الجنسية
            $table->enum('kidney_status', [
                'طبيعي',       // الكلى تعمل بشكل طبيعي
                'قصور بسيط',    // مرحلة مبكرة من القصور الكلوي
                'قصور متوسط',  // مرحلة متوسطة من القصور الكلوي
                'قصور شديد',   // مرحلة متقدمة من القصور الكلوي
                'فشل كلوي'     // الفشل الكلوي الكامل
            ])->default('طبيعي'); // حالة الكلية
                $table->text('dialysis_days')
          ->nullable(); // كم مرة يغسل كلاوي بالاسبوع
            $table->timestamps();
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
