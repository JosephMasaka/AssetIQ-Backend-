<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Component categories — equivalent to Maximo's "Item Set / Classification"
     * or SAP's Material Group. Kept as its own table (rather than an enum)
     * since companies will want custom categories (RAM, PSU, Solar Panel,
     * Inverter Battery, etc.) — same pattern as your existing
     * asset_categories table.
     */
    public function up(): void
    {
        Schema::create('component_categories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_categories');
    }
};