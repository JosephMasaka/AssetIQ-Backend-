<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('gr_id'); // Goods Receipt ID
            $table->unsignedBigInteger('po_item_id'); // PO Item ID
            $table->unsignedBigInteger('uom_id')->nullable();
            $table->integer('quantity_received');
            $table->timestamps();

            $table->foreign('gr_id')->references('id')->on('goods_receipts')->onDelete('cascade');
            $table->foreign('po_item_id')->references('id')->on('purchase_order_items')->onDelete('cascade');

            $table->foreign('uom_id')->references('uom_id')->on('units_of_measure')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};

