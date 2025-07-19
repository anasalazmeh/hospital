<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('internal_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients', 'id')->onDelete('cascade');
            $table->string('status'); // يمكن استبدالها بـ enum إذا كانت الحالات محددة
            $table->foreignId('room_id')->constrained('rooms', 'id')->onDelete('cascade');
            $table->foreignId('bed_id')->constrained('beds', 'id')->onDelete('cascade');
            $table->text('notes')->nullable();
            $table->date('discharge_date')->nullable();
            $table->boolean('ecg')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('internal_departments');
    }
};