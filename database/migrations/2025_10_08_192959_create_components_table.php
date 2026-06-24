<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Components module.
     *
     * Modeled on:
     *  - IBM Maximo "Item / Rotable Asset" pattern: a component is a discrete,
     *    serialized or batch-tracked part that can be installed on a parent
     *    Asset, removed, repaired, and re-installed elsewhere. Unlike a
     *    Consumable, it is not "used up" — it has its own lifecycle.
     *  - SAP PM "Equipment BOM" pattern: components are linked to a parent
     *    piece of equipment (here, our Asset) with quantity + position, and
     *    stock-managed items have min/max/reorder thresholds (SAP MM logic).
     *
     * company_id scoping follows the same multi-tenancy convention used
     * across Assets, Consumables, and Licenses.
     */
    public function up(): void
    {
        Schema::create('components', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('users')->cascadeOnDelete();

            // --- Identity (Maximo: Item Number / SAP: Material Number) ---
            $table->string('component_tag')->comment('Internal unique code, e.g. CMP-00231');
            $table->string('name');
            $table->string('manufacturer')->nullable();
            $table->string('model_number')->nullable();
            $table->string('serial_number')->nullable()
                ->comment('Null for non-serialized/batch-tracked components, e.g. generic fan units');
            $table->foreignId('category_id')->nullable()
                ->constrained('component_categories')->nullOnDelete();

            // --- Rotable vs consumed-on-install (Maximo "Rotating Item" flag) ---
            $table->boolean('is_rotable')->default(true)
                ->comment('True: can be removed, repaired, and reinstalled elsewhere. False: scrapped on removal.');

            // --- Stock management (SAP MM-style, for spare components in the bin) ---
            $table->unsignedInteger('quantity_on_hand')->default(0)
                ->comment('Units sitting in spares inventory, not yet installed on any asset');
            $table->unsignedInteger('reorder_point')->nullable()
                ->comment('SAP MRP-style reorder trigger for spare stock');
            $table->unsignedInteger('reorder_quantity')->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->string('currency', 3)->default('KES');

            // --- Lifecycle (mirrors lifecycle_status convention on assets) ---
            $table->enum('lifecycle_status', [
                'in_stock',     // sitting in spares inventory
                'installed',    // currently attached to a parent asset
                'in_repair',    // sent out / under repair (Maximo "Repair Facility" status)
                'retired',      // scrapped, end of life
            ])->default('in_stock');

            // --- Current installation pointer (denormalized for fast lookups) ---
            $table->foreignId('current_asset_id')->nullable()
                ->constrained('assets')->nullOnDelete()
                ->comment('Parent asset this component is currently installed on, if any');
            $table->string('install_position')->nullable()
                ->comment('SAP BOM "item position" equivalent, e.g. Slot A1, Bay 2');

            // --- Warranty / maintenance ---
            $table->date('warranty_expiry')->nullable();
            $table->date('last_serviced_at')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'component_tag']);
            $table->index(['company_id', 'lifecycle_status']);
            $table->index(['company_id', 'current_asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('components');
    }
};