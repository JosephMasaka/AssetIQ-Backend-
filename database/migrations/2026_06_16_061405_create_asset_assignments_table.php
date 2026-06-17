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
        Schema::create('asset_assignments', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('tenant_id')->index();

            $table->foreignId('asset_id')
                ->constrained('assets')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->date('assigned_date');

            $table->date('expected_return_date')
                ->nullable();

            $table->date('returned_date')
                ->nullable();

            $table->enum('status', [
                'assigned',
                'returned',
                'lost',
                'damaged'
            ])->default('assigned');

            $table->text('notes')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_assignments');
    }
};
