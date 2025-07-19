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
        Schema::create('obgyn_doctor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('obgyn_id');
            $table->unsignedBigInteger('doctor_id');
            $table->foreign('obgyn_id')
                ->references('id')
                ->on('obstetrics_gynecology')
                ->onDelete('cascade');

            $table->foreign('doctor_id')
                ->references('id')
                ->on('user_accounts')
                ->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obgyn_doctor');
    }
};
