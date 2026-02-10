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
        Schema::create('asset_depreciation_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id');
            $table->unsignedBigInteger('depreciation_area_id');
            $table->unsignedBigInteger('depreciation_rule_id');

            $table->integer('useful_life')->nullable(); // override
            $table->decimal('salvage_value', 15, 2)->nullable(); // override

            $table->date('capitalization_date');
            $table->string('cost_center')->nullable();
            $table->string('gl_account')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_depreciation_settings');
    }
};
