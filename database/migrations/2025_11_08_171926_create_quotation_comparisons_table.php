<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_comparisons', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('requisition_id');
            $table->string('title')->nullable(); // e.g. "Laptop Tender Comparison"
            $table->date('compared_on')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); // user id
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');

            $table->timestamps();

            $table->foreign('requisition_id')->references('id')->on('requisitions')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('quotation_comparison_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('comparison_id');
            $table->unsignedBigInteger('quotation_item_id');
            $table->unsignedBigInteger('vendor_id');

            // Prices at time of comparison
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);

            // Rank = 1 (best price), 2, 3...
            $table->integer('rank')->default(0);

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('company_id');

            $table->timestamps();

            $table->foreign('comparison_id')->references('id')->on('quotation_comparisons')->onDelete('cascade');
            $table->foreign('quotation_item_id')->references('id')->on('quotation_items')->onDelete('cascade');
            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_comparison_lines');
        Schema::dropIfExists('quotation_comparisons');
    }
};
