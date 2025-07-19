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
        Schema::create('pediatrics_department_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pediatrics_id')->constrained('pediatrics')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('user_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pediatrics_department_doctor');
    }
};
