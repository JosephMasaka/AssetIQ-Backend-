<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Component installation history.
     *
     * This is the "Asset BOM history" in Maximo terms, or the equipment
     * dismantling/installation log in SAP PM terms. Every time a component
     * moves on/off a parent asset, we log it here rather than overwriting
     * `components.current_asset_id` blind — this is what lets you answer
     * "which battery was in this UPS in March?" or "how many times has this
     * part been swapped?" later for maintenance/warranty claims.
     */
    public function up(): void
    {
        Schema::create('component_installations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('component_id');
            $table->unsignedBigInteger('asset_id');

            $table->string('install_position')->nullable();
            $table->timestamp('installed_at');
            $table->timestamp('removed_at')->nullable();

            $table->enum('removal_reason', [
                'failed',
                'scheduled_replacement',
                'upgrade',
                'transferred',
            ])->nullable();

            $table->foreignId('installed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('removed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'component_id']);
            $table->index(['company_id', 'asset_id']);
            // A component can only be actively installed (removed_at null) once at a time —
            // enforced at the application layer in ComponentActionService, since a partial
            // unique index on removed_at IS NULL isn't portable across MySQL/Postgres in one migration.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('component_installations');
    }
};