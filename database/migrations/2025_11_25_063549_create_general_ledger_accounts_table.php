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
        Schema::create('gl_accounts', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('company_id');

            $table->string('gl_code')->unique();
            $table->string('name');
            $table->string('type');

            $table->unsignedBigInteger('asset_category')->nullable();
            $table->text('long_text')->nullable();

            $table->string('account_group')->nullable();
            $table->string('reconciliation_type')->nullable(); // customer/vendor/none
            $table->string('currency')->default('KES');
            $table->string('sort_key')->nullable();

            $table->boolean('balance_sheet_account')->default(false);
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gl_accounts');
    }
};
