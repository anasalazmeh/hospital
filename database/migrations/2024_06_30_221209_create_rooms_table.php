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
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            $table->string('room_number')->unique();
            $table->integer('capacity'); // عدد الأسرة في الغرفة
            $table->enum('status', ['available', 'occupied'])->default('available'); // مثل: available, occupied
            $table->boolean('is_active')->default(true); // true = مفعلة، false = معطلة
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
