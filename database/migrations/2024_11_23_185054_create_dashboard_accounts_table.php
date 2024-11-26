<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDashboardAccountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dashboard_accounts', function (Blueprint $table) {
            $table->id(); // المفتاح الأساسي
            $table->string('full_name'); // الاسم الكامل
            $table->string('email')->unique(); // البريد الإلكتروني (فريد)
            $table->string('phone_number')->unique(); // رقم الموبايل
            $table->string('password')->uniqid(); // كلمة السر
            $table->enum('role', ['admin', 'card_creator', 'intensive_care'])->default('card_creator'); // الصلاحيات
            $table->timestamps(); // الحقول الافتراضية created_at و updated_at
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dashboard_accounts');
    }
}
