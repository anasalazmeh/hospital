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
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->enum('role', ['admin', 'doctor', 'lab_technician', 'nurse', 'department_head', 'radiology_technician', 'icu_specialist', 'admission_head', 'accountant','warehouse_manager','warehouse_employee'])->default('admission_head');
            $table->enum('gender', ['male', 'female']);
            $table->string('id_card')->unique();
            $table->date('birth_date'); // التعديل هنا
            $table->string('pin'); // يجب تشفيرها في الكود قبل التخزين
            $table->string('last_device_id')->nullable();
            $table->string('verification_code')->nullable();
            $table->string('verification_code_expires_at')->nullable();
            $table->boolean('force_password_reset')->default(false);
            $table->boolean('first_login')->default(false);
            $table->string('phone');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('blood_type', ['A+', 'A-', 'AB+', "AB-", 'O+', "O-"])->nullable();
            $table->foreignId('department_id')->nullable()->constrained("departments", "id")->onDelete('cascade');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};