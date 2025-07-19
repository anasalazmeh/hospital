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
        Schema::create('doctor_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('user_accounts', 'id')
                ->cascadeOnDelete(); // أو ->restrictOnDelete() حسب احتياجك
            $table->foreignId('icup_id')
                ->nullable()
                ->constrained('intensive_care_patients', 'id')
                ->nullOnDelete();
            $table->foreignId('patient_id')
                ->nullable()
                ->constrained('patients', 'id')
                ->nullOnDelete();
            $table->text('report'); // محتوى التقرير
            $table->text('department'); // اي قسم كتب هذا تقرير
            $table->enum('type', ['department', 'general'])->default('department');
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_reports');
    }
};
