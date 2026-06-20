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
        Schema::create('asset_tracking_logs', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('asset_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('action');

            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            $table->unsignedBigInteger('user_id');

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_tracking_logs');
    }
};
