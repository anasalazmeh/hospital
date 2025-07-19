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
        Schema::create('user_specialty', function (Blueprint $table) {
            $table->unsignedBigInteger('user_account_id');
            $table->unsignedBigInteger('specialty_id')->constrained('specialties');
            $table->timestamps();
        
            // تعريف المفتاح المركب
            $table->primary(['user_account_id', 'specialty_id']);
        
            // تعريف العلاقات
            $table->foreign('user_account_id')
                  ->references('id')
                  ->on('user_accounts')
                  ->onDelete('cascade');
                  
            $table->foreign('specialty_id')
                  ->references('id')
                  ->on('specialties')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('_user_specialty_');
    }
};
