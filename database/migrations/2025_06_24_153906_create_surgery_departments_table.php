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
        Schema::create('surgery_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients', 'id')->onDelete('cascade');
            $table->string('status');
            $table->string('surgeryType');
            $table->date('surgeryDate');
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->foreignId('bed_id')->constrained('beds', 'id')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->dateTime('dressing_changed')->nullable();
            $table->string('wound_condition')->nullable();
            $table->string('surgical_drains')->nullable();
            $table->integer('pain_level')->nullable();
            $table->date('discharge_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('surgery_departments');
    }
};
