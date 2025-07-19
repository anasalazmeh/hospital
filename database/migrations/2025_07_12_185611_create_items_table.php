<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateItemsTable extends Migration
{
    public function up()
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id(); // رقم معرف
            $table->string('name'); // اسم الصنف
            $table->unsignedBigInteger('category_id'); // التصنيف (مفتاح أجنبي لو عندك جدول تصنيفات)
            $table->text('description')->nullable(); // الوصف
            $table->string('unit'); // وحدة القياس
            $table->integer('min_stock')->default(0); // الحد الأدنى
            $table->integer('max_stock')->default(0); // الحد الأقصى
            $table->string('barcode')->nullable(); // الباركود أو QRcode
            $table->timestamps();

            // لو عندك جدول تصنيفات أضف هذا المفتاح الأجنبي
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('items');
    }
}
