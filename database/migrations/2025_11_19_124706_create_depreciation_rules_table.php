<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        // Schema::create('depreciation_rules', function (Blueprint $table) {
        //     $table->id();
        //     // $table->unsignedBigInteger('company_id');
        //     $table->string('name'); // e.g. Straight Line, DB 20%, IFRS RULE 01
        //     $table->enum('method', ['straight_line', 'declining_balance', 'units_of_production', 'custom']);
        //     $table->integer('useful_life')->nullable(); // in years
        //     $table->decimal('salvage_value', 15, 2)->default(0);
        //     $table->string('gl_account')->nullable();
        //     $table->string('cost_center')->nullable();
            
        //     $table->boolean('is_active')->default(true);

        //     // SAP-style parameters
        //     $table->enum('period_control', ['pro_rata', 'full_month', 'half_year', 'first_month', 'custom'])->default('pro_rata');
        //     $table->enum('base_value', ['acquisition', 'net_book_value', 'custom'])->default('acquisition');

        //     $table->unsignedBigInteger('created_by')->nullable();
        //     $table->unsignedBigInteger('company_id');

        //     $table->timestamps();
        // });

        Schema::create('depreciation_rules', function (Blueprint $table) {
            $table->id();

            // Parent key (MANDATORY)
            $table->unsignedBigInteger('depreciation_key_id');

            // Depreciation Area (SAP AFABE)
            $table->string('depreciation_area', 10); // BOOK, TAX, IFRS

            // Rule identification
            $table->string('name'); // e.g. SL 5Y, DB 20%

            // Method (SAP supports multiple per key)
            $table->enum('method', [
                'straight_line',
                'declining_balance',
                'units_of_production',
                'custom'
            ]);

            // SAP-style parameters
            $table->integer('useful_life')->nullable(); // years
            $table->decimal('depreciation_rate', 8, 4)->nullable(); // % (DB)
            $table->decimal('salvage_value', 15, 2)->default(0);

            // Validity / multi-level control
            $table->integer('valid_from_year')->default(1);
            $table->integer('valid_to_year')->nullable();

            // Period & base control
            $table->enum('period_control', [
                'pro_rata',
                'full_month',
                'half_year',
                'first_month',
                'custom'
            ])->default('pro_rata');

            $table->enum('base_value', [
                'acquisition',
                'net_book_value',
                'custom'
            ])->default('acquisition');

            // Accounting integration
            $table->string('gl_account')->nullable();
            $table->string('cost_center')->nullable();

            $table->boolean('is_active')->default(true);

            // Audit
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['depreciation_key_id', 'depreciation_area']);

            // Foreign key (optional but recommended)
            // $table->foreign('depreciation_key_id')->references('id')->on('depreciation_keys');
        });
    }

    public function down() {
        Schema::dropIfExists('depreciation_rules');
    }
};



