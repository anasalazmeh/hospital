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
        Schema::create('intensive_care_patients', function (Blueprint $table) {
            $table->bigIncrements("id");
            $table->unsignedBigInteger("id_patients"); // مفتاح خارجي
            $table->string('id_card');
            $table->string('specialties');
            $table->string('health_condition');
            $table->string('room_number');
            $table->string('bed_number');
            $table->unsignedBigInteger('id_measurements_and_surgeries'); // مفتاح خارجي
            $table->longText('doctor_report')->nullable();
            $table->date('discharge_date')->nullable();
            $table->timestamps();
            // تعريف العلاقات
            $table->foreign('id_patients')->references('id')->on('patients')->onDelete('cascade');
            $table->foreign('id_measurements_and_surgeries')->references('id')->on('measurements_and_doses')->onDelete('cascade');
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
