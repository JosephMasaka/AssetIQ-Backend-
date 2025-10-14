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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number')->unique();
            $table->unsignedBigInteger('vendor_id');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('requisition_id')->nullable();
            $table->date('order_date');
            $table->string('status')->default('Draft'); // Draft, Approved, Delivered, Closed
            $table->string('currency')->default('USD');
            $table->timestamps();

            $table->foreign('vendor_id')->references('id')->on('vendors');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('requisition_id')->references('id')->on('requisitions');

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
