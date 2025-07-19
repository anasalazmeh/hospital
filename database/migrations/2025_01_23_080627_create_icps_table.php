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
        Schema::create('icps', function (Blueprint $table) {
            $table->unsignedBigInteger('intensive_care_patient_id');
            $table->unsignedBigInteger('specialty_id');
            $table->timestamps();

            // تعريف العلاقات
            $table->foreign('intensive_care_patient_id')
                  ->references('id')
                  ->on('intensive_care_patients')
                  ->onDelete('cascade');
            $table->foreign('specialty_id')
                  ->references('id')
                  ->on('specialties')
                  ->onDelete('cascade');
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intensive_care_patient_specialty');
    }
};
