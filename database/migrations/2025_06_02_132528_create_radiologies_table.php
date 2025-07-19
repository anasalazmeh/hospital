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
        Schema::create('radiologies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')
                ->constrained('patients', 'id')
                ->onDelete('cascade');

            $table->foreignId('icup_id')
                ->nullable()
                ->constrained('intensive_care_patients', 'id')
                ->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->json('media_files')->nullable();
            $table->dateTime('radiologies_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiologies');
    }
};
