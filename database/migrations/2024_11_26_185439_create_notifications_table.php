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
        // Schema::create('notifications', function (Blueprint $table) {
        //     $table->id();  // رقم تعريف فريد للإشعار
        //     $table->string('id_card_medicalStaff');  // رقم تعريف طاقم طبي
        //     $table->unsignedBigInteger('id_patient');  // رقم تعريف المريض (من نوع unsignedBigInteger للتوافق مع id في intensive_care_patients)
        //     $table->text('notification_text');  // نص الإشعار
        //     $table->string('sender');  // اسم المرسل
        //     $table->string('receiver');  // اسم المستقبل
        //     $table->boolean('notification_status');  // حالة الإشعار
        //     $table->timestamps();  // عمودان لتواريخ الإنشاء والتحديث
        //     // ربط id_card_medicalStaff مع medical_staff
        //     $table->foreign('id_card_medicalStaff')->references('id_card_medicalStaff')->on('medical_staff')->onDelete('cascade');
        //     // ربط id_patient مع intensive_care_patients
        //     $table->foreign('id_patient')->references('id')->on('intensive_care_patients')->onDelete('cascade');
        // });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
