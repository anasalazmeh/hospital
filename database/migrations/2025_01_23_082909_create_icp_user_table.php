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
        Schema::create('icp_user', function (Blueprint $table) {
            $table->unsignedBigInteger('intensive_care_patients_id');
            $table->unsignedBigInteger('user_account_id');
            $table->timestamps();
                 //تعريف العلاقات   //
            $table->primary(['user_account_id', 'intensive_care_patients_id']);

            $table->foreign('user_account_id')
            ->references('id')
            ->on('user_accounts')
            ->onDelete('cascade');
      $table->foreign('intensive_care_patients_id')
            ->references('id')
            ->on('intensive_care_patients')
            ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('intensive_care_patient_user_');
    }
};
