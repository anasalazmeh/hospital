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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();  // رقم تعريف فريد للإشعار
            $table->unsignedBigInteger('id_card_medicalStaff');  // رقم تعريف المريض
            $table->unsignedBigInteger('id_patient');  // رقم تعريف طاقم طبي
            $table->text('notification_text');  // نص الإشعار
            $table->string('sender');  // اسم المرسل
            $table->string('receiver');  // اسم المستقبل
            $table->bool('notification_status');  // حالة الإشعار
            $table->timestamps();  // عمودان لتواريخ الإنشاء والتحديث
            $table->foreign('id_card_medicalStaff')->references('id_card_medicalStaff')->on('medical_staff')->onDelete('cascade');
            $table->foreign('id_patient')->references('id')->on('intensive_care_patients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
