<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('stock_transactions', callback: function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->unsignedBigInteger('location_id');
            $table->integer('quantity');
            $table->enum('transaction_type', ['purchase', 'return', 'damage', 'fulfillment']);
            $table->unsignedBigInteger('department_id')->nullable()->comment('القسم المرتبط (اختياري)');
            $table->unsignedBigInteger('supplier_id')->nullable()->comment('المورد المرتبط (اختياري)');
            $table->unsignedBigInteger('department_request_id')->nullable()->comment('المورد المرتبط (اختياري)');
            $table->unsignedBigInteger('user_id')->comment('المسؤول عن العملية');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('locations')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('department_request_id')->references('id')->on('department_requests')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('user_accounts')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_transactions');
    }
};