<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::create('depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('depreciation_run_id');
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('depreciation_area_id');

            $table->decimal('amount', 15, 2);
            $table->decimal('accumulated', 15, 2);
            $table->decimal('net_book_value', 15, 2);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('depreciation_entries');
    }
};
