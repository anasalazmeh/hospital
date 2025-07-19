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
        Schema::create('department_request_items', function (Blueprint $table) {
            $table->id();
            // العلاقة مع جدول طلبات الأقسام
            $table->unsignedBigInteger('department_request_id');
            // العلاقة مع جدول الأصناف (افترض أن لديك جدول items)
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('location_id')->nullable();;
            // كمية الصنف المطلوبة
            $table->integer('quantity')->default(1);
            // كمية الصنف التي تم الموافقة عليها (يمكن أن تكون أقل من الكمية المطلوبة)
            $table->integer('approved_quantity')->nullable();
            $table->integer('delivered_quantity')->nullable(); // الكمية التي تم إرسالها فعليًا
            $table->string('batch_number')->nullable();
            // تواريخ إنشاء وتحديث السجل
            $table->timestamps();

            // المفاتيح الخارجية
            $table->foreign('department_request_id')
                ->references('id')
                ->on('department_requests')
                ->onDelete('cascade');

            $table->foreign('location_id')
                ->references('id')
                ->on('locations')
                ->onDelete('set null');

            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('department_request_items');
    }
};
