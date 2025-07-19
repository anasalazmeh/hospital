<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained('patients', 'id')
                ->onDelete('cascade');

            $table->foreignId('icup_id')
                ->nullable()
                ->constrained('intensive_care_patients', 'id')
                ->nullOnDelete(); // تغيير من cascade إلى nullOnDelete

            $table->string('title');
            $table->text('description')->nullable();
            $table->string('pdf_path');
            $table->dateTime('analysis_date'); // تغيير من date إلى dateTime
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
