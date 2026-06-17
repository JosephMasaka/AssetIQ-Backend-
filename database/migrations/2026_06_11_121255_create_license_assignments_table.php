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
        Schema::create('license_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained()->onDelete('cascade');
            
            // Polymorphic relation to handle both Hardware Assets and Users
            $table->morphs('assignable'); 
            // Creates fields: assignable_type (e.g., 'App\Models\Asset') and assignable_id
            
            $table->date('assigned_at');
            $table->date('revoked_at')->nullable();
            $table->timestamps();
            
            // Prevent assigning the exact same license to the exact same asset twice
            $table->unique(['license_id', 'assignable_type', 'assignable_id'], 'unique_license_assignment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_assignments');
    }
};
