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
        Schema::create('pediatrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade'); // رابط بجدول المرضى
            $table->string('status'); // حالة الطفل (مستقر، حرج، إلخ)
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->foreignId('bed_id')->constrained('beds', 'id')->onDelete('cascade');
            $table->boolean('ecg')->default(false); // هل تم عمل تخطيط قلب أم لا
            $table->string('vaccinations')->nullable(); // التطعيمات (يمكن تخزينها كمصفوفة JSON)
            $table->decimal('height', 5, 2)->nullable(); // الطول بالسنتيمتر
            $table->decimal('weight', 5, 2)->nullable(); // الوزن بالكيلوجرام
            $table->date('discharge_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pediatrics');
    }
};
