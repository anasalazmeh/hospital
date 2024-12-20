<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMedicalStaffTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Schema::create('medical_staff', function (Blueprint $table) {

        //     $table->id('id')->primary(); // رقم
        //     $table->string('id_card_medicalStaff'); // رقم البطافة
        //     $table->string('full_name'); // الاسم الكامل
        //     $table->enum('role', ['nurse', 'doctor', 'head_of_department'])->default('nurse'); // الصفة
        //     $table->string('department')->nullable(); // القسم (يمكن أن يكون فارغًا)
        //     $table->string('pin')->unique(); // رمز التعريف الشخصي (PIN)
        //     $table->string('phone_number')->nullable(); // رقم الموبايل
        //     $table->timestamps(); // الحقول الافتراضية created_at و updated_at
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medical_staff');
    }
}
