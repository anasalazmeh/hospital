<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('department_requests', function (Blueprint $table) {
            $table->id('id');
            $table->unsignedBigInteger('department_id');
            $table->enum('status', ['pending', 'approved', 'rejected', 'partially_fulfilled'])->default('pending');
            $table->timestamp('status_updated_at')->nullable(); // يُضبط تلقائيًا عند التغيير
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('requested_by');// شخص يلي طلب هذا الطب
            $table->unsignedBigInteger('processed_by')->nullable(); // مسوؤل عن تنفيذ الطلب ام قبول اوربفض او فبول جزئي

            $table->boolean('is_issued')->default(false); // تم إصدار المواد من المستودع؟
            $table->timestamp('issued_at')->nullable(); // تاريخ الصرف الفعلي
            $table->unsignedBigInteger('issued_by')->nullable(); // المسؤول عن الصرف
            $table->timestamps();
            // Foreign keys
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('cascade');
            $table->foreign('requested_by')->references('id')->on('user_accounts')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('user_accounts')->onDelete('set null'); // لا تحذف المستخدم إذا حذفنا الطلب
            $table->foreign('issued_by')->references('id')->on('user_accounts')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('department_requests');
    }
};