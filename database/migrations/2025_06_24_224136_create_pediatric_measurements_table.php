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
        Schema::create('pediatric_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pediatric_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained("user_accounts", "id")->onDelete('cascade');

            $table->decimal('temperature', 5, 2)->nullable()->comment('حرارة');
            $table->integer('heart_rate')->nullable()->comment('نبض القلب');
            $table->string('blood_pressure', 20)->nullable()->comment('ضغط الدم');
            $table->integer('respiratory_rate')->nullable()->comment('معدل التنفس');
            $table->decimal('oxygen_saturation', 5, 2)->nullable()->comment('اكسجه');
            $table->decimal('glucose_level', 5, 2)->nullable()->comment('معدل السكري');
            $table->decimal('urine_output', 5, 2)->nullable()->comment('نسبة التبول');
            $table->text('serum')->nullable()->comment('السيروم');
            $table->text('medications')->nullable()->comment('الأدوية');
            $table->text('new_measurement')->nullable()->comment('قياس جديد');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pediatric_measurements');
    }
};
