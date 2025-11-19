<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();

            // relation to invoice header
            $table->unsignedBigInteger('invoice_id');

            // relation to PO item
            $table->unsignedBigInteger('po_item_id')->nullable();

            // relation to goods receipt line
            $table->unsignedBigInteger('gr_item_id')->nullable();

            // item fields
            $table->integer('line_number');
            $table->text('description');
            $table->decimal('quantity', 18, 3);
            $table->string('uom', 10);

            // pricing
            $table->decimal('unit_price', 18, 4);
            $table->decimal('tax_rate', 8, 2)->default(0);
            $table->decimal('line_total', 18, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
