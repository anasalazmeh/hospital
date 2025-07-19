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
        Schema::create('internal_department_doctor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internal_department_id')->constrained('internal_departments')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('user_accounts')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('internal_department_doctor');
    }
};
