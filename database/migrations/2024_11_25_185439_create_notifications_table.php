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
      Schema::create('notifications', function (Blueprint $table) {
    // الأساسيات
    $table->id();
    
    // العلاقات الأساسية
    $table->unsignedBigInteger('sender_account_id')->comment('مرتبط بجدول user_accounts - المرسل');
    $table->unsignedBigInteger('receiver_account_id')->comment('مرتبط بجدول user_accounts - المستقبل');
    $table->unsignedBigInteger('patient_id')->nullable()->comment('المريض المرتبط إن وجد');
    
    // محتوى الإشعار
    $table->string('title')->comment('عنوان مختصر للإشعار');
    $table->text('message')->comment('نص الإشعار التفصيلي');
    $table->string('link')->nullable()->comment('رابط مرتبط بالإشعار');
    $table->enum('priority', ['normal', 'urgent', 'critical'])->default('normal');
    
    // حالة الإشعار
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    
    // التواريخ
    $table->timestamps();
    $table->softDeletes();
    
    // المفاتيح الأجنبية
    $table->foreign('sender_account_id')
         ->references('id')
         ->on('user_accounts')
         ->onDelete('cascade');
         
    $table->foreign('receiver_account_id')
         ->references('id')
         ->on('user_accounts')
         ->onDelete('cascade');
         
    $table->foreign('patient_id')
         ->references('id')
         ->on('patients')
         ->onDelete('cascade');
    
    // الفهارس
    $table->index('receiver_account_id');
    $table->index(['receiver_account_id', 'is_read']);
    $table->index('created_at');
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
