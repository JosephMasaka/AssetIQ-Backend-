<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();

            // Short code for the account group (e.g. 1000, 2000)
            $table->string('code', 10)->unique();

            // Descriptive name (e.g. "Balance Sheet Accounts")
            $table->string('name');

            // Optional: type mapping (Assets / Liabilities / Expenses / Revenue)
            $table->string('category')->nullable();

            // Optional: description for internal understanding
            $table->text('description')->nullable();

            // Optional: status (active/inactive)
            $table->boolean('is_active')->default(true);

            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('company_id');
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_groups');
    }
};
