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
        Schema::create('users', function (Blueprint $table) {
            // Primary Key (SAP = USER_ID / BNAME in USR02)
            $table->id()->comment('USER_ID (SAP: Unique User Identifier)');

            // Tenant / Client (SAP = MANDT)
            $table->unsignedBigInteger('tenant_id')->nullable()->comment('MANDT - Client/Tenant/Company reference');

            // Basic User Info
            $table->string('username')->unique()->comment('BNAME - User Login ID')->nullable();
            $table->string('name')->comment('Full Name');
            $table->string('email')->unique()->comment('SAP: SMTP_ADDR');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('google_id')->unique()->nullable();
            $table->string('role');

            // Security / Authentication
            $table->string('password')->comment('PASSCODE - Password Hash');
            $table->string('auth_provider')->nullable()->comment('USTYP/Auth Provider: Local, SSO, LDAP');
            $table->boolean('is_active')->default(true)->comment('UFLAG - Lock Status (0=Active,1=Locked)');

            // Role & Authorization
            $table->unsignedBigInteger('role_id')->nullable()->comment('Authorization Role ID (SAP: PROFILES)');
            $table->json('permissions')->nullable()->comment('Custom permission set (similar to SAP Authorization Objects)');

            // Contact Info
            $table->string('phone')->nullable()->comment('TEL_NUMBER');
            $table->string('job_title')->nullable()->comment('Job Title (SAP: STELL)');
            $table->string('department')->nullable()->comment('Department (SAP: DEPT)');

            // Audit Fields (SAP style: ERDAT, ERNAM, AEDAT, AENAM)
            $table->unsignedBigInteger('created_by')->nullable()->comment('ERNAM - Created By');
            $table->unsignedBigInteger('updated_by')->nullable()->comment('AENAM - Last Changed By');
            $table->unsignedBigInteger('deleted_by')->nullable()->comment('Deleted By (Custom extension)');

            // Standard Laravel fields (with SAP naming hints)
            $table->rememberToken()->comment('Logon Token (like SAP SECSTORE)');
            $table->timestamps(); // created_at ~ ERDAT, updated_at ~ AEDAT
            $table->softDeletes()->comment('Deletion Flag (like SAP LOEVM)');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
