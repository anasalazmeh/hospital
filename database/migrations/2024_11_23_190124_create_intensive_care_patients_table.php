<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIntensiveCarePatientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('intensive_care_patients', function (Blueprint $table) {

            $table->id(); // رقم البطاقة كمفتاح أساسي
            $table->unsignedBigInteger('id_card');
            $table->enum('patient_status', ['stable', 'critical', 'discharged'])->default('stable'); // حالة المريض
            $table->string('room_number'); // رقم الغرفة
            $table->string('bed_number'); // رقم التخت
            $table->text('measurements_and_surgeries')->nullable(); // قياسات وجراحات
            $table->text('doctor_report')->nullable(); // تقرير الدكتور
            $table->date('discharge_date')->nullable(); // تاريخ الخروج
            $table->timestamps(); // الحقول الافتراضية created_at و updated_at
            $table->foreign('id_card')->references('id_card')->on('patients')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('intensive_care_patients');
    }
}
