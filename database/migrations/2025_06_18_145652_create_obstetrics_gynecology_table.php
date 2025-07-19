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
        Schema::create('obstetrics_gynecology', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');

            // Basic information
            $table->enum('status', ['prenatal', 'intrapartum', 'postpartum'])->default('prenatal'); //حالة المريضة: قبل الولادة، أثناء الولادة، بعد الولادة
            $table->integer('gestational_weeks')->nullable(); //عدد أسابيع الحمل الحالية
            $table->enum('delivery_type', ['vaginal', 'cesarean', 'assisted'])->nullable();//نوع الولادة: طبيعية، قيصرية، مساعدة
            $table->date('delivery_date')->nullable();//تاريخ الولادة الفعلي
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->foreignId('bed_id')->constrained('beds', 'id')->onDelete('cascade');
            $table->date('discharge_date')->nullable(); // تاريخ الخروج
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('obstetrics_gynecology');
    }
};
