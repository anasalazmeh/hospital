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
        Schema::create('users1', function (Blueprint $table) {
            $table->id(); // مفتاح أساسي تلقائي
            $table->string('name'); // اسم المستخدم
            $table->string('email')->unique(); // البريد الإلكتروني (فريد)
            $table->timestamp('email_verified_at')->nullable(); // التحقق من البريد
            $table->string('password'); // كلمة المرور
            $table->rememberToken(); // لتذكر المستخدم (الكوكيز)
            $table->timestamps(); // حقول created_at و updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users1');
    }
};
