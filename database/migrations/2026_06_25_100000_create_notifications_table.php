<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // In-app notifications table
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->string('type');
                $table->morphs('notifiable');
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                // $table->index(['notifiable_type', 'notifiable_id']);
            });
        }

        // Custom notification log for tracking all sent notifications
        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('notification_type'); // system, alert, warning, info, success
                $table->string('title');
                $table->text('message');
                $table->json('data')->nullable();
                $table->json('channels')->nullable(); // ['database', 'mail', 'sms']
                $table->string('status')->default('sent'); // sent, failed, queued
                $table->timestamp('read_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('related_type')->nullable(); // Polymorphic relation
                $table->unsignedBigInteger('related_id')->nullable();
                $table->unsignedBigInteger('company_id');
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['company_id', 'created_at']);
                $table->index(['related_type', 'related_id']);
                $table->index(['notification_type', 'created_at']);
            });
        }

        // SMS notification log
        if (!Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('phone_number');
                $table->text('message');
                $table->string('status')->default('pending'); // pending, sent, failed, delivered
                $table->string('provider')->nullable(); // twilio, nexmo, etc.
                $table->string('provider_message_id')->nullable();
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('company_id');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['company_id', 'created_at']);
                $table->index('status');
            });
        }

        // Email notification queue/log
        if (!Schema::hasTable('email_logs')) {
            Schema::create('email_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('to_email');
                $table->string('subject');
                $table->text('body')->nullable();
                $table->string('status')->default('pending'); // pending, sent, failed, bounced
                $table->text('error_message')->nullable();
                $table->json('metadata')->nullable();
                $table->unsignedBigInteger('company_id');
                $table->timestamp('sent_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index(['company_id', 'created_at']);
                $table->index('status');
            });
        }

        // Notification preferences per user
        if (!Schema::hasTable('notification_preferences')) {
            Schema::create('notification_preferences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('notification_type'); // asset_expiry, maintenance_due, approval_required, etc.
                $table->boolean('email_enabled')->default(true);
                $table->boolean('database_enabled')->default(true);
                $table->boolean('sms_enabled')->default(false);
                $table->json('custom_settings')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'notification_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('notifications');
    }
};
