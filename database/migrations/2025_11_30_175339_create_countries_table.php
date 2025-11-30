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
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();      // ISO Alpha-3 Code (KEN)
            $table->string('alpha2', 2)->unique()->nullable();    // ISO Alpha-2 (KE)
            $table->string('name');                   // Kenya
            $table->string('phone_code')->nullable(); // +254
            $table->string('currency')->nullable();   // KES
            $table->string('currency_symbol')->nullable(); // KSh, $, etc.
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
