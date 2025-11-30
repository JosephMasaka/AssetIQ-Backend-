<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('depreciation_rules', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('company_id');
            $table->string('name'); // e.g. Straight Line, DB 20%, IFRS RULE 01
            $table->enum('method', ['straight_line', 'declining_balance', 'units_of_production', 'custom']);
            $table->integer('useful_life')->nullable(); // in years
            $table->decimal('salvage_value', 15, 2)->default(0);
            $table->string('gl_account')->nullable();
            $table->string('cost_center')->nullable();
            $table->string('asset_class')->nullable();
            $table->boolean('is_active')->default(true);

            // SAP-style parameters
            $table->enum('period_control', ['pro_rata', 'full_month', 'half_year', 'first_month', 'custom'])->default('pro_rata');
            $table->enum('base_value', ['acquisition', 'net_book_value', 'custom'])->default('acquisition');

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('company_id');

            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('depreciation_rules');
    }
};

