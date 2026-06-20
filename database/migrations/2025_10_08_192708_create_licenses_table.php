<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licenses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Adobe Creative Cloud", "Windows 11 Pro"
            $table->string('version')->nullable();
            $table->string('manufacturer')->nullable(); // e.g., "Microsoft", "Adobe"
            $table->text('license_key')->nullable();
            
            // License Metrics
            $table->integer('seats_purchased')->default(1);
            $table->integer('seats_assigned')->default(0);

            // Procurement Details
            $table->string('purchase_order_number')->nullable();
            $table->decimal('purchase_cost', 10, 2)->nullable();
            $table->date('purchase_date')->nullable();
        
            
            // Lifecycle Dates
            $table->enum('license_type', ['Perpetual', 'Subscription', 'Open_source', 'Trial', 'OEM', 'Enterprise']);
            $table->date('expiration_date')->nullable();
            $table->boolean('is_renewable')->default(false);

            // Status & Tracking
            $table->foreignId('vendor_id')->nullable()->constrained('vendors');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Preserve data history
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_licenses');
    }
};
